<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Dashboard') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '
 
 
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/../../models/is_license.php');
?>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-2 page-header-breadcrumb">

        </div>
        <?php if (checkPermission($getUser['admin'], 'view_license') == true): ?>
            <?php
            $install_hidden_file = $CMSNT->site('install_file_name');
            $has_quick_fix = !empty($install_hidden_file) && file_exists(__DIR__ . '/../../' . $install_hidden_file);
            ?>
            <div class="alert alert-secondary alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-code-branch text-primary me-2"></i>
                        <h5 class="mb-0"><?= $config['project']; ?></h5>
                    </div>
                    <?php if ($has_quick_fix): ?>
                        <button type="button" class="btn btn-sm btn-info" id="btnQuickFix" onclick="runQuickFix()">
                            <i class="fas fa-wrench me-1"></i><?= __('Sửa lỗi nhanh'); ?>
                        </button>
                        <script>
                            function runQuickFix() {
                                Swal.fire({
                                    title: '<?= __('Xác nhận sửa lỗi nhanh'); ?>',
                                    text: '<?= __('Hệ thống sẽ chạy lại cập nhật cơ sở dữ liệu. Bạn có muốn tiếp tục?'); ?>',
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonColor: '#17a2b8',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: '<?= __('Thực hiện'); ?>',
                                    cancelButtonText: '<?= __('Hủy'); ?>'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const btn = document.getElementById('btnQuickFix');
                                        const originalText = btn.innerHTML;
                                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i><?= __('Đang xử lý...'); ?>';
                                        btn.disabled = true;

                                        $.ajax({
                                            url: '<?= BASE_URL($install_hidden_file); ?>',
                                            method: 'GET',
                                            success: function() {
                                                Swal.fire({
                                                    title: '<?= __('Thành công!'); ?>',
                                                    text: '<?= __('Đã chạy sửa lỗi nhanh thành công.'); ?>',
                                                    icon: 'success',
                                                    confirmButtonText: '<?= __('Đóng'); ?>'
                                                }).then(() => {
                                                    location.reload();
                                                });
                                            },
                                            error: function() {
                                                Swal.fire('<?= __('Lỗi!'); ?>', '<?= __('Có lỗi xảy ra.'); ?>', 'error');
                                                btn.innerHTML = originalText;
                                                btn.disabled = false;
                                            }
                                        });
                                    }
                                });
                            }
                        </script>
                    <?php endif; ?>
                    <a href="<?= base_url_admin('logs&user_id=&username=&content=' . urlencode('Cập nhật hệ thống từ phiên bản') . '&ip=&device=&createdate=&limit=20&shortByDate='); ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-history me-1"></i><?= __('Nhật ký update'); ?>
                    </a>
                </div>
                <?php if ($CMSNT->site('status_update') == 1): ?>
                    <div class="alert alert-light border-0 py-2">
                        <i class="fas fa-sync-alt text-success me-1"></i>
                        <small>Hệ thống sẽ tự động cập nhật phiên bản mới khi bạn truy cập trang này. Để tắt tính năng này, vào
                            <strong>Cài Đặt</strong> &rarr; <strong>Cài đặt chung</strong> &rarr; <strong>Cập nhật phiên bản tự động</strong> &rarr; <strong>OFF</strong>.</small>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border-0 py-2">
                        <i class="fas fa-sync-alt text-danger me-1"></i>
                        <small>Bạn đang tắt chức năng cập nhật phiên bản. Để bật tính năng này, vào
                            <strong>Cài Đặt</strong> &rarr; <strong>Cài đặt chung</strong> &rarr; <strong>Cập nhật phiên bản tự động</strong> &rarr; <strong>ON</strong>.</small>
                    </div>
                <?php endif ?>

                <div class="mt-3">
                    <!-- Thông báo quan trọng về việc ngừng kinh doanh mã nguồn -->
                    <div class="alert alert-warning border-warning mt-3" role="alert">
                        <h5 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>THÔNG BÁO QUAN TRỌNG</h5>
                        <hr>
                        <p>Do thời gian gần đây các mã nguồn do bên mình phát triển bị nhiều bên lợi dụng để phục vụ cho các hành vi vi phạm pháp luật Việt Nam gây ảnh hưởng nghiêm trọng đến bên mình, nên bên mình quyết định:</p>
                        <ul class="mb-3">
                            <li>Ngừng bán toàn bộ mã nguồn SHOPCLONE6, SHOPCLONE7</li>
                            <li>Ngừng hỗ trợ kỹ thuật và cập nhật trong tương lai đối với các sản phẩm trên</li>
                        </ul>
                        <p class="fw-bold">Đối với các khách hàng đã mua trước đây:</p>
                        <ul class="mb-3">
                            <li>Vẫn có thể tiếp tục sử dụng mã nguồn bình thường.</li>
                            <li>Tuy nhiên CMSNT sẽ không hỗ trợ kỹ thuật, vá lỗi hoặc cập nhật thêm trong tương lai.</li>
                        </ul>
                        <p>Cảm ơn quý khách hàng sử dụng nền tảng của CMSNT trong thời gian qua, hẹn gặp lại ở các dự án phù hợp hơn trong tương lai.</p>
                    </div>
                    <button class="btn btn-warning btn-sm ms-auto" id="hideAlert24h">
                        <i class="fas fa-eye-slash me-1"></i> Ẩn trong 24 giờ
                    </button>
                    <?php if ($CMSNT->site('status_demo') != 1): ?>
                        <script>
                            function copyLicenseKey() {
                                const licenseKey = '<?= $CMSNT->site('license_key'); ?>';
                                navigator.clipboard.writeText(licenseKey).then(function() {
                                    // Hiển thị thông báo thành công
                                    const button = document.getElementById('copyLicenseKey');
                                    const originalText = button.innerHTML;
                                    button.innerHTML = '<i class="fas fa-check me-1"></i> Đã sao chép';
                                    button.classList.remove('btn-info');
                                    button.classList.add('btn-success');

                                    // Khôi phục lại trạng thái ban đầu sau 2 giây
                                    setTimeout(function() {
                                        button.innerHTML = originalText;
                                        button.classList.remove('btn-success');
                                        button.classList.add('btn-info');
                                    }, 2000);
                                }).catch(function(err) {
                                    console.error('Không thể sao chép: ', err);
                                    alert('Không thể sao chép giấy phép. Vui lòng thử lại.');
                                });
                            }
                        </script>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <script>
                document.getElementById('hideAlert24h').addEventListener('click', function() {
                    // Lưu thời gian ẩn vào localStorage
                    localStorage.setItem('hideAlertUntil', Date.now() + 24 * 60 * 60 * 1000);
                    // Ẩn thông báo
                    this.closest('.alert').style.display = 'none';
                });

                // Kiểm tra xem thông báo có nên hiển thị không khi trang tải
                document.addEventListener('DOMContentLoaded', function() {
                    const hideUntil = localStorage.getItem('hideAlertUntil');
                    const alertElement = document.querySelector('.alert-secondary');

                    if (hideUntil && Date.now() < parseInt(hideUntil) && alertElement) {
                        alertElement.style.display = 'none';
                    }
                });
            </script>
        <?php endif ?>
        <?php if (file_exists(__DIR__ . '/../../installer.php')): ?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                </svg>
                <strong><?= __('CẢNH BÁO BẢO MẬT NGHIÊM TRỌNG!'); ?></strong> <?= __('Vui lòng xóa file'); ?> <b
                    style="color:red;">installer.php</b>
                <?= __('trong thư mục gốc ngay lập tức để bảo vệ bảo mật website trong môi trường'); ?> <b>PRODUCTION</b>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-light" onclick="deleteInstallerFile()">
                        <i class="ri-delete-bin-line me-1"></i><?= __('Xóa ngay'); ?>
                    </button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
            <script>
                function deleteInstallerFile() {
                    Swal.fire({
                        title: '<?= __('Xác nhận xóa file installer.php'); ?>',
                        text: '<?= __('Bạn có chắc chắn muốn xóa file installer.php? Hành động này không thể hoàn tác.'); ?>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '<?= __('Xóa ngay'); ?>',
                        cancelButtonText: '<?= __('Hủy'); ?>'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
                                method: "POST",
                                dataType: "JSON",
                                data: {
                                    action: 'deleteInstallerFile',
                                    token: '<?= $getUser['token']; ?>'
                                },
                                success: function(response) {
                                    if (response.status == 'success') {
                                        Swal.fire({
                                            title: '<?= __('Thành công!'); ?>',
                                            text: response.msg,
                                            icon: 'success',
                                            confirmButtonText: '<?= __('Đóng'); ?>'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire('<?= __('Lỗi!'); ?>', response.msg, 'error');
                                    }
                                },
                                error: function() {
                                    Swal.fire('<?= __('Lỗi!'); ?>', '<?= __('Có lỗi xảy ra khi xóa file'); ?>',
                                        'error');
                                }
                            });
                        }
                    });
                }
            </script>
        <?php endif; ?>
        <?php if ($CMSNT->site('smtp_status') != 1): ?>
            <div class="alert alert-warning alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-warning" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
                </svg>
                Vui lòng cấu hình <b>SMTP</b> để sử dụng toàn bộ tiện ích từ Mail:
                <a class="text-primary"
                    href="https://help.cmsnt.co/huong-dan/huong-dan-cau-hinh-smtp-vao-website-shopclone7/"
                    target="_blank">Xem Hướng Dẫn</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <?php if ($CMSNT->site('smtp_status') == 1 && time() - $CMSNT->site('check_time_cron_email_queue') >= 120): ?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                </svg>
                <strong><i class="ri-mail-send-line me-1"></i>Email Queue:</strong>
                <?= __('Vui lòng thực hiện'); ?> <b><a target="_blank" class="text-primary"
                        href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b>
                <?= __('liên kết'); ?>:
                <a class="text-primary" href="<?= base_url('cron/process_email_queue.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                    target="_blank">
                    <?= base_url('cron/process_email_queue.php?key=' . $CMSNT->site('key_cron_job')); ?>
                </a> <?= __('1 phút 1 lần để hệ thống tự động gửi email.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <?php if ($CMSNT->site('telegram_status') == 1 && time() - $CMSNT->site('check_time_cron_telegram_queue') >= 120): ?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                </svg>
                <strong><i class="fab fa-telegram me-1"></i>Telegram Queue:</strong>
                <?= __('Vui lòng thực hiện'); ?> <b><a target="_blank" class="text-primary"
                        href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b>
                <?= __('liên kết'); ?>:
                <a class="text-primary" href="<?= base_url('cron/process_telegram_queue.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                    target="_blank">
                    <?= base_url('cron/process_telegram_queue.php?key=' . $CMSNT->site('key_cron_job')); ?>
                </a> <?= __('1 phút 1 lần để hệ thống tự động gửi thông báo Telegram.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <?php if ($CMSNT->site('debug_auto_bank') == 1): ?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                </svg>
                Vui lòng tắt chức năng <b>Debug Auto Bank</b> khi không gặp vấn đề về Auto Bank. Tắt chức năng này trong Cài
                Đặt -> ON/OFF Debug Auto Bank
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <?php if (version_compare(PHP_VERSION, '7.4.0', '<') || version_compare(PHP_VERSION, '7.5.0', '>=')): ?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                </svg>
                <strong><i class="ri-error-warning-line me-1"></i><?= __('Phiên bản PHP không tương thích!'); ?></strong>
                <?= __('Hệ thống yêu cầu'); ?> <b>PHP 7.4</b>. <?= __('Phiên bản hiện tại của bạn là'); ?> <b class="text-danger">PHP <?= PHP_VERSION; ?></b>.
                <?= __('Vui lòng chuyển sang PHP 7.4 để đảm bảo hệ thống hoạt động ổn định.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <div class="row">
            <?php if (checkPermission($getUser['admin'], 'view_statistical') == true): ?>
                <div class="col-12">
                    <div class="text-right mb-3">
                        <img src="<?= base_url('mod/img/gif-live.gif'); ?>" width="60px">
                    </div>
                </div>
                <!-- ========================= 4 Widget Thống kê lớn ========================= -->

                <!-- Widget Toàn thời gian - Xanh dương -->
                <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card custom-card overflow-hidden" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold mb-0 text-white"><?= __('TOÀN THỜI GIAN'); ?></h6>
                                <span style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.25); border-radius: 50%;">
                                    <i class="fa-solid fa-infinity" style="font-size: 18px; color: #fff;"></i>
                                </span>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Thành viên'); ?></p>
                                        <h5 class="fw-bold mb-0 text-white" id="total_users_all">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Đơn hàng'); ?></p>
                                        <h5 class="fw-bold mb-0 text-white" id="total_orders_all">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Doanh thu'); ?></p>
                                        <h6 class="fw-bold mb-0 text-white fs-13" id="total_pay_all">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h6>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Lợi nhuận'); ?></p>
                                        <h6 class="fw-bold mb-0 text-white fs-13" id="profit_all">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget Tháng này - Tím -->
                <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card custom-card overflow-hidden" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold mb-0 text-white"><?= __('THÁNG'); ?> <?= date('m'); ?></h6>
                                <span style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.25); border-radius: 50%;">
                                    <i class="fa-solid fa-calendar-days" style="font-size: 18px; color: #fff;"></i>
                                </span>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Thành viên'); ?></p>
                                        <h5 class="fw-bold mb-0 text-white" id="new_users_month">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Đơn hàng'); ?></p>
                                        <h5 class="fw-bold mb-0 text-white" id="total_orders_month">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Doanh thu'); ?></p>
                                        <h6 class="fw-bold mb-0 text-white fs-13" id="total_pay_month">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h6>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Lợi nhuận'); ?></p>
                                        <h6 class="fw-bold mb-0 text-white fs-13" id="profit_month">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget Tuần này - Xanh lá -->
                <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card custom-card overflow-hidden" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold mb-0 text-white"><?= __('TUẦN NÀY'); ?></h6>
                                <span style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.25); border-radius: 50%;">
                                    <i class="fa-solid fa-calendar-week" style="font-size: 18px; color: #fff;"></i>
                                </span>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Thành viên'); ?></p>
                                        <h5 class="fw-bold mb-0 text-white" id="new_users_week">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Đơn hàng'); ?></p>
                                        <h5 class="fw-bold mb-0 text-white" id="total_orders_week">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Doanh thu'); ?></p>
                                        <h6 class="fw-bold mb-0 text-white fs-13" id="total_pay_week">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h6>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Lợi nhuận'); ?></p>
                                        <h6 class="fw-bold mb-0 text-white fs-13" id="profit_week">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget Hôm nay - Cam -->
                <div class="col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12">
                    <div class="card custom-card overflow-hidden" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fw-bold mb-0 text-white"><?= __('HÔM NAY'); ?></h6>
                                <span style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.25); border-radius: 50%;">
                                    <i class="fa-solid fa-sun" style="font-size: 18px; color: #fff;"></i>
                                </span>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Thành viên'); ?></p>
                                        <h5 class="fw-bold mb-0 text-white" id="new_users_today">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Đơn hàng'); ?></p>
                                        <h5 class="fw-bold mb-0 text-white" id="total_orders_today">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Doanh thu'); ?></p>
                                        <h6 class="fw-bold mb-0 text-white fs-13" id="total_pay_today">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h6>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                        <p class="mb-1 text-white text-opacity-75 fs-11"><?= __('Lợi nhuận'); ?></p>
                                        <h6 class="fw-bold mb-0 text-white fs-13" id="profit_today">
                                            <i class="fas fa-spinner fa-spin spinner" style="display: none;"></i>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ========================= Kết thúc 4 Widget Thống kê lớn ========================= -->

                <script>
                    function show_thong_ke_dashboard() {
                        $.ajax({
                            url: '<?= base_url('ajaxs/admin/view.php'); ?>',
                            method: 'POST',
                            dataType: 'JSON',
                            data: {
                                action: 'show_thong_ke_dashboard',
                                token: '<?= $getUser['token']; ?>'
                            },
                            beforeSend: function() {
                                $('.spinner').show(); // Hiển thị icon spin trước khi gửi yêu cầu
                            },
                            success: function(data) {
                                if (data.status !== 'error') {
                                    // Cập nhật dữ liệu Toàn thời gian
                                    $('#total_users_all').text(data.total_users_all);
                                    $('#total_orders_all').text(data.total_orders_all);
                                    $('#total_pay_all').text(data.total_pay_all);
                                    $('#profit_all').text(data.profit_all);

                                    // Cập nhật dữ liệu Tháng này
                                    $('#new_users_month').text(data.new_users_month);
                                    $('#total_orders_month').text(data.total_orders_month);
                                    $('#total_pay_month').text(data.total_pay_month);
                                    $('#profit_month').text(data.profit_month);

                                    // Cập nhật dữ liệu Hôm nay
                                    $('#new_users_today').text(data.new_users_today);
                                    $('#total_orders_today').text(data.total_orders_today);
                                    $('#total_pay_today').text(data.total_pay_today);
                                    $('#profit_today').text(data.profit_today);

                                    // Cập nhật dữ liệu Tuần này
                                    $('#new_users_week').text(data.new_users_week);
                                    $('#total_orders_week').text(data.total_orders_week);
                                    $('#total_pay_week').text(data.total_pay_week);
                                    $('#profit_week').text(data.profit_week);
                                } else {
                                    // Xử lý khi có lỗi từ phía backend
                                    alert(data.msg);
                                }
                            },
                            complete: function() {
                                $('.spinner').hide(); // Ẩn icon spin sau khi hoàn thành yêu cầu
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', error);
                                $('.spinner').hide(); // Ẩn icon spin trong trường hợp lỗi
                            }
                        });
                    }

                    $(document).ready(function() {
                        show_thong_ke_dashboard(); // Cập nhật dữ liệu ngay khi tải trang
                        setInterval(show_thong_ke_dashboard, 5000); // Cập nhật dữ liệu mỗi 5 giây
                    });
                </script>


                <div class="col-xl-6">
                    <div class="card custom-card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h6 class="card-title mb-0" id="chart-order-title">THỐNG KÊ ĐƠN HÀNG THÁNG <?= date('m'); ?></h6>
                            <div class="dropdown">
                                <select id="chart-time-range" class="form-select form-select-sm">
                                    <option value="today">Hôm nay</option>
                                    <option value="week">7 ngày gần đây</option>
                                    <option value="month" selected>Tháng <?= date('m'); ?></option>
                                    <option value="last_month">Tháng <?= date('m', strtotime('-1 month')); ?></option>
                                    <option value="year">Năm <?= date('Y'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Thêm hiệu ứng loading cho chart đơn hàng -->
                            <div id="chart-order-loader" class="text-center py-5" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang tải...</span>
                                </div>
                                <div class="mt-2">Đang tải dữ liệu biểu đồ...</div>
                            </div>
                            <canvas id="chartjs-line" class="chartjs-chart" style="height: 300px;"></canvas>
                            <script>
                                (function() {
                                    document.addEventListener('DOMContentLoaded', function() {
                                        let myChart;

                                        function loadChartData(timeRange) {
                                            // Cập nhật tiêu đề
                                            let titleText;
                                            switch (timeRange) {
                                                case 'today':
                                                    titleText = 'THỐNG KÊ ĐƠN HÀNG TRONG NGÀY';
                                                    break;
                                                case 'week':
                                                    titleText = 'THỐNG KÊ ĐƠN HÀNG 7 NGÀY GẦN ĐÂY';
                                                    break;
                                                case 'month':
                                                    titleText = 'THỐNG KÊ ĐƠN HÀNG THÁNG <?= date('m'); ?>';
                                                    break;
                                                case 'last_month':
                                                    titleText = 'THỐNG KÊ ĐƠN HÀNG THÁNG <?= date('m', strtotime('-1 month')); ?>';
                                                    break;
                                                case 'year':
                                                    titleText = 'THỐNG KÊ ĐƠN HÀNG NĂM <?= date('Y'); ?>';
                                                    break;
                                            }
                                            document.getElementById('chart-order-title').innerText = titleText;

                                            // Hiển thị loader và ẩn chart
                                            document.getElementById('chart-order-loader').style.display = 'block';
                                            document.getElementById('chartjs-line').style.opacity = '0';

                                            // Hủy biểu đồ cũ nếu tồn tại
                                            if (myChart) {
                                                myChart.destroy();
                                            }

                                            // Gọi API lấy dữ liệu mới
                                            $.ajax({
                                                url: '<?= base_url('ajaxs/admin/view.php'); ?>',
                                                method: 'POST',
                                                dataType: 'json',
                                                data: {
                                                    action: 'view_chart_thong_ke_don_hang',
                                                    token: '<?= $getUser['token']; ?>',
                                                    time_range: timeRange
                                                },
                                                success: function(response) {
                                                    // Ẩn loader và hiển thị biểu đồ
                                                    document.getElementById('chart-order-loader').style.display = 'none';
                                                    document.getElementById('chartjs-line').style.opacity = '1';

                                                    const labels = response.labels;
                                                    const revenues = response.revenues;
                                                    const profits = response.profits;
                                                    const data = {
                                                        labels: labels,
                                                        datasets: [{
                                                                label: 'Lợi nhuận (VNĐ)',
                                                                backgroundColor: 'rgb(73,182,245)',
                                                                borderColor: 'rgb(73,182,245)',
                                                                data: profits,
                                                            },
                                                            {
                                                                label: 'Doanh thu (VNĐ)',
                                                                backgroundColor: 'rgb(132, 90, 223)',
                                                                borderColor: 'rgb(132, 90, 223)',
                                                                data: revenues,
                                                            }
                                                        ]
                                                    };
                                                    const config = {
                                                        type: 'bar',
                                                        data: data,
                                                        options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false
                                                        }
                                                    };
                                                    myChart = new Chart(
                                                        document.getElementById('chartjs-line'),
                                                        config
                                                    );
                                                },
                                                error: function() {
                                                    // Ẩn loader khi có lỗi
                                                    document.getElementById('chart-order-loader').style.display = 'none';
                                                    document.getElementById('chartjs-line').style.opacity = '1';

                                                    // Hiển thị biểu đồ thông báo lỗi
                                                    const config = {
                                                        type: 'bar',
                                                        data: {
                                                            labels: ['Không có dữ liệu'],
                                                            datasets: []
                                                        },
                                                        options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false,
                                                            plugins: {
                                                                title: {
                                                                    display: true,
                                                                    text: 'Không thể tải dữ liệu biểu đồ. Vui lòng thử lại sau.',
                                                                    color: '#dc3545',
                                                                    font: {
                                                                        size: 16
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    };

                                                    myChart = new Chart(
                                                        document.getElementById('chartjs-line'),
                                                        config
                                                    );
                                                }
                                            });
                                        }

                                        // Xử lý sự kiện khi người dùng thay đổi khoảng thời gian
                                        document.getElementById('chart-time-range').addEventListener('change', function() {
                                            loadChartData(this.value);
                                        });

                                        // Khởi tạo biểu đồ với tháng hiện tại
                                        setTimeout(function() {
                                            Chart.defaults.borderColor = "rgba(142, 156, 173,0.1)";
                                            Chart.defaults.color = "#8c9097";
                                            loadChartData('month');
                                        }, 5);
                                    });
                                })();
                            </script>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card custom-card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h6 class="card-title mb-0" id="chart-deposit-title">THỐNG KÊ NẠP TIỀN THÁNG <?= date('m') ?></h6>
                            <div class="dropdown">
                                <select id="chart-deposit-time-range" class="form-select form-select-sm">
                                    <option value="today">Hôm nay</option>
                                    <option value="week">7 ngày gần đây</option>
                                    <option value="month" selected>Tháng <?= date('m') ?></option>
                                    <option value="last_month">Tháng <?= date('m', strtotime('-1 month')); ?></option>
                                    <option value="year">Năm <?= date('Y') ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Thêm hiệu ứng loading cho chart nạp tiền -->
                            <div id="chart-deposit-loader" class="text-center py-5" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang tải...</span>
                                </div>
                                <div class="mt-2">Đang tải dữ liệu biểu đồ...</div>
                            </div>
                            <canvas id="chartjs-naptien" class="chartjs-chart" style="height: 300px;"></canvas>
                            <script>
                                (function() {
                                    document.addEventListener('DOMContentLoaded', function() {
                                        let myChart;

                                        function loadChartData(timeRange) {
                                            // Cập nhật tiêu đề
                                            let titleText;
                                            switch (timeRange) {
                                                case 'today':
                                                    titleText = 'THỐNG KÊ NẠP TIỀN TRONG NGÀY';
                                                    break;
                                                case 'week':
                                                    titleText = 'THỐNG KÊ NẠP TIỀN 7 NGÀY GẦN ĐÂY';
                                                    break;
                                                case 'month':
                                                    titleText = 'THỐNG KÊ NẠP TIỀN THÁNG <?= date('m') ?>';
                                                    break;
                                                case 'last_month':
                                                    titleText = 'THỐNG KÊ NẠP TIỀN THÁNG <?= date('m', strtotime('-1 month')); ?>';
                                                    break;
                                                case 'year':
                                                    titleText = 'THỐNG KÊ NẠP TIỀN NĂM <?= date('Y') ?>';
                                                    break;
                                            }
                                            document.getElementById('chart-deposit-title').innerText = titleText;

                                            // Hiển thị loader và ẩn chart
                                            document.getElementById('chart-deposit-loader').style.display = 'block';
                                            document.getElementById('chartjs-naptien').style.opacity = '0';

                                            // Hủy biểu đồ cũ nếu tồn tại
                                            if (myChart) {
                                                myChart.destroy();
                                            }

                                            // Gọi API lấy dữ liệu mới
                                            $.ajax({
                                                url: '<?= base_url('ajaxs/admin/view.php'); ?>',
                                                method: 'POST',
                                                dataType: 'json',
                                                data: {
                                                    action: 'view_chart_thong_ke_nap_tien',
                                                    token: '<?= $getUser['token']; ?>',
                                                    time_range: timeRange
                                                },
                                                success: function(response) {
                                                    // Ẩn loader và hiển thị biểu đồ
                                                    document.getElementById('chart-deposit-loader').style.display = 'none';
                                                    document.getElementById('chartjs-naptien').style.opacity = '1';

                                                    const labels = response.labels;
                                                    const revenues = response.amount;
                                                    const data = {
                                                        labels: labels,
                                                        datasets: [{
                                                            label: 'Doanh thu (VNĐ)',
                                                            backgroundColor: 'rgb(29, 78, 216)',
                                                            borderColor: 'rgb(29, 78, 216)',
                                                            data: revenues,
                                                        }]
                                                    };
                                                    const config = {
                                                        type: 'bar',
                                                        data: data,
                                                        options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false
                                                        }
                                                    };
                                                    myChart = new Chart(
                                                        document.getElementById('chartjs-naptien'),
                                                        config
                                                    );
                                                },
                                                error: function() {
                                                    // Ẩn loader khi có lỗi
                                                    document.getElementById('chart-deposit-loader').style.display = 'none';
                                                    document.getElementById('chartjs-naptien').style.opacity = '1';

                                                    // Hiển thị biểu đồ thông báo lỗi
                                                    const config = {
                                                        type: 'bar',
                                                        data: {
                                                            labels: ['Không có dữ liệu'],
                                                            datasets: []
                                                        },
                                                        options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false,
                                                            plugins: {
                                                                title: {
                                                                    display: true,
                                                                    text: 'Không thể tải dữ liệu biểu đồ. Vui lòng thử lại sau.',
                                                                    color: '#dc3545',
                                                                    font: {
                                                                        size: 16
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    };

                                                    myChart = new Chart(
                                                        document.getElementById('chartjs-naptien'),
                                                        config
                                                    );
                                                }
                                            });
                                        }

                                        // Xử lý sự kiện khi người dùng thay đổi khoảng thời gian
                                        document.getElementById('chart-deposit-time-range').addEventListener('change', function() {
                                            loadChartData(this.value);
                                        });

                                        // Khởi tạo biểu đồ với tháng hiện tại
                                        setTimeout(function() {
                                            Chart.defaults.borderColor = "rgba(142, 156, 173,0.1)";
                                            Chart.defaults.color = "#8c9097";
                                            loadChartData('month');
                                        }, 5);
                                    });
                                })();
                            </script>
                        </div>
                    </div>
                </div>



            <?php endif ?>
            <?php if (checkPermission($getUser['admin'], 'view_recent_transactions') == true): ?>
                <div class="col-xl-6">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">ĐƠN HÀNG GẦN ĐÂY</div>
                            <div class="ms-auto">
                                <img class="text-right" src="<?= base_url('mod/img/gif-live.gif'); ?>" width="60px">
                            </div>
                        </div>
                    </div>
                    <ul class="timeline list-unstyled orders-timeline"
                        style="height:500px;overflow-x:hidden;overflow-y:auto;">

                    </ul>
                </div>
                <div class="col-xl-6">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">NẠP TIỀN GẦN ĐÂY</div>
                            <div class="ms-auto">
                                <img class="text-right" src="<?= base_url('mod/img/gif-live.gif'); ?>" width="60px">
                            </div>
                        </div>
                    </div>
                    <ul class="timeline list-unstyled deposits-timeline"
                        style="height:500px;overflow-x:hidden;overflow-y:auto;">

                    </ul>
                </div>
                <script>
                    function fetchOrders() {
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/view.php'); ?>",
                            method: 'POST',
                            data: {
                                action: 'view_don_hang_gan_day',
                                token: '<?= $getUser['token']; ?>'
                            },
                            success: function(html) {
                                $('.orders-timeline').html(html);
                            }
                        });
                    }

                    function fetchDeposits() {
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/view.php'); ?>",
                            method: 'POST',
                            data: {
                                action: 'view_nap_tien_gan_day',
                                token: '<?= $getUser['token']; ?>'
                            },
                            success: function(html) {
                                $('.deposits-timeline').html(html);
                            }
                        });
                    }
                    setInterval(fetchOrders, 5000);
                    setInterval(fetchDeposits, 5000);

                    $(document).ready(function() {
                        fetchOrders();
                        fetchDeposits();
                    });
                </script>
            <?php endif ?>
        </div>
    </div>
</div>




<!-- Floating buttons -->
<div class="position-fixed" style="bottom: 80px; right: 20px; z-index: 1000;">
    <!-- Button Top Services -->
    <div class="mb-3">
        <button type="button" class="btn btn-success btn-icon rounded-circle shadow-lg" onclick="showTopServices()"
            data-toggle="tooltip" data-placement="left"
            title="<?= __('Top 50 dịch vụ bán chạy nhất hôm nay') ?>">
            <i class="fas fa-chart-bar fs-18"></i>
        </button>
    </div>

    <!-- Button Leaderboard -->
    <div>
        <button type="button" class="btn btn-primary btn-icon rounded-circle shadow-lg" onclick="showLeaderboard()"
            data-toggle="tooltip" data-placement="left"
            title="<?= __('Top 50 khách hàng chi tiêu nhiều nhất hôm nay') ?>">
            <i class="fas fa-trophy fs-18"></i>
        </button>
    </div>
</div>

<!-- Modal Bảng xếp hạng -->
<div class="modal fade" id="leaderboardModal" tabindex="-1" aria-labelledby="leaderboardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaderboardModalLabel">
                    <i class="fas fa-trophy text-warning me-2"></i><?= __('Bảng xếp hạng ngày') ?> <span
                        id="leaderboard-date"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                <div id="leaderboard-loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?= __('Đang tải...') ?></span>
                    </div>
                    <div class="mt-2"><?= __('Đang tải bảng xếp hạng...') ?></div>
                </div>

                <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong><?= __('Top 50 khách hàng chi tiêu nhiều nhất hôm nay') ?></strong><br />
                        <small><?= __('Danh sách được cập nhật theo thời gian thực') ?></small>
                    </div>
                </div>

                <div id="leaderboard-content" class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-primary sticky-top">
                            <tr>
                                <th class="text-center" style="width: 80px;"><?= __('Hạng') ?></th>
                                <th style="width: 170px;"><?= __('ID - Tên đăng nhập') ?></th>
                                <th><?= __('Địa chỉ Email') ?></th>
                                <th class="text-end" style="width: 140px;"><?= __('Tổng chi tiêu') ?></th>
                                <th class="text-center" style="width: 100px;"><?= __('Số đơn hàng') ?></th>
                            </tr>
                        </thead>
                        <tbody id="leaderboard-table-body">
                            <!-- Dữ liệu sẽ được tải bằng AJAX -->
                        </tbody>
                    </table>
                </div>

                <div id="leaderboard-empty" class="text-center py-4" style="display: none;">
                    <i class="fas fa-inbox text-muted mb-2" style="font-size: 3rem;"></i>
                    <p class="text-muted"><?= __('Chưa có dữ liệu bảng xếp hạng cho ngày hôm nay') ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i><?= __('Đóng') ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="loadLeaderboard()">
                    <i class="fas fa-sync-alt me-1"></i><?= __('Làm mới') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Top Services -->
<div class="modal fade" id="topServicesModal" tabindex="-1" aria-labelledby="topServicesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="topServicesModalLabel">
                    <i class="fas fa-chart-bar text-success me-2"></i><?= __('Top dịch vụ bán chạy nhất') ?> <span id="services-date"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
                <div id="services-loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden"><?= __('Đang tải...') ?></span>
                    </div>
                    <div class="mt-2"><?= __('Đang tải danh sách dịch vụ...') ?></div>
                </div>

                <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong><?= __('Top 50 dịch vụ có doanh thu cao nhất hôm nay') ?></strong><br />
                        <small><?= __('Được sắp xếp theo tổng doanh thu từ cao đến thấp') ?></small>
                    </div>
                </div>

                <div id="services-content" class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-success sticky-top">
                            <tr>
                                <th class="text-center" style="width: 60px;"><?= __('Hạng') ?></th>
                                <th style="width: 320px;"><?= __('ID - Tên dịch vụ') ?></th>
                                <th class="text-end" style="width: 120px;"><?= __('Doanh thu') ?></th>
                                <th class="text-end" style="width: 120px;"><?= __('Chi phí') ?></th>
                                <th class="text-end" style="width: 120px;"><?= __('Lợi nhuận') ?></th>
                                <th class="text-center" style="width: 100px;"><?= __('Số đơn') ?></th>
                                <th class="text-end" style="width: 120px;"><?= __('Giá TB') ?></th>
                            </tr>
                        </thead>
                        <tbody id="services-table-body">
                            <!-- Dữ liệu sẽ được tải bằng AJAX -->
                        </tbody>
                    </table>
                </div>

                <div id="services-empty" class="text-center py-4" style="display: none;">
                    <i class="fas fa-box-open text-muted mb-2" style="font-size: 3rem;"></i>
                    <p class="text-muted"><?= __('Chưa có dịch vụ nào được bán trong ngày hôm nay') ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i><?= __('Đóng') ?>
                </button>
                <button type="button" class="btn btn-success" onclick="loadTopServices()">
                    <i class="fas fa-sync-alt me-1"></i><?= __('Làm mới') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function showLeaderboard() {
        // Đóng top services modal nếu đang mở
        if ($('#topServicesModal').hasClass('show')) {
            $('#topServicesModal').modal('hide');
        }

        // Hiển thị modal và tải dữ liệu
        $('#leaderboardModal').modal('show');
        loadLeaderboard();
    }

    function loadLeaderboard() {
        // Hiển thị loading
        $('#leaderboard-loading').show();
        $('#leaderboard-content').hide();
        $('#leaderboard-empty').hide();

        $.ajax({
            url: '<?= base_url('ajaxs/admin/view.php') ?>',
            method: 'POST',
            dataType: 'JSON',
            data: {
                action: 'get_daily_leaderboard',
                token: '<?= $getUser['token'] ?>'
            },
            success: function(response) {
                // Ẩn loading
                $('#leaderboard-loading').hide();

                if (response.status === 'success') {
                    // Cập nhật ngày
                    $('#leaderboard-date').text(response.date);

                    if (response.data.length > 0) {
                        // Hiển thị bảng
                        $('#leaderboard-content').show();

                        // Xóa dữ liệu cũ
                        $('#leaderboard-table-body').empty();

                        // Thêm dữ liệu mới
                        response.data.forEach(function(user) {
                            let rankClass = '';
                            let rankIcon = '';

                            if (user.rank === 1) {
                                rankClass = 'text-warning fw-bold';
                                rankIcon = '<i class="fas fa-crown text-warning me-1"></i>';
                            } else if (user.rank === 2) {
                                rankClass = 'text-secondary fw-bold';
                                rankIcon = '<i class="fas fa-medal text-secondary me-1"></i>';
                            } else if (user.rank === 3) {
                                rankClass = 'text-danger fw-bold';
                                rankIcon = '<i class="fas fa-medal text-danger me-1"></i>';
                            }

                            let row = `
                            <tr>
                                <td class="text-center ${rankClass}">
                                    ${rankIcon}${user.rank}
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="<?= base_url_admin('user-edit&id=') ?>${user.id}" 
                                           class="text-decoration-none fw-bold text-primary" data-toggle="tooltip" data-placement="left"
                                           title="<?= __('Chỉnh sửa thành viên') ?>">
                                            ${user.username}
                                        </a>
                                        <small class="text-muted"><?= __('ID:') ?> ${user.id}</small>
                                    </div>
                                </td>
                                <td>${user.email}</td>
                                <td class="text-end fw-bold text-success">${user.total_spent}</td>
                                <td class="text-center">
                                    <span class="badge bg-primary">${user.total_orders}</span>
                                </td>
                            </tr>
                        `;
                            $('#leaderboard-table-body').append(row);
                        });
                    } else {
                        // Hiển thị thông báo trống
                        $('#leaderboard-empty').show();
                    }
                } else {
                    // Hiển thị lỗi
                    $('#leaderboard-empty').show();
                    $('#leaderboard-empty').html(`
                    <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 3rem;"></i>
                    <p class="text-muted">${response.msg}</p>
                `);
                }
            },
            error: function() {
                // Ẩn loading và hiển thị lỗi
                $('#leaderboard-loading').hide();
                $('#leaderboard-empty').show();
                $('#leaderboard-empty').html(`
                <i class="fas fa-exclamation-triangle text-danger mb-2" style="font-size: 3rem;"></i>
                <p class="text-muted"><?= __('Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại.') ?></p>
            `);
            }
        });
    }

    // Tự động tải bảng xếp hạng khi modal được mở
    $('#leaderboardModal').on('shown.bs.modal', function() {
        if ($('#leaderboard-table-body').is(':empty')) {
            loadLeaderboard();
        }
    });

    // ========== Top Services Functions ==========
    function showTopServices() {
        // Đóng leaderboard modal nếu đang mở
        if ($('#leaderboardModal').hasClass('show')) {
            $('#leaderboardModal').modal('hide');
        }

        // Hiển thị modal và tải dữ liệu
        $('#topServicesModal').modal('show');
        loadTopServices();
    }

    function loadTopServices() {
        // Hiển thị loading
        $('#services-loading').show();
        $('#services-content').hide();
        $('#services-empty').hide();

        $.ajax({
            url: '<?= base_url('ajaxs/admin/view.php') ?>',
            method: 'POST',
            dataType: 'JSON',
            data: {
                action: 'get_daily_top_services',
                token: '<?= $getUser['token'] ?>'
            },
            success: function(response) {
                // Ẩn loading
                $('#services-loading').hide();

                if (response.status === 'success') {
                    // Cập nhật ngày
                    $('#services-date').text(response.date);

                    if (response.data.length > 0) {
                        // Hiển thị bảng
                        $('#services-content').show();

                        // Xóa dữ liệu cũ
                        $('#services-table-body').empty();

                        // Thêm dữ liệu mới
                        response.data.forEach(function(service) {
                            let rankClass = '';
                            let rankIcon = '';

                            if (service.rank === 1) {
                                rankClass = 'text-warning fw-bold';
                                rankIcon = '<i class="fas fa-crown text-warning me-1"></i>';
                            } else if (service.rank === 2) {
                                rankClass = 'text-secondary fw-bold';
                                rankIcon = '<i class="fas fa-medal text-secondary me-1"></i>';
                            } else if (service.rank === 3) {
                                rankClass = 'text-danger fw-bold';
                                rankIcon = '<i class="fas fa-medal text-danger me-1"></i>';
                            }

                            // Màu lợi nhuận: xanh nếu > 0, đỏ nếu < 0
                            let profitClass = 'text-success';
                            if (service.profit.indexOf('-') === 0) {
                                profitClass = 'text-danger';
                            }

                            let row = `
                            <tr>
                                <td class="text-center ${rankClass}">
                                    ${rankIcon}${service.rank}
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="<?= base_url_admin('product-edit&id=') ?>${service.product_id}" 
                                           class="text-decoration-none fw-bold text-primary"  data-toggle="tooltip" data-placement="left"
                                           title="<?= __('Chỉnh sửa sản phẩm') ?>">
                                            #${service.product_id} - ${service.product_name}
                                        </a>
                                    </div>
                                </td>
                                <td class="text-end fw-bold text-primary">${service.total_revenue}</td>
                                <td class="text-end text-warning">${service.total_cost}</td>
                                <td class="text-end fw-bold ${profitClass}">${service.profit}</td>
                                <td class="text-center">
                                    <span class="badge bg-info">${service.total_orders}</span>
                                </td>
                                <td class="text-end text-muted">${service.avg_price}</td>
                            </tr>
                        `;
                            $('#services-table-body').append(row);
                        });
                    } else {
                        // Hiển thị thông báo trống
                        $('#services-empty').show();
                    }
                } else {
                    // Hiển thị lỗi
                    $('#services-empty').show();
                    $('#services-empty').html(`
                    <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 3rem;"></i>
                    <p class="text-muted">${response.msg}</p>
                `);
                }
            },
            error: function() {
                // Ẩn loading và hiển thị lỗi
                $('#services-loading').hide();
                $('#services-empty').show();
                $('#services-empty').html(`
                <i class="fas fa-exclamation-triangle text-danger mb-2" style="font-size: 3rem;"></i>
                <p class="text-muted"><?= __('Có lỗi xảy ra khi tải dữ liệu. Vui lòng thử lại.') ?></p>
            `);
            }
        });
    }

    // Tự động tải danh sách services khi modal được mở
    $('#topServicesModal').on('shown.bs.modal', function() {
        if ($('#services-table-body').is(':empty')) {
            loadTopServices();
        }
    });

    // Event listeners để đảm bảo chỉ một modal mở tại một thời điểm
    $('#topServicesModal').on('show.bs.modal', function() {
        $('#leaderboardModal').modal('hide');
    });

    $('#leaderboardModal').on('show.bs.modal', function() {
        $('#topServicesModal').modal('hide');
    });
</script>


<?php
require_once(__DIR__ . '/footer.php');
?>
<script type="text/javascript">
    new ClipboardJS(".copy");

    function copy() {
        showMessage('Đã sao chép vào bộ nhớ tạm', 'success');
    }
</script>