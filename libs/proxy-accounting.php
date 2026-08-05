<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once __DIR__ . '/youproxy.php';

function proxy_accounting_month_bounds($month = '')
{
    $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $month) ? (string) $month : date('Y-m');
    $start = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');
    if (!$start) {
        $month = date('Y-m');
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01');
    }
    return [
        'month' => $month,
        'start' => $start->format('Y-m-d 00:00:00'),
        'end' => $start->modify('+1 month')->format('Y-m-d 00:00:00')
    ];
}

function proxy_accounting_empty_summary()
{
    return [
        'orders' => 0,
        'quantity' => 0,
        'revenue' => 0,
        'direct_cost' => 0,
        'retail_cost' => 0,
        'cost_of_goods_sold' => 0,
        'profit' => 0
    ];
}

function proxy_accounting_batch_cost($batch, $usdRate)
{
    $storedCost = max(0, (float) ($batch['provider_cost_vnd'] ?? 0));
    if ($storedCost > 0) {
        return round($storedCost, 2);
    }
    $price = max(0, (float) ($batch['provider_price'] ?? 0));
    $currency = strtoupper(trim((string) ($batch['provider_currency'] ?? 'USD')));
    return round($currency === 'VND' ? $price : $price * $usdRate, 2);
}

function proxy_accounting_batch_unit_cost($batch, $usdRate)
{
    $storedUnitCost = max(0, (float) ($batch['acquisition_unit_cost_vnd'] ?? 0));
    if ($storedUnitCost > 0) {
        return round($storedUnitCost, 2);
    }
    $quantity = max(1, (int) ($batch['expected_quantity'] ?? 1));
    return round(proxy_accounting_batch_cost($batch, $usdRate) / $quantity, 2);
}

function proxy_accounting_add_summary(&$summary, $order)
{
    $summary['orders']++;
    $summary['quantity'] += max(1, (int) ($order['quantity'] ?? 1));
    $summary['revenue'] += max(0, (float) ($order['revenue'] ?? 0));
    $summary['direct_cost'] += max(0, (float) ($order['direct_cost'] ?? 0));
    $summary['retail_cost'] += max(0, (float) ($order['retail_cost'] ?? 0));
    $summary['cost_of_goods_sold'] = $summary['direct_cost'] + $summary['retail_cost'];
    $summary['profit'] = $summary['revenue'] - $summary['cost_of_goods_sold'];
}

function proxy_accounting_fetch_batches()
{
    global $CMSNT;
    return $CMSNT->get_list_safe(
        'SELECT `id`, `country`, `protocol`, `rent_period_days`, `expected_quantity`, `received_quantity`,
                `provider_price`, `provider_cost_vnd`, `retail_unit_price`, `provider_currency`, `status`, `created_at`
         FROM `proxy_ipv6_batches`
         ORDER BY `created_at` ASC, `id` ASC'
    ) ?: [];
}

function proxy_accounting_fetch_orders()
{
    global $CMSNT;
    return $CMSNT->get_list_safe(
        'SELECT o.`id`, o.`user_id`, o.`proxy_type`, o.`provider_order_id`, o.`provider_price`, o.`provider_cost_vnd`, o.`provider_currency`, o.`wallet_amount`,
                o.`quantity`, o.`status`, o.`created_at`,
                i.`id` AS `inventory_id`, i.`batch_id`,
                i.`acquisition_unit_cost_vnd`, b.`provider_price` AS `batch_provider_price`,
                b.`provider_cost_vnd` AS `batch_provider_cost_vnd`,
                b.`provider_currency` AS `batch_provider_currency`,
                b.`expected_quantity` AS `batch_expected_quantity`
         FROM `proxy_orders` o
         LEFT JOIN `proxy_ipv6_inventory` i ON i.`customer_proxy_order_id` = o.`id`
         LEFT JOIN `proxy_ipv6_batches` b ON b.`id` = i.`batch_id`
         WHERE o.`status` <> ?
         ORDER BY o.`created_at` ASC, o.`id` ASC',
        ['refunded']
    ) ?: [];
}

function proxy_accounting_summarize_orders($rows, $usdRate)
{
    $orders = [];
    foreach ((array) $rows as $row) {
        $orderId = (int) ($row['id'] ?? 0);
        if ($orderId <= 0) {
            continue;
        }
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'id' => $orderId,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                'revenue' => max(0, (float) ($row['wallet_amount'] ?? 0)),
                'provider_price' => max(0, (float) ($row['provider_price'] ?? 0)),
                'provider_cost_vnd' => max(0, (float) ($row['provider_cost_vnd'] ?? 0)),
                'direct_cost' => 0,
                'retail_cost' => 0,
                'inventory_ids' => [],
                'has_inventory' => false,
                'provider_order_id' => trim((string) ($row['provider_order_id'] ?? '')),
                'provider_currency' => strtoupper(trim((string) ($row['provider_currency'] ?? 'USD')))
            ];
        }
        $order = &$orders[$orderId];
        $inventoryId = (int) ($row['inventory_id'] ?? 0);
        if ($inventoryId > 0 && !isset($order['inventory_ids'][$inventoryId])) {
            $order['inventory_ids'][$inventoryId] = true;
            $order['has_inventory'] = true;
            $batch = [
                'acquisition_unit_cost_vnd' => $row['acquisition_unit_cost_vnd'] ?? 0,
                'provider_price' => $row['batch_provider_price'] ?? 0,
                'provider_cost_vnd' => $row['batch_provider_cost_vnd'] ?? 0,
                'provider_currency' => $row['batch_provider_currency'] ?? 'USD',
                'expected_quantity' => $row['batch_expected_quantity'] ?? 1
            ];
            $order['retail_cost'] += proxy_accounting_batch_unit_cost($batch, $usdRate);
        }
        unset($order);
    }

    $summary = proxy_accounting_empty_summary();
    $byMonth = [];
    foreach ($orders as $order) {
        if (!$order['has_inventory'] && $order['provider_order_id'] !== '') {
            $currency = 'USD';
            $order['direct_cost'] = proxy_accounting_batch_cost([
                'provider_price' => $order['provider_price'] ?? 0,
                'provider_cost_vnd' => $order['provider_cost_vnd'] ?? 0,
                'provider_currency' => $order['provider_currency'] ?? $currency
            ], $usdRate);
        }
        proxy_accounting_add_summary($summary, $order);
        $month = substr($order['created_at'], 0, 7);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            continue;
        }
        if (!isset($byMonth[$month])) {
            $byMonth[$month] = proxy_accounting_empty_summary();
        }
        proxy_accounting_add_summary($byMonth[$month], $order);
    }
    $summary['by_month'] = $byMonth;
    return $summary;
}

function proxy_accounting_stock_summary($batches, $usdRate)
{
    global $CMSNT;
    $rows = $CMSNT->get_list_safe(
        'SELECT i.`id`, i.`country`, i.`protocol`, i.`rent_period_days`, i.`retail_price`, i.`acquisition_unit_cost_vnd`, i.`status`,
                b.`provider_price`, b.`provider_cost_vnd`, b.`provider_currency`, b.`expected_quantity`
         FROM `proxy_ipv6_inventory` i
         LEFT JOIN `proxy_ipv6_batches` b ON b.`id` = i.`batch_id`
         WHERE i.`status` IN (?, ?) AND i.`date_end` >= ?
         ORDER BY i.`country` ASC, i.`date_end` ASC, i.`id` ASC',
        ['available', 'reserved', date('Y-m-d H:i:s')]
    ) ?: [];

    $stock = [
        'quantity' => 0,
        'cost' => 0,
        'revenue' => 0,
        'profit' => 0,
        'by_country' => []
    ];
    foreach ($rows as $row) {
        $batch = [
            'acquisition_unit_cost_vnd' => $row['acquisition_unit_cost_vnd'] ?? 0,
            'provider_price' => $row['provider_price'] ?? 0,
            'provider_cost_vnd' => $row['provider_cost_vnd'] ?? 0,
            'provider_currency' => $row['provider_currency'] ?? 'USD',
            'expected_quantity' => $row['expected_quantity'] ?? 1
        ];
        $cost = proxy_accounting_batch_unit_cost($batch, $usdRate);
        $revenue = max(0, (float) ($row['retail_price'] ?? 0));
        $country = strtoupper(trim((string) ($row['country'] ?? 'Unknown')));
        $stock['quantity']++;
        $stock['cost'] += $cost;
        $stock['revenue'] += $revenue;
        if (!isset($stock['by_country'][$country])) {
            $stock['by_country'][$country] = ['quantity' => 0, 'cost' => 0, 'revenue' => 0];
        }
        $stock['by_country'][$country]['quantity']++;
        $stock['by_country'][$country]['cost'] += $cost;
        $stock['by_country'][$country]['revenue'] += $revenue;
    }
    $stock['profit'] = $stock['revenue'] - $stock['cost'];
    foreach ($stock['by_country'] as &$country) {
        $country['profit'] = $country['revenue'] - $country['cost'];
    }
    unset($country);
    return $stock;
}

function proxy_accounting_report($month = '')
{
    global $CMSNT;
    $period = proxy_accounting_month_bounds($month);
    if (!youproxy_ensure_tables()) {
        return ['success' => false, 'message' => 'Không thể chuẩn bị dữ liệu kế toán proxy.'];
    }

    $config = youproxy_config();
    $usdRate = max(1, (float) ($config['usd_rate'] ?? 25000));
    $batches = proxy_accounting_fetch_batches();
    $orders = proxy_accounting_fetch_orders();
    $orderSummary = proxy_accounting_summarize_orders($orders, $usdRate);
    $batchCostByMonth = [];
    $batchCountByMonth = [];
    $batchCostAll = 0;
    $batchCountAll = 0;
    foreach ($batches as $batch) {
        $cost = proxy_accounting_batch_cost($batch, $usdRate);
        $batchCostAll += $cost;
        $batchCountAll++;
        $batchMonth = substr((string) ($batch['created_at'] ?? ''), 0, 7);
        if ($batchMonth !== '') {
            $batchCostByMonth[$batchMonth] = ($batchCostByMonth[$batchMonth] ?? 0) + $cost;
            $batchCountByMonth[$batchMonth] = ($batchCountByMonth[$batchMonth] ?? 0) + 1;
        }
    }

    $selectedSales = $orderSummary['by_month'][$period['month']] ?? proxy_accounting_empty_summary();
    $selectedInput = (float) ($batchCostByMonth[$period['month']] ?? 0) + (float) $selectedSales['direct_cost'];
    $selected = [
        'month' => $period['month'],
        'input_cost' => $selectedInput,
        'revenue' => (float) $selectedSales['revenue'],
        'cost_of_goods_sold' => (float) $selectedSales['cost_of_goods_sold'],
        'profit' => (float) $selectedSales['profit'],
        'cash_difference' => (float) $selectedSales['revenue'] - $selectedInput,
        'orders' => (int) $selectedSales['orders'],
        'quantity' => (int) $selectedSales['quantity'],
        'batch_count' => 0
    ];
    foreach ($batches as $batch) {
        if (substr((string) ($batch['created_at'] ?? ''), 0, 7) === $period['month']) {
            $selected['batch_count']++;
        }
    }

    $monthly = [];
    $monthStart = DateTimeImmutable::createFromFormat('!Y-m-d', $period['month'] . '-01');
    for ($offset = 11; $offset >= 0; $offset--) {
        $monthKey = $monthStart->modify('-' . $offset . ' months')->format('Y-m');
        $sales = $orderSummary['by_month'][$monthKey] ?? proxy_accounting_empty_summary();
        $input = (float) ($batchCostByMonth[$monthKey] ?? 0) + (float) $sales['direct_cost'];
        $monthly[] = [
            'month' => $monthKey,
            'input_cost' => $input,
            'revenue' => (float) $sales['revenue'],
            'cost_of_goods_sold' => (float) $sales['cost_of_goods_sold'],
            'profit' => (float) $sales['profit'],
            'cash_difference' => (float) $sales['revenue'] - $input,
            'orders' => (int) $sales['orders'],
            'batch_count' => (int) ($batchCountByMonth[$monthKey] ?? 0)
        ];
    }

    $allRevenue = (float) $orderSummary['revenue'];
    $allCostOfGoods = (float) $orderSummary['cost_of_goods_sold'];
    $allInput = $batchCostAll + (float) $orderSummary['direct_cost'];
    $stock = proxy_accounting_stock_summary($batches, $usdRate);
    return [
        'success' => true,
        'period' => $period,
        'usd_rate' => $usdRate,
        'selected' => $selected,
        'all' => [
            'batch_count' => $batchCountAll,
            'input_cost' => $allInput,
            'revenue' => $allRevenue,
            'cost_of_goods_sold' => $allCostOfGoods,
            'profit' => $allRevenue - $allCostOfGoods,
            'cash_difference' => $allRevenue - $allInput,
            'orders' => (int) $orderSummary['orders'],
            'quantity' => (int) $orderSummary['quantity']
        ],
        'stock' => $stock,
        'monthly' => $monthly
    ];
}
