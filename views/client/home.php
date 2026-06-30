<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
';
$body['footer'] = '
 
';

if($CMSNT->site('isLoginRequiredToViewProduct') == 1) {
    require_once(__DIR__ . '/../../models/is_user.php');
}else{
    if (isSecureCookie('user_login') == true) {
        require_once(__DIR__ . '/../../models/is_user.php');
    }
}

// Kiểm tra và redirect đến trang VAT invoice nếu popup_vat được bật và user chưa có thông tin VAT
if($CMSNT->site('popup_vat') == 1 && isset($getUser)) {
    // Kiểm tra xem user đã nhập thông tin VAT chưa
    $has_vat_info = false;
    try {
        $vat_info_json = $CMSNT->get_row_safe("SELECT vat_info FROM `users` WHERE `id` = ?", [$getUser['id']]);
        if($vat_info_json && !empty($vat_info_json['vat_info'])) {
            $vat_info = json_decode($vat_info_json['vat_info'], true);
            if($vat_info && !empty($vat_info['vat_type']) && !empty($vat_info['vat_name'])) {
                $has_vat_info = true;
            }
        }
    } catch (Exception $e) {
        $has_vat_info = false;
    }
    
    // Nếu user chưa có thông tin VAT, redirect đến trang cấu hình
    if(!$has_vat_info) {
        redirect(base_url('client/vat-invoice'));
    }
}

// ✅ An toàn cho slug parameter
$category_id = '';
$category_slug = '';
if (isset($_GET['slug'])) {
    $slug = validate_slug($_GET['slug'], 255);
    if($slug !== false) {
        if (!$category = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `slug` = ? AND `status` = ?", [$slug, 1])) {
            $category_id = '';
            $category_slug = '';
        }else{
            $category_id = $category['id'];
            $category_slug = $category['slug'];
            $body['title']  = $category['name'].' | '.$CMSNT->site('title');
            $body['desc']   = $category['description'];
        }
    }
}

// ✅ An toàn cho category filtering
if(!empty($_GET['category'])){
    $category_id = validate_int($_GET['category'], 1);
    // Lấy slug từ cache nếu có category_id
    if($category_id) {
        $all_cats = get_categories_not_parent_cached();
        foreach($all_cats as $cat) {
            if($cat['id'] == $category_id) {
                $category_slug = $cat['slug'];
                break;
            }
        }
    }
}

// ✅ An toàn cho keyword search
$keyword = '';
if(!empty($_GET['keyword'])){
    $keyword = validate_string($_GET['keyword'], 255, 2);
}

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
?>

<section class="section feature-part">
    <div class="container">
        <div class="mb-5">

        </div>
        <div class="row mb-5">
            <?php if($CMSNT->site('notice_home') != ''):?>
            <div class="col-md-12">
                <div class="account-card pt-3">
                    <?=$CMSNT->site('notice_home');?>
                </div>
            </div>
            <?php endif?>

            <?php require_once(__DIR__.'/widget_tools.php');?>
            <br>



            <div class="<?=$CMSNT->site('cot_so_du_ben_phai') == 1 ? 'col-xl-9' : 'col-xl-12';?>">
                <?php if($CMSNT->site('show_btn_category_home') == 1):?>
                <!-- Container để load category buttons bằng AJAX -->
                <ul class="custom-button-list" id="home-categories-container">
                    <li><a class="btn-category-home <?=$category_id == '' ? 'active' : '';?>" 
                            href="javascript:void(0);" 
                            onclick="loadProductsByCategory('', '')"
                            data-category-id=""
                            data-category-slug=""><i
                                class="fa-solid fa-cart-shopping me-2"></i><?=__('Tất cả sản phẩm');?></a>
                    </li>
                    <!-- Skeleton loading cho categories -->
                    <li class="home-categories-skeleton">
                        <div class="skeleton-category-btn"></div>
                    </li>
                    <li class="home-categories-skeleton">
                        <div class="skeleton-category-btn"></div>
                    </li>
                    <li class="home-categories-skeleton">
                        <div class="skeleton-category-btn"></div>
                    </li>
                    <li class="home-categories-skeleton">
                        <div class="skeleton-category-btn"></div>
                    </li>
                </ul>
                <?php endif?>
                
                <!-- Container để load sản phẩm bằng AJAX -->
                <div id="products-container">
                    <div class="skeleton-container">
                        <div class="skeleton-card">
                            <div class="skeleton-image"></div>
                            <div class="skeleton-content">
                                <div class="skeleton-title"></div>
                                <div class="skeleton-text"></div>
                                <div class="skeleton-info">
                                    <div class="skeleton-stock"></div>
                                    <div class="skeleton-sales"></div>
                                </div>
                            </div>
                            <div class="skeleton-right">
                                <div class="skeleton-price"></div>
                                <div class="skeleton-button"></div>
                            </div>
                        </div>
                        <div class="skeleton-card">
                            <div class="skeleton-image"></div>
                            <div class="skeleton-content">
                                <div class="skeleton-title"></div>
                                <div class="skeleton-text"></div>
                                <div class="skeleton-info">
                                    <div class="skeleton-stock"></div>
                                    <div class="skeleton-sales"></div>
                                </div>
                            </div>
                            <div class="skeleton-right">
                                <div class="skeleton-price"></div>
                                <div class="skeleton-button"></div>
                            </div>
                        </div>
                    </div>
                    <div class="loading-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="loading-text"><?=__('Đang tải sản phẩm...');?></div>
                </div>
                
            </div>
            <?php if($CMSNT->site('cot_so_du_ben_phai') == 1):?>
            <div class="col-xl-3">
                <div class="account-card card-wallet-home py-4">
                    <?php if(isset($getUser)):?>
                    <div class="my-wallet">
                        <p><?=__('Số dư hiện tại');?></p>
                        <h3><?=format_currency($getUser['money']);?></h3>
                    </div>
                    <div class="wallet-card-group">
                        <div class="wallet-card">
                            <p><?=__('Tổng tiền nạp');?></p>
                            <h3><?=format_currency($getUser['total_money']);?></h3>
                        </div>
                        <div class="wallet-card">
                            <p><?=__('Số dư đã sử dụng');?></p>
                            <h3><?=format_currency($getUser['total_money'] - $getUser['money']);?></h3>
                        </div>
                        <div class="wallet-card">
                            <p><?=__('Giảm giá');?></p>
                            <h3><?=$getUser['discount'];?>%</h3>
                        </div>
                    </div>
                    <?php else:?>
                    <ul class="user-form-social">
                        <li><a href="<?=base_url('client/login');?>" class="facebook"><i
                                    class="fa-solid fa-right-to-bracket"></i> <?=mb_strtoupper(__('Đăng nhập'));?></a>
                        </li>
                        <li><a href="<?=base_url('client/register');?>" class="google"><i
                                    class="fa-solid fa-user-plus"></i> <?=mb_strtoupper(__('Đăng ký tài khoản'));?></a>
                        </li>
                    </ul>
                    <?php endif?>
                </div>
            </div>
            <?php endif?>
        </div>
        <?php if($CMSNT->site('status_giao_dich_gan_day') == 1):?>
        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-cart-shopping m-2"></i> <?=mb_strtoupper(__('Đơn hàng gần đây'));?>
                    </h3>
                </div>
                <div style="height:350px;overflow-x:hidden;overflow-y:auto;">
                    <?php 
                    // ⚡ Tối ưu: Sử dụng JOIN thay vì query trong vòng lặp (tránh N+1 problem)
                    $recent_orders = $CMSNT->get_list_safe("
                        SELECT o.*, u.username 
                        FROM `order_log` o 
                        LEFT JOIN `users` u ON o.buyer = u.id 
                        ORDER BY o.id DESC 
                        LIMIT 20
                    ", []);
                    
                    foreach ($recent_orders as $log_order):
                    ?>
                    <div class="feature-card">
                        <div class="feature-content">
                            <div class="row">
                                <div class="col-10 col-md-10">
                                    <?php
                                    $content = $CMSNT->site('content_gd_mua_gan_day');
                                    $content = str_replace('{username}', mb_substr($log_order['username'] ?? '***', -3, 3), $content);
                                    $content = str_replace('{amount}', format_cash($log_order['amount']), $content);
                                    $content = str_replace('{product_name}', mb_substr($log_order['product_name'], 0, 30).'...', $content);
                                    $content_gd_mua_gan_day = str_replace('{price}', format_currency($log_order['pay']), $content); 
                                    ?>
                                    <?=$content_gd_mua_gan_day;?>
                                </div>
                                <div class="col-2 col-md-2">
                                    <span class="badge bg-primary"><?= timeAgo($log_order['create_time']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach?>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-credit-card m-2"></i> <?=mb_strtoupper(__('Nạp tiền gần đây'));?>
                    </h3>
                </div>
                <div style="height:350px;overflow-x:hidden;overflow-y:auto;">
                    <?php 
                    // ⚡ Tối ưu: Sử dụng JOIN thay vì query trong vòng lặp (tránh N+1 problem)
                    $recent_deposits = $CMSNT->get_list_safe("
                        SELECT d.*, u.username 
                        FROM `deposit_log` d 
                        LEFT JOIN `users` u ON d.user_id = u.id 
                        ORDER BY d.id DESC 
                        LIMIT 20
                    ", []);
                    
                    foreach ($recent_deposits as $log_payment):
                    ?>
                    <div class="feature-card">
                        <div class="feature-content">
                            <div class="row">
                                <div class="col-9 col-md-10">
                                    <?php
                                    $content = $CMSNT->site('content_gd_nap_tien_gan_day');
                                    $content = str_replace('{username}', mb_substr($log_payment['username'] ?? '***', -3, 3), $content);
                                    $content = str_replace('{amount}', format_currency($log_payment['amount']), $content);
                                    $content = str_replace('{method}', mb_substr($log_payment['method'], 0, 45), $content);
                                    $content_gd_nap_tien_gan_day = str_replace('{received}', format_currency($log_payment['received']), $content); 
                                    ?>
                                    <?=$content_gd_nap_tien_gan_day;?>
                                </div>
                                <div class="col-3 col-md-2">
                                    <span class="badge bg-primary"><?= timeAgo($log_payment['create_time']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach?>
                </div>
            </div>
        </div>
        <?php endif?>


    </div>
</section>

<script defer>
// Biến global để lưu category hiện tại
let currentCategoryId = '<?=htmlspecialchars($category_id, ENT_QUOTES, 'UTF-8');?>';
let currentCategorySlug = '<?=htmlspecialchars($category_slug ?? '', ENT_QUOTES, 'UTF-8');?>';
let currentKeyword = '<?=htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');?>';

// Hàm tạo skeleton loading HTML
function getSkeletonLoadingHTML() {
    return `
        <div class="skeleton-container">
            ${Array(2).fill(0).map(() => `
                <div class="skeleton-card">
                    <div class="skeleton-image"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-title"></div>
                        <div class="skeleton-text"></div>
                        <div class="skeleton-info">
                            <div class="skeleton-stock"></div>
                            <div class="skeleton-sales"></div>
                        </div>
                    </div>
                    <div class="skeleton-right">
                        <div class="skeleton-price"></div>
                        <div class="skeleton-button"></div>
                    </div>
                </div>
            `).join('')}
        </div>
        <div class="loading-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="loading-text"><?=__('Đang tải sản phẩm...');?></div>
    `;
}

// Hàm load sản phẩm theo category
function loadProductsByCategory(categoryId, categorySlug) {
    currentCategoryId = categoryId;
    currentCategorySlug = categorySlug || '';
    currentKeyword = ''; // Clear keyword khi chọn category
    
    // Cập nhật active class cho button categories
    $('.btn-category-home').removeClass('active');
    if(event && event.target) {
        $(event.target).closest('a').addClass('active');
    }
    
    // Cập nhật active class cho menu items
    $('.menu-item').removeClass('active');
    if(event && event.target) {
        $(event.target).closest('a').addClass('active');
    }
    
    // 🔄 Thay đổi URL trong thanh địa chỉ (không reload trang)
    let newUrl = '<?=base_url();?>';
    let pageTitle = <?=json_encode($CMSNT->site('title'));?>;
    
    if(categorySlug) {
        newUrl = '<?=base_url();?>category/' + categorySlug;
        // Cập nhật title của trang
        let categoryName = $(event.target).closest('a').find('span').text() || $(event.target).text();
        pageTitle = categoryName.trim() + ' | ' + <?=json_encode($CMSNT->site('title'));?>;
    }
    
    document.title = pageTitle;
    window.history.pushState({categoryId: categoryId, categorySlug: categorySlug, title: pageTitle}, pageTitle, newUrl);
    
    // Hiển thị skeleton loading
    $('#products-container').html(getSkeletonLoadingHTML());
    
    // Scroll lên phần sản phẩm (nhanh hơn)
    $('html, body').animate({
        scrollTop: $('#products-container').offset().top - 110
    }, 50);
    
    // Load sản phẩm
    loadProducts();
}

// Hàm load sản phẩm
function loadProducts() {
    let params = {
        type: 'categories'
    };
    
    // Lấy các tham số từ URL
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    const limit = urlParams.get('limit');
    
    if(currentCategoryId) {
        params.category_id = currentCategoryId;
    }
    
    if(currentKeyword) {
        params.type = 'search';
        params.keyword = currentKeyword;
    }
    
    // Thêm page và limit nếu có trong URL
    if(page) {
        params.page = page;
    }
    if(limit) {
        params.limit = limit;
    }
    
    $.ajax({
        url: '<?=base_url('ajaxs/client/load_products.php');?>',
        type: 'GET',
        data: params,
        success: function(response) {
            $('#products-container').html('<div class="row">' + response + '</div>');
        },
        error: function(xhr, status, error) {
            $('#products-container').html(`
                <div class="alert alert-danger" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <?=__('Không thể tải sản phẩm. Vui lòng thử lại!');?>
                </div>
            `);
            console.error('Error loading products:', error);
        }
    });
}

// Load sản phẩm khi trang load xong
$(document).ready(function() {
    // 🔄 Set initial state cho history
    window.history.replaceState({
        categoryId: currentCategoryId, 
        categorySlug: currentCategorySlug,
        title: document.title
    }, document.title, window.location.href);
    
    // Nếu có keyword từ URL thì load search results
    const urlParams = new URLSearchParams(window.location.search);
    const keyword = urlParams.get('keyword');
    
    if(keyword) {
        currentKeyword = keyword;
        loadProducts();
    } else {
        // Load sản phẩm theo categories
        loadProducts();
    }
});

// 🔄 Xử lý khi user nhấn nút Back/Forward của browser
window.addEventListener('popstate', function(event) {
    if(event.state) {
        currentCategoryId = event.state.categoryId || '';
        currentCategorySlug = event.state.categorySlug || '';
        currentKeyword = '';
        
        // Cập nhật title
        if(event.state.title) {
            document.title = event.state.title;
        }
        
        // Cập nhật active class
        $('.btn-category-home').removeClass('active');
        $('.menu-item').removeClass('active');
        
        // Tìm và active button/menu tương ứng
        if(currentCategoryId) {
            $('[data-category-id="' + currentCategoryId + '"]').addClass('active');
        } else {
            $('[data-category-id=""]').addClass('active');
        }
        
        // Load lại sản phẩm
        loadProducts();
    }
});
</script>

<div class="modal fade" id="openModal" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div id="modalContent"></div>
        </div>
    </div>
</div>

<script>
function openModal(token, id, previewUid) {
    $("#modalContent").html('');
    var originalButtonContent = $('#openModal_' + id).html();
    $('#openModal_' + id).html('<span><i class="fa fa-spinner fa-spin"></i> <?=__('Processing...');?></span>')
        .prop('disabled',
            true);
    // Điều chỉnh kích thước modal
    var modalDialog = $('#openModal .modal-dialog');
    if (previewUid == 1) {
        modalDialog.removeClass('modal-lg').addClass('modal-xl');
    } else {
        modalDialog.removeClass('modal-xl').addClass('modal-lg');
    }
    var modalUrl = previewUid == 1 
        ? "<?=BASE_URL('ajaxs/client/modal/view-product-preview.php');?>" 
        : "<?=BASE_URL('ajaxs/client/modal/view-product.php');?>";
    $.ajax({
        url: modalUrl,
        method: "GET",
        data: {
            id: id,
            token: token
        },
        success: function(data) {
            $("#modalContent").html(data);
            $('#openModal').modal('show');
            $('#openModal_' + id).html(originalButtonContent).prop('disabled', false);
        },
        error: function() {
            Swal.fire('<?=__('Thất bại!');?>', data, 'error');
        }
    });
}
</script>





<?php if($CMSNT->site('menu_category_right') != 0):?>
<ul id="top-menu-<?=$CMSNT->site('menu_category_right') == 1 ? 'right' : 'left';?>">
    <li>
        <a class="menu-item" id="toggle-menu-button">
            <i class="fa-solid fa-eye-slash"></i>
            <span><?=__('Tạm ẩn trong 24 giờ');?></span></a>
    </li>
    <li>
        <a class="menu-item" href="<?=base_url('client/favorites');?>">
            <i class="fa-solid fa-heart" style="color:red;"></i></i>
            <span><?=__('Sản phẩm yêu thích');?></span></a>
    </li>
    <?php 
// ⚡ Sử dụng cache function (đã load ở trên)
if(!isset($all_categories)) {
    $all_categories = get_categories_not_parent_cached();
}
foreach($all_categories as $category):
?>
    <li>
        <a class="menu-item <?=$category_id == $category['id'] ? 'active' : '';?>"
            href="javascript:void(0);" 
            onclick="loadProductsByCategory('<?=htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8');?>', '<?=htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8');?>')"
            data-category-id="<?=htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8');?>"
            data-category-slug="<?=htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8');?>"><i>
                <img  src="<?=base_url($category['icon']);?>"></i>
            <span><?=htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8');?></span></a>
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


<?php if($CMSNT->site('popup_status') == 1):?>
<div class="modal fade" id="modal_notification" tabindex="-1" role="dialog"  aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="exampleModalLabel1"><i class="fa-solid fa-bell"></i> <?=__('Thông Báo');?>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?=$CMSNT->site('popup_noti');?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger"
                    id="dontShowAgainBtn"><?=__('Không hiển thị lại trong 2 giờ');?></button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var modal = document.getElementById('modal_notification');
    var dontShowAgainBtn = document.getElementById('dontShowAgainBtn');
    var modalClosedTime = localStorage.getItem('modalClosedTime');

    // Nếu modalClosedTime chưa được lưu hoặc đã quá 2 giờ, hiển thị modal
    if (!modalClosedTime || (Date.now() - parseInt(modalClosedTime) > 2 * 60 * 60 * 1000)) {
        var bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    }

    // Lưu thời gian khi modal được đóng khi người dùng click vào nút "Không hiển thị lại" và ẩn modal
    dontShowAgainBtn.addEventListener('click', function() {
        localStorage.setItem('modalClosedTime', Date.now());
        var bootstrapModal = bootstrap.Modal.getInstance(modal);
        bootstrapModal.hide();
    });
});
</script>
<?php endif?>



<?php
require_once(__DIR__.'/footer.php');
?>
