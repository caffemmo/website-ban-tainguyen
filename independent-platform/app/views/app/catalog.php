<div class="page-intro">
    <div><span class="eyebrow"><i data-lucide="store"></i> CATALOG</span><h1>Tất cả sản phẩm</h1><p>So sánh sản phẩm, dịch vụ và giá trước khi bắt đầu.</p></div>
    <a class="button button-secondary" href="<?= e(url('/app/orders')) ?>"><i data-lucide="receipt-text"></i> Đơn hàng</a>
</div>
<div class="catalog-toolbar">
    <div class="filter-pills"><button class="filter-pill is-active" type="button" data-filter="all">Tất cả</button><button class="filter-pill" type="button" data-filter="proxy">Proxy</button><button class="filter-pill" type="button" data-filter="social">Mạng xã hội</button><button class="filter-pill" type="button" data-filter="digital">Tài nguyên số</button></div>
    <label class="search-field"><i data-lucide="search"></i><input type="search" data-filter-input placeholder="Tìm sản phẩm..."></label>
</div>
<div class="product-grid" data-product-grid>
    <?php foreach ($products as $product): $type = strtolower((string) ($product['product_type'] ?? $product['type'] ?? 'digital')); $name = (string) ($product['name'] ?? 'Sản phẩm'); $price = $product['price'] ?? 0; $stock = $product['stock_count'] ?? ($product['stock'] ?? 'Sẵn sàng'); $productId = (int) ($product['id'] ?? 0); ?>
        <article class="product-card" data-type="<?= e($type) ?>" data-name="<?= e(strtolower($name)) ?>">
            <div class="product-art tone-<?= e($type === 'proxy' ? 'teal' : ($type === 'social' ? 'blue' : 'violet')) ?>"><i data-lucide="<?= $type === 'proxy' ? 'network' : ($type === 'social' ? 'badge-check' : 'box') ?>"></i></div>
            <div class="product-body"><div class="product-meta"><span><?= e($product['category_name'] ?? ucfirst($type)) ?></span><b><?= e(is_numeric($stock) ? ((int) $stock > 0 ? 'Còn hàng' : 'Theo cấu hình') : $stock) ?></b></div><h2><?= e($name) ?></h2><p><?= e($product['description'] ?? 'Thông tin rõ ràng, giá hiển thị trước khi thanh toán.') ?></p><div class="product-footer"><strong><?= e(format_money($price)) ?></strong><?php if ($type === 'proxy'): ?><a class="button button-small button-ghost" href="<?= e(url('/app/proxy')) ?>">Mở <i data-lucide="arrow-up-right"></i></a><?php elseif ($type === 'social'): ?><a class="button button-small button-ghost" href="<?= e(url('/app/social')) ?>">Mở <i data-lucide="arrow-up-right"></i></a><?php else: ?><button class="button button-small button-primary" type="button" data-product-buy data-product-id="<?= e($productId) ?>" <?= $productId < 1 ? 'disabled' : '' ?>>Mua <i data-lucide="shopping-cart"></i></button><?php endif; ?></div></div>
        </article>
    <?php endforeach; ?>
</div>
<p class="form-result" data-order-result role="status"></p>
