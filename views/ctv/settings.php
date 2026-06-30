<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Cấu hình CTV | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_ctv.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
?>
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-2 page-header-breadcrumb mb-3">
            <div>
                <h1 class="page-title fw-semibold fs-18 mb-0"><?= __('Cấu hình'); ?></h1>
                <p class="text-muted mb-0"><?= __('Quản lý cài đặt CTV của bạn'); ?></p>
            </div>
            <div class="btn-list mt-md-0 mt-2">
                <a href="<?= base_url_ctv('home'); ?>" class="btn btn-primary btn-sm">
                    <i class="ri-arrow-left-line me-1 align-bottom"></i><?= __('Quay lại'); ?>
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6 col-lg-8 col-md-12">
                <div class="card custom-card overflow-hidden border-0">
                    <div class="card-header border-bottom-0 pb-0">
                        <div class="card-title">
                            <i class="bx bxl-telegram text-primary fs-20 me-1"></i>
                            <?= __('Cài đặt thông báo Telegram'); ?>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <form id="form-settings">
                            <input type="hidden" name="action" value="update_settings">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">

                            <div class="mb-4">
                                <label for="telegram_chat_id" class="form-label fw-semibold text-dark">
                                    <?= __('Chat ID Telegram'); ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="fas fa-hashtag"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 ps-0" id="telegram_chat_id"
                                        name="telegram_chat_id"
                                        value="<?= htmlspecialchars($getUser['telegram_chat_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        placeholder="<?= __('Nhập Chat ID Telegram của bạn'); ?>">
                                </div>
                                <div class="form-text mt-2 text-muted">
                                    <i class="fas fa-info-circle me-1 text-info"></i>
                                    <?= __('Nhập Chat ID Telegram để nhận thông báo tự động khi có khách mua sản phẩm của bạn.'); ?>
                                    <br>
                                    <span class="ms-3"><?= __('Lấy ID bằng cách chat với bot'); ?>
                                        <a href="https://t.me/userinfobot" target="_blank" class="fw-semibold text-primary">@userinfobot</a>
                                        <?= __('trên Telegram.'); ?></span>
                                </div>
                            </div>

                            <button type="button" id="btn-save" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="bx bx-save me-1 fs-16"></i>
                                <?= __('Lưu thay đổi'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/footer.php'); ?>
<script>
    $(document).ready(function() {
        $('#btn-save').click(function(e) {
            e.preventDefault();
            var button = $(this);
            var originalText = button.html();

            button.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><?= __('Đang xử lý...'); ?>').prop('disabled', true);

            $.ajax({
                url: '<?= base_url('ajaxs/ctv/update.php'); ?>',
                type: 'POST',
                dataType: 'json',
                data: $('#form-settings').serialize(),
                success: function(response) {
                    button.html(originalText).prop('disabled', false);
                    if (response.status == 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '<?= __('Thành công!'); ?>',
                            text: response.msg,
                            showConfirmButton: false,
                            timer: 2000
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '<?= __('Thất bại!'); ?>',
                            text: response.msg
                        });
                    }
                },
                error: function(xhr, status, error) {
                    button.html(originalText).prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: '<?= __('Lỗi!'); ?>',
                        text: '<?= __('Không thể kết nối đến máy chủ: '); ?>' + error
                    });
                }
            });
        });
    });
</script>