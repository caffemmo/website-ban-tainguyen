<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'CTV Config',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
';
$body['footer'] = '
<!-- ckeditor -->
<script src="'.BASE_URL('public/ckeditor/ckeditor.js').'"></script>
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
// Kiểm tra giấy phép addon
$checkKey = checkAddonLicense($CMSNT->site('ctv_panel_license'), 'SHOPCLONE7_CTVPANEL');

// Kiểm tra quyền
if(checkPermission($getUser['admin'], 'edit_config_ctv') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back().location.reload();}</script>');
}

// Lưu cấu hình
if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình CTV Panel')
    ]);
    // Chỉ cập nhật các key liên quan đến CTV
    $allowedKeys = ['ctv_status','ctv_min_withdraw','ctv_fee_withdraw','ctv_prefix_withdraw','ctv_banks_withdraw','ctv_notice_withdraw','ctv_notice','ctv_panel_license'];
    foreach ($allowedKeys as $key) {
        if (isset($_POST[$key])) {
            $CMSNT->update("settings", [
                'value' => $_POST[$key]
            ], " `name` = '$key' ");
        }
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình CTV Panel'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);    
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("'.__('Lưu thành công!').'")){location.href=`' . base_url_admin('ctv-config') . '`;}</script>');
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Cấu hình CTV Panel</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">CTV Panel</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cấu hình</li>
                    </ol>
                </nav>
            </div>
        </div>
        <?php if(!column_exists('products', 'pending')):?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm mb-3"
                    role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Cột <strong>pending</strong> không tồn tại trong bảng <strong>products</strong>.
                    <br><small class="text-muted">Có thể bạn đang sử dụng phiên bản cũ của SHOPCLONE7. Vui lòng cập nhật lên phiên bản mới nhất hoặc thêm cột pending vào bảng products để sử dụng đầy đủ tính năng.</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            </div>
        </div>
        <?php endif?>
        <?php if(!function_exists('finfo_open')):?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm mb-3"
                    role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    PHP Extension <strong>fileinfo</strong> chưa được cài đặt trên máy chủ của bạn.
                    <br><small class="text-muted">Extension này là bắt buộc để kiểm tra tính hợp lệ của file ảnh trong trang tải lên sản phẩm của CTV. Vui lòng cài đặt theo hướng dẫn:
                    <br>- <strong>Shared Hosting/cPanel:</strong> Vào cPanel > Select PHP Version > Tích chọn "fileinfo" > Save
                    <br>- <strong>VPS/Server:</strong> Chạy lệnh <code>sudo apt-get install php7.4-fileinfo</code> (với PHP 7.4) hoặc tương ứng với phiên bản PHP của bạn, sau đó khởi động lại web server</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            </div>
        </div>
        <?php endif?>
        
        <?php if($CMSNT->site('cong_tien_nguoi_ban') != 1):?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm mb-3"
                    role="alert">
                    <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                        width="1.5rem" fill="#000000">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                    </svg>
                    Bạn cần bật tính năng "Cộng tiền người bán" trong cài đặt hệ thống trước khi có thể sử dụng CTV
                    Panel.
                    <br><small class="text-muted">Vui lòng vào <strong>Cài đặt → Thiết lập chung</strong> và bật tùy
                        chọn "Cộng tiền người bán" để kích hoạt CTV Panel.</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                            class="bi bi-x"></i></button>
                </div>
            </div>
        </div>
        <?php endif?>

        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-body">
                        <form action="" method="post">
                            <?php if($checkKey['status'] != true):?>
                            <div class="mb-3">
                                <label class="form-label">Giấy phép kích hoạt Addon CTV Panel</label>
                                <input type="text" class="form-control" name="ctv_panel_license" 
                                    placeholder="921abf4dbff01xxxxxf3c562c356c769" 
                                    value="<?=$CMSNT->site('ctv_panel_license');?>">
                                <div class="mt-2 p-3 bg-warning-subtle border border-warning-subtle rounded-3 text-warning-emphasis">
                                    <strong>Chú ý:</strong> Bạn cần phải mua giấy phép kích hoạt <a
                                        target="_blank" class="text-primary"
                                        href="https://client.cmsnt.co/store/license-source-code/addon-ctv-panel-shopclone-v7">Addon CTV Panel</a> trước
                                    khi sử dụng. Truy cập Admin/Cài Đặt/Addons để mua giấy phép hoặc nhấn vào <a
                                        target="_blank" class="text-primary"
                                        href="https://client.cmsnt.co/store/license-source-code/addon-ctv-panel-shopclone-v7">đây</a>.
                                </div>
                            </div>
                            <?php else:?>
                            <div class="mb-3">
                                <label class="form-label">Trạng thái CTV Panel</label>
                                <select class="form-control" name="ctv_status">
                                    <option <?=$CMSNT->site('ctv_status') == 1 ? 'selected' : '';?> value="1">
                                        <i class="fas fa-check-circle text-success"></i> ON - Kích hoạt
                                    </option>
                                    <option <?=$CMSNT->site('ctv_status') == 0 ? 'selected' : '';?> value="0">
                                        <i class="fas fa-times-circle text-danger"></i> OFF - Tắt
                                    </option>
                                </select>
                                <small class="text-muted">Bật/tắt toàn bộ tính năng CTV Panel.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tiền rút tối thiểu (<?=currencyDefault();?>)</label>
                                <input type="number" min="0" step="1000" class="form-control" name="ctv_min_withdraw"
                                    value="<?=$CMSNT->site('ctv_min_withdraw');?>" placeholder="Nhập số tiền tối thiểu">
                                <small class="text-muted">Ví dụ: 100000 tương đương <?=format_currency(100000);?>.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phí rút tiền (%)</label>
                                <input type="number" min="0" max="100" step="0.1" class="form-control" name="ctv_fee_withdraw"
                                    value="<?=$CMSNT->site('ctv_fee_withdraw');?>" placeholder="Nhập phần trăm phí rút tiền">
                                <small class="text-muted">Ví dụ: 2.5 = 2.5% phí rút tiền. Để 0 nếu không tính phí.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tiền tố mã giao dịch rút</label>
                                <input type="text" class="form-control" name="ctv_prefix_withdraw"
                                    value="<?=$CMSNT->site('ctv_prefix_withdraw');?>" placeholder="Ví dụ: CTV">
                                <small class="text-muted">Tiền tố sẽ gắn trước mã giao dịch, ví dụ:
                                    CTV1699999999.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Danh sách ngân hàng hỗ trợ rút</label>
                                <textarea class="form-control" name="ctv_banks_withdraw" rows="8"
                                    placeholder="Mỗi dòng 1 ngân hàng, có thể nhập tên viết tắt ngân hàng hoặc USDT."><?=$CMSNT->site('ctv_banks_withdraw');?></textarea>
                                <small class="text-muted">Ví dụ: USDT, VCB, MBBank, ACB... Mỗi ngân hàng một
                                    dòng.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Thông báo hiển thị trên trang home CTV</label>
                                <textarea class="form-control" name="ctv_notice" rows="6"
                                    placeholder="Nội dung HTML được hỗ trợ."><?=$CMSNT->site('ctv_notice');?></textarea>
                                <small class="text-muted">Thông báo này sẽ hiển thị trên trang chủ của CTV
                                    Panel.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Thông báo hiển thị trên trang rút tiền CTV</label>
                                <textarea class="form-control" name="ctv_notice_withdraw" rows="6"
                                    placeholder="Nội dung HTML được hỗ trợ."><?=$CMSNT->site('ctv_notice_withdraw');?></textarea>
                                <small class="text-muted">Bạn có thể nhập HTML để định dạng (ví dụ: danh sách điều kiện,
                                    lưu ý...).</small>
                            </div>
                            <?php endif?>
                            <button type="submit" name="SaveSettings" class="btn btn-primary">
                                <i class="fa fa-fw fa-save me-1"></i> <?=__('Save');?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Thiết lập số tiền rút tối thiểu để kiểm soát chi phí xử lý.</li>
                            <li>Phí rút tiền tính theo % giúp bù đắp chi phí xử lý giao dịch.</li>
                            <li>Tiền tố mã giúp phân biệt giao dịch rút CTV với loại khác.</li>
                            <li>Danh sách ngân hàng chỉ nên bao gồm các kênh bạn hỗ trợ.</li>
                            <li>Thông báo trên trang home giúp CTV nắm rõ thông tin chung.</li>
                            <li>Thông báo trên trang rút tiền giúp CTV nắm rõ quy định.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<?php if($checkKey['status'] == true): ?>
<script>
CKEDITOR.replace("ctv_notice");
CKEDITOR.replace("ctv_notice_withdraw");
</script>
<?php endif; ?>