<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'CTV Dashboard | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '
 
 
';
require_once(__DIR__.'/../../models/is_ctv.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');

?>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-2 page-header-breadcrumb mb-3">
            <div>
                <h1 class="page-title fw-semibold fs-18 mb-0"><?=__('Chào mừng');?>, <?=$getUser['username'];?>!</h1>
                <p class="text-muted mb-0"><?=__('Quản lý hoạt động cộng tác viên của bạn');?></p>
            </div>
            <div class="btn-list mt-md-0 mt-2">
                <a href="<?=base_url();?>" class="btn btn-primary">
                    <i class="ri-home-4-line me-1"></i><?=__('Về trang chủ');?>
                </a>
            </div>
        </div>

        <!-- Thông tin tài khoản CTV -->
        <div class="alert alert-info alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
            <div class="d-flex align-items-center mb-3">
                <i class="fas fa-bell text-info me-2"></i>
                <h5 class="mb-0"><?=__('Thông báo từ Admin');?></h5>
            </div>
            <?=$CMSNT->site('ctv_notice');?>
        </div>

        <div class="row">
            <!-- Số dư hiện tại -->
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card hrm-main-card primary">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar bg-primary">
                                    <i class="fa-solid fa-wallet fs-18"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <span class="fw-semibold text-muted d-block mb-2"><?=__('Số dư hiện tại');?></span>
                                <h5 class="fw-semibold mb-2 text-primary">
                                    <?=format_currency($getUser['money']);?>
                                </h5>
                                <p class="mb-0">
                                    <span class="badge bg-primary-transparent"><?=__('Tài khoản chính');?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Đơn hàng đã bán hôm nay -->
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card hrm-main-card secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar bg-info">
                                    <i class="fa-solid fa-cart-shopping fs-18"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <span class="fw-semibold text-muted d-block mb-2"><?=__('Đơn hàng hôm nay');?></span>
                                <h5 class="fw-semibold mb-2" id="orders_today">
                                    <?php
                                    $orders_today = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `product_order` WHERE DATE(`create_gettime`) = CURDATE() AND `seller` = ?", [$getUser['id']]);
                                    echo $orders_today['total'];
                                    ?>
                                </h5>
                                <p class="mb-0">
                                    <span class="badge bg-info-transparent"><?=__('Hôm nay');?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doanh thu hôm nay -->
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card hrm-main-card warning">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar bg-warning">
                                    <i class="fa-solid fa-chart-simple fs-18"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <span class="fw-semibold text-muted d-block mb-2"><?=__('Doanh thu hôm nay');?></span>
                                <h5 class="fw-semibold mb-2" id="revenue_today">
                                    <?php
                                    $revenue_today = $CMSNT->get_row_safe("SELECT SUM(`pay`) as total FROM `product_order` WHERE DATE(`create_gettime`) = CURDATE() AND `seller` = ?", [$getUser['id']]);
                                    echo format_currency($revenue_today['total'] ?? 0);
                                    ?>
                                </h5>
                                <p class="mb-0">
                                    <span class="badge bg-warning-transparent"><?=__('Hôm nay');?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Tổng đơn hàng -->
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card hrm-main-card success">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar bg-success">
                                    <i class="fa-solid fa-shopping-bag fs-18"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <span class="fw-semibold text-muted d-block mb-2"><?=__('Tổng đơn hàng');?></span>
                                <h5 class="fw-semibold mb-2">
                                    <?php
                                    $total_orders = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `product_order` WHERE `seller` = ?", [$getUser['id']]);
                                    echo $total_orders['total'];
                                    ?>
                                </h5>
                                <p class="mb-0">
                                    <span class="badge bg-success-transparent"><?=__('Toàn thời gian');?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tổng doanh thu -->
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card hrm-main-card info">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar bg-info">
                                    <i class="fa-solid fa-coins fs-18"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <span class="fw-semibold text-muted d-block mb-2"><?=__('Tổng doanh thu');?></span>
                                <h5 class="fw-semibold mb-2">
                                    <?php
                                    $total_revenue = $CMSNT->get_row_safe("SELECT SUM(`pay`) as total FROM `product_order` WHERE `seller` = ?", [$getUser['id']]);
                                    echo format_currency($total_revenue['total'] ?? 0);
                                    ?>
                                </h5>
                                <p class="mb-0">
                                    <span class="badge bg-info-transparent"><?=__('Toàn thời gian');?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Khách hàng -->
            <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                <div class="card custom-card hrm-main-card secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-top">
                            <div class="me-3">
                                <span class="avatar bg-secondary">
                                    <i class="fa-solid fa-users fs-18"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <span class="fw-semibold text-muted d-block mb-2"><?=__('Khách hàng');?></span>
                                <h5 class="fw-semibold mb-2">
                                    <?php
                                    $total_customers = $CMSNT->get_row_safe("SELECT COUNT(DISTINCT `buyer`) as total FROM `product_order` WHERE `seller` = ?", [$getUser['id']]);
                                    echo $total_customers['total'];
                                    ?>
                                </h5>
                                <p class="mb-0">
                                    <span class="badge bg-secondary-transparent"><?=__('Duy nhất');?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Đơn hàng gần đây -->
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title"><?=__('ĐƠN HÀNG GẦN ĐÂY');?></div>
                        <div class="ms-auto">
                            <a href="<?=base_url_ctv('orders');?>" class="btn btn-sm btn-primary"><?=__('Xem tất cả');?></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th><?=__('ID');?></th>
                                        <th><?=__('Sản phẩm');?></th>
                                        <th><?=__('Khách hàng');?></th>
                                        <th><?=__('Số tiền');?></th>
                                        <th><?=__('Thời gian');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recent_orders = $CMSNT->get_list_safe("SELECT po.*, p.name as product_name, u.username 
                                                                      FROM `product_order` po 
                                                                      LEFT JOIN `products` p ON po.product_id = p.id 
                                                                      LEFT JOIN `users` u ON po.buyer = u.id 
                                                                      WHERE po.seller = ? 
                                                                      ORDER BY po.create_gettime DESC LIMIT 10", [$getUser['id']]);
                                    if(empty($recent_orders)) {
                                        echo '<tr><td colspan="5" class="text-center">'.__('Chưa có đơn hàng nào').'</td></tr>';
                                    } else {
                                        foreach($recent_orders as $order) {
                                    ?>
                                    <tr>
                                        <td>#<?=$order['id'];?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold"><?=$order['product_name'] ?? $order['product_name'];?> <a class="text-primary" href="<?=base_url_ctv('product-edit&id='.$order['product_id']);?>"><i class="fa-solid fa-edit"></i></a></span>
                                                <small class="text-muted"><?=__('Số lượng:');?> <?=$order['amount'];?></small>
                                            </div>
                                        </td>
                                        <td><?=$order['username'] ?? 'N/A';?></td>
                                        <td><span class="text-success fw-bold"><?=format_currency($order['pay']);?></span></td>
                                        <td><?=date('d/m/Y H:i', strtotime($order['create_gettime']));?></td>
                                    </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thống kê nhanh -->
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title"><?=__('THỐNG KÊ NHANH');?></div>
                    </div>
                    <div class="card-body">
                        <!-- Thống kê 7 ngày gần đây -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3"><?=__('7 ngày gần đây');?></h6>
                            <?php
                            $stats_7days = $CMSNT->get_row_safe("SELECT 
                                                            COUNT(*) as orders,
                                                            SUM(pay) as revenue,
                                                            SUM(commission_fee) as commission
                                                            FROM `product_order` 
                                                            WHERE DATE(create_gettime) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                                                            AND seller = ?", [$getUser['id']]);
                            ?>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="p-2 bg-light rounded d-flex justify-content-between">
                                        <span><?=__('Đơn hàng:');?></span>
                                        <strong class="text-primary"><?=$stats_7days['orders'] ?? 0;?></strong>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="p-2 bg-light rounded d-flex justify-content-between">
                                        <span><?=__('Doanh thu:');?></span>
                                        <strong class="text-success"><?=format_currency($stats_7days['revenue'] ?? 0);?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thống kê tháng này -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3"><?=__('Tháng này');?></h6>
                            <?php
                            $stats_month = $CMSNT->get_row_safe("SELECT 
                                                           COUNT(*) as orders,
                                                           SUM(pay) as revenue,
                                                           SUM(commission_fee) as commission
                                                           FROM `product_order` 
                                                           WHERE MONTH(create_gettime) = MONTH(CURDATE()) 
                                                           AND YEAR(create_gettime) = YEAR(CURDATE())
                                                           AND seller = ?", [$getUser['id']]);
                            ?>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="p-2 bg-light rounded d-flex justify-content-between">
                                        <span><?=__('Đơn hàng:');?></span>
                                        <strong class="text-primary"><?=$stats_month['orders'] ?? 0;?></strong>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="p-2 bg-light rounded d-flex justify-content-between">
                                        <span><?=__('Doanh thu:');?></span>
                                        <strong class="text-success"><?=format_currency($stats_month['revenue'] ?? 0);?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top sản phẩm bán chạy -->
                        <div>
                            <h6 class="fw-semibold mb-3"><?=__('Top sản phẩm bán chạy');?></h6>
                            <?php
                            $top_products = $CMSNT->get_list_safe("SELECT p.name, COUNT(po.id) as sold, SUM(po.pay) as revenue
                                                             FROM `product_order` po 
                                                             LEFT JOIN `products` p ON po.product_id = p.id 
                                                             WHERE po.seller = ?
                                                             GROUP BY po.product_id 
                                                             ORDER BY sold DESC LIMIT 5", [$getUser['id']]);
                            if(empty($top_products)) {
                                echo '<p class="text-muted">'.__('Chưa có dữ liệu').'</p>';
                            } else {
                                foreach($top_products as $product) {
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                <div>
                                    <div class="fw-semibold"><?=substr($product['name'], 0, 30);?>...</div>
                                    <small class="text-muted"><?=__('Đã bán:');?> <?=format_cash($product['sold']);?></small>
                                </div>
                                <div class="text-end">
                                    <div class="text-success fw-bold"><?=format_currency($product['revenue']);?></div>
                                </div>
                            </div>
                            <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__.'/footer.php');
?>
<script type="text/javascript">
new ClipboardJS(".copy");

function copy() {
    showMessage('<?=__('Đã sao chép vào bộ nhớ tạm');?>', 'success');
}

 
</script>
