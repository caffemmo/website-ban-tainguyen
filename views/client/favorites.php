<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Sản phẩm yêu thích').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_user.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
// ✅ Sử dụng validation functions an toàn
$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;

$from = ($page - 1) * $limit;

// ✅ Sử dụng prepared statements pattern
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];

$shortByDate = '';
$time = '';

// ✅ An toàn cho date range filtering
if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if($time !== false) {
        $create_date_1 = str_replace('-', '/', $time);
        $create_date_1 = explode(' to ', $create_date_1);
        if(count($create_date_1) == 2 && $create_date_1[0] != $create_date_1[1]){
            $start_date = $create_date_1[0].' 00:00:00';
            $end_date = $create_date_1[1].' 23:59:59';
            // Validate date format
            if(validate_date($create_date_1[0], 'Y/m/d') !== false && validate_date($create_date_1[1], 'Y/m/d') !== false) {
                $where_conditions[] = '`create_gettime` >= ? AND `create_gettime` <= ?';
                $where_params[] = $start_date;
                $where_params[] = $end_date;
            }
        }
    }
}

// ✅ An toàn cho shortByDate filtering
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false) {
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");

        if($shortByDate == 1){
            $where_conditions[] = '`create_gettime` LIKE ?';
            $where_params[] = '%'.$currentDate.'%';
        }
        if($shortByDate == 2){
            $where_conditions[] = 'YEAR(create_gettime) = ? AND WEEK(create_gettime, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = 'MONTH(create_gettime) = ? AND YEAR(create_gettime) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

// ✅ Sử dụng prepared statements an toàn
$where_clause = implode(' AND ', $where_conditions);
$sql_list = "SELECT * FROM `favorites` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_with_limit);

$sql_count = "SELECT COUNT(*) as total FROM `favorites` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($sql_count, $where_params)['total'];

$urlDatatable = pagination_client(base_url("?action=favorites&limit=$limit&shortByDate=$shortByDate&"), $from, $totalDatatable, $limit);
?>
<section class="inner-section single-banner"
    style="background: url(<?=base_url($CMSNT->site('banner_singer'));?>) no-repeat center;">
    <div class="container">
        <h2><?=__('Yêu thích');?></h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.html"><?=__('Trang chủ');?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?=__('Yêu thích');?></li>
        </ol>
    </div>
</section>
<section class="inner-section profile-part">
    <div class="container">
        <div class="row content-reverse">
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="account-card">
                            <h4 class="account-title"><?=__('Danh sách sản phẩm yêu thích');?></h4>
                            <form action="<?=base_url();?>" method="GET">
                                <input type="hidden" name="action" value="favorites">
                                <div class="top-filter">
                                    <div class="filter-show"><label class="filter-label">Show :</label>
                                        <select name="limit" onchange="this.form.submit()"
                                            class="form-select filter-select">
                                            <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                            <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                            <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                            <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                            <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                            <option <?=$limit == 500 ? 'selected' : '';?> value="500">500</option>
                                            <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000
                                            </option>
                                        </select>
                                    </div>
                                    <div class="filter-short">
                                        <label class="filter-label"><?=__('Short by Date:');?></label>
                                        <select name="shortByDate" onchange="this.form.submit()"
                                            class="form-select filter-select">
                                            <option value=""><?=__('Tất cả');?></option>
                                            <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1">
                                                <?=__('Hôm nay');?>
                                            </option>
                                            <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2">
                                                <?=__('Tuần này');?>
                                            </option>
                                            <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3">
                                                <?=__('Tháng này');?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                            <div class="table-scroll">
                                <table class="table fs-sm text-nowrap mb-0">
                                    <thead>
                                        <tr>
                                            <th><?=__('Sản phẩm');?></th>
                                            <th class="text-center"><?=__('Kho hàng');?></th>
                                            <th class="text-center"><?=__('Giá');?></th>
                                            <th class="text-center"><?=__('Thời gian');?></th>
                                            <th><?=__('Thao tác');?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listDatatable as $favorite) {?>
                                        <?php
                                            // ✅ Sử dụng prepared statement an toàn
                                            $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ?", [$favorite['product_id']]);
                                            // sản phẩm nào bị xóa thì bypass
                                            if(!$product){
                                                continue;
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <h6 class="feature-name"><a href="<?=base_url('product/'.$product['slug']);?>"><?=$product['name'];?></a>
                                                </h6>
                                            </td>
                                            <td class="text-center">
                                                <label class="label-text feat">
                                                    <?php 
                                                    $stock = $product['supplier_id'] != 0 ? $product['api_stock'] : getStock($product['code']);?>
                                                    <b><?=format_cash($stock);?></b></label>
                                            </td>
                                            <td class="text-right">
                                                <b
                                                    style="color:red;"><?=format_currency($product['price']);?></b>
                                            </td>
                                            <td class="text-center"><?=$favorite['create_gettime'];?></td>
                                            <td>
                                                <a class="btn btn-sm btn-primary" onclick="openModal(`<?=isset($getUser) ? $getUser['token'] : NULL;?>`, `<?=$favorite['product_id'];?>`)" type="button"><i class="fa-solid fa-cart-shopping"></i> <?=__('Mua');?></a>
                                                <a class="btn btn-sm btn-success" href="<?=base_url('product/'.$product['slug']);?>" type="button"><i
                                                        class="fas fa-eye"></i> <?=__('Xem');?></a>
                                                <a class="btn btn-sm btn-danger" onclick="remove(`<?=$favorite['id'];?>`)"
                                                    type="button" title="Remove Wishlist"><i class="icofont-trash"></i>
                                                    <?=__('Xóa');?></a>
                                            </td>
                                        </tr>
                                        <?php }?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if($totalDatatable == 0):?>
                            <div class="empty-state">
                                <svg width="184" height="152" viewBox="0 0 184 152" xmlns="http://www.w3.org/2000/svg">
                                    <g fill="none" fill-rule="evenodd">
                                        <g transform="translate(24 31.67)">
                                            <ellipse fill-opacity=".8" fill="#F5F5F7" cx="67.797" cy="106.89"
                                                rx="67.797" ry="12.668"></ellipse>
                                            <path
                                                d="M122.034 69.674L98.109 40.229c-1.148-1.386-2.826-2.225-4.593-2.225h-51.44c-1.766 0-3.444.839-4.592 2.225L13.56 69.674v15.383h108.475V69.674z"
                                                fill="#AEB8C2"></path>
                                            <path
                                                d="M101.537 86.214L80.63 61.102c-1.001-1.207-2.507-1.867-4.048-1.867H31.724c-1.54 0-3.047.66-4.048 1.867L6.769 86.214v13.792h94.768V86.214z"
                                                fill="url(#linearGradient-1)" transform="translate(13.56)"></path>
                                            <path
                                                d="M33.83 0h67.933a4 4 0 0 1 4 4v93.344a4 4 0 0 1-4 4H33.83a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4z"
                                                fill="#F5F5F7"></path>
                                            <path
                                                d="M42.678 9.953h50.237a2 2 0 0 1 2 2V36.91a2 2 0 0 1-2 2H42.678a2 2 0 0 1-2-2V11.953a2 2 0 0 1 2-2zM42.94 49.767h49.713a2.262 2.262 0 1 1 0 4.524H42.94a2.262 2.262 0 0 1 0-4.524zM42.94 61.53h49.713a2.262 2.262 0 1 1 0 4.525H42.94a2.262 2.262 0 0 1 0-4.525zM121.813 105.032c-.775 3.071-3.497 5.36-6.735 5.36H20.515c-3.238 0-5.96-2.29-6.734-5.36a7.309 7.309 0 0 1-.222-1.79V69.675h26.318c2.907 0 5.25 2.448 5.25 5.42v.04c0 2.971 2.37 5.37 5.277 5.37h34.785c2.907 0 5.277-2.421 5.277-5.393V75.1c0-2.972 2.343-5.426 5.25-5.426h26.318v33.569c0 .617-.077 1.216-.221 1.789z"
                                                fill="#DCE0E6"></path>
                                        </g>
                                        <path
                                            d="M149.121 33.292l-6.83 2.65a1 1 0 0 1-1.317-1.23l1.937-6.207c-2.589-2.944-4.109-6.534-4.109-10.408C138.802 8.102 148.92 0 161.402 0 173.881 0 184 8.102 184 18.097c0 9.995-10.118 18.097-22.599 18.097-4.528 0-8.744-1.066-12.28-2.902z"
                                            fill="#DCE0E6"></path>
                                        <g transform="translate(149.65 15.383)" fill="#FFF">
                                            <ellipse cx="20.654" cy="3.167" rx="2.849" ry="2.815"></ellipse>
                                            <path d="M5.698 5.63H0L2.898.704zM9.259.704h4.985V5.63H9.259z"></path>
                                        </g>
                                    </g>
                                </svg>
                                <p><?=__('Không có dữ liệu');?></p>
                            </div>
                            <?php endif?>
                            <div class="bottom-paginate">
                                <p class="page-info">Showing <?=$limit;?> of <?=$totalDatatable;?> Results</p>
                                <div class="pagination">
                                    <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if($CMSNT->site('menu_category_right') != 0):?>
<ul id="top-menu-<?=$CMSNT->site('menu_category_right') == 1 ? 'right' : 'left';?>">
    <li>
        <a class="menu-item" id="toggle-menu-button">
            <i class="fa-solid fa-eye-slash"></i>
            <span><?=__('Tạm ẩn trong 24 giờ');?></span></a>
    </li>
    <li>
        <a class="menu-item active" href="<?=base_url('client/favorites');?>">
            <i class="fa-solid fa-heart" style="color:red;"></i></i>
            <span><?=__('Sản phẩm yêu thích');?></span></a>
    </li>
    <?php foreach($CMSNT->get_list_safe(" SELECT * FROM `categories` WHERE `status` = ? AND `parent_id` != ? ", [1, 0]) as $category):?>
    <li>
        <a class="menu-item <?=$category_id == $category['id'] ? 'active' : '';?>"
            href="<?=base_url('category/'.$category['slug']);?>"><i>
                <img alt="<?=$category['name'];?>" src="<?=base_url($category['icon']);?>"></i> <span><?=$category['name'];?></span></a>
    </li>
    <?php endforeach?>
</ul>
<script>
// JavaScript để ẩn menu trong 24 giờ khi click vào nút
document.addEventListener('DOMContentLoaded', function() {
    const toggleMenuButton = document.getElementById('toggle-menu-button');
    const topMenu = document.getElementById('top-menu-<?=$CMSNT->site('menu_category_right') == 1 ? 'right' : 'left';?>');
    const menuHiddenTime = localStorage.getItem('menuHiddenTime');
    
    // Kiểm tra nếu menu đã được ẩn và chưa quá 24 giờ
    if (menuHiddenTime && (Date.now() - parseInt(menuHiddenTime) < 24 * 60 * 60 * 1000)) {
        topMenu.classList.add('hidden');
    }
    
    // Xử lý khi click vào nút ẩn menu
    toggleMenuButton.addEventListener('click', function() {
        if (topMenu.classList.contains('hidden')) {
            // Nếu đang ẩn thì hiện lại và xóa thời gian lưu
            topMenu.classList.remove('hidden');
            localStorage.removeItem('menuHiddenTime');
        } else {
            // Nếu đang hiện thì ẩn đi và lưu thời gian
            topMenu.classList.add('hidden');
            localStorage.setItem('menuHiddenTime', Date.now());
        }
    });
});
</script>
<?php endif?>
<script>
function remove(id) {
    Swal.fire({
        title: "<?=__('Bạn có chắc không?');?>",
        text: "<?=__('Hệ thống sẽ thực hiện xóa dữ liệu này nếu bạn nhấn đồng ý');?>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Hủy');?>"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "<?=BASE_URL("ajaxs/client/remove.php");?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    id: id,
                    token: '<?=$getUser['token'];?>',
                    action: 'removeFavorite'
                },
                success: function(respone) {
                    if (respone.status == 'success') {
                        Swal.fire({
                            title: "<?=__('Thành công!');?>",
                            text: respone.msg,
                            icon: "success"
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            title: "<?=__('Thất bại!');?>",
                            text: respone.msg,
                            icon: "error"
                        });
                    }
                },
                error: function() {
                    alert(html(response));
                    location.reload();
                }
            });
        }
    });
}
</script>

<div class="modal fade" id="openModal" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-popout" role="document">
        <div class="modal-content">
            <div id="modalContent"></div>
        </div>
    </div>
</div>

<script>
function openModal(token, id) {
    $("#modalContent").html('');
    const url = `<?=BASE_URL('ajaxs/client/modal/view-product.php?id=${id}&token=${token}');?>`;
    const buttons = $(".btn-buy");
    // Thay đổi nội dung và vô hiệu hóa tất cả các button
    buttons.each(function() {
        const button = $(this);
        button.html('<i class="fa fa-spinner fa-spin"></i>');
        button.prop('disabled', true);
    });
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`AJAX request failed with status: ${response.status}`);
            }
            return response.text();
        })
        .then(data => {
            $("#modalContent").html(data);
            $('#openModal').modal('show');
        })
        .catch(error => {
            showMessage('AJAX request failed: ' + error.message, 'error');
        });
    // Khi yêu cầu hoàn tất, khôi phục lại nội dung và kích hoạt lại tất cả các button
    buttons.each(function() {
        const button = $(this);
        button.html('<i class="fa-solid fa-cart-shopping"></i> <?=__('MUA NGAY');?>');
        button.prop('disabled', false);
    });
}
</script>


<?php
require_once(__DIR__.'/footer.php');
?>