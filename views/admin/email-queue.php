<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Email Queue'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');

if (checkPermission($getUser['admin'], 'view_logs') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}

// Pagination
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$from = ($page - 1) * $limit;

// Filters
$where = " `id` > 0 ";
$params = [];
$status_filter = isset($_GET['status']) ? check_string($_GET['status']) : '';
$to_email = isset($_GET['to_email']) ? check_string($_GET['to_email']) : '';
$subject_filter = isset($_GET['subject_filter']) ? check_string($_GET['subject_filter']) : '';

if (!empty($status_filter)) {
    $where .= " AND `status` = ? ";
    $params[] = $status_filter;
}
if (!empty($to_email)) {
    $where .= " AND `to_email` LIKE ? ";
    $params[] = '%' . $to_email . '%';
}
if (!empty($subject_filter)) {
    $where .= " AND `subject` LIKE ? ";
    $params[] = '%' . $subject_filter . '%';
}

if (!empty($params)) {
    $listDatatable = $CMSNT->get_list_safe(" SELECT * FROM `email_queue` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ", $params);
    $totalDatatable = $CMSNT->num_rows_safe(" SELECT * FROM `email_queue` WHERE $where ORDER BY id DESC ", $params);
} else {
    $listDatatable = $CMSNT->get_list(" SELECT * FROM `email_queue` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
    $totalDatatable = $CMSNT->num_rows(" SELECT * FROM `email_queue` WHERE $where ORDER BY id DESC ");
}

$urlDatatable = pagination(base_url_admin("email-queue&limit=$limit&status=$status_filter&to_email=$to_email&subject_filter=$subject_filter&"), $from, $totalDatatable, $limit);

// Statistics
$stats = [
    'pending' => $CMSNT->num_rows(" SELECT id FROM `email_queue` WHERE `status` = 'pending' "),
    'sent' => $CMSNT->num_rows(" SELECT id FROM `email_queue` WHERE `status` = 'sent' "),
    'failed' => $CMSNT->num_rows(" SELECT id FROM `email_queue` WHERE `status` = 'failed' "),
];
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="d-flex align-items-center gap-3">
                <h1 class="page-title fw-semibold fs-18 mb-0"><i class="ri-mail-send-line"></i> <?= __('Email chờ gửi'); ?></h1>
                <?php if (checkPermission($getUser['admin'], 'edit_logs')): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cleanupEmailQueueModal">
                        <i class="ri-delete-bin-line me-1"></i><?= __('Dọn dẹp'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($CMSNT->site('smtp_status') != 1): ?>
            <div class="alert alert-warning alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-warning" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24" width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
                </svg>
                <?= __('Chức năng gửi Email đang tắt. Các email mới sẽ không được thêm vào hàng đợi.'); ?>
                <a href="<?= base_url_admin('settings&tab=connection'); ?>" class="alert-link fw-semibold">
                    <i class="ri-settings-3-line me-1"></i><?= __('Bật trong Cài đặt SMTP'); ?>
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (time() - $CMSNT->site('check_time_cron_email_queue') >= 120): ?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                </svg>
                <?= __('Vui lòng thực hiện'); ?> <b><a target="_blank" class="text-primary"
                        href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b>
                <?= __('liên kết'); ?>:
                <a class="text-primary" href="<?= base_url('cron/process_email_queue.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                    target="_blank">
                    <?= base_url('cron/process_email_queue.php?key=' . $CMSNT->site('key_cron_job')); ?>
                </a> <?= __('1 phút 1 lần để hệ thống tự động gửi email.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        <?php endif; ?>


        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card custom-card bg-warning-transparent">
                    <div class="card-body text-center">
                        <h3 class="mb-1"><?= format_cash($stats['pending']); ?></h3>
                        <span class="text-muted"><i class="ri-time-line me-1"></i><?= __('Đang chờ'); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card bg-success-transparent">
                    <div class="card-body text-center">
                        <h3 class="mb-1"><?= format_cash($stats['sent']); ?></h3>
                        <span class="text-muted"><i class="ri-check-line me-1"></i><?= __('Đã gửi'); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card bg-danger-transparent">
                    <div class="card-body text-center">
                        <h3 class="mb-1"><?= format_cash($stats['failed']); ?></h3>
                        <span class="text-muted"><i class="ri-close-circle-line me-1"></i><?= __('Thất bại'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title"><?= __('DANH SÁCH EMAIL QUEUE'); ?></div>
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url(); ?>" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="email-queue">
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-select form-select-sm" name="status">
                                        <option value=""><?= __('Tất cả trạng thái'); ?></option>
                                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : ''; ?>><?= __('Đang chờ'); ?></option>
                                        <option value="sent" <?= $status_filter == 'sent' ? 'selected' : ''; ?>><?= __('Đã gửi'); ?></option>
                                        <option value="failed" <?= $status_filter == 'failed' ? 'selected' : ''; ?>><?= __('Thất bại'); ?></option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= htmlspecialchars($to_email); ?>" name="to_email" placeholder="<?= __('Email người nhận'); ?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= htmlspecialchars($subject_filter); ?>" name="subject_filter" placeholder="<?= __('Tiêu đề'); ?>">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i> <?= __('Tìm kiếm'); ?></button>
                                    <a class="btn btn-hero btn-sm btn-danger" href="<?= base_url_admin('email-queue'); ?>"><i class="fa fa-trash"></i> <?= __('Xóa bộ lọc'); ?></a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label"><?= __('Hiển thị'); ?> :</label>
                                    <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                        <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?= __('Người nhận'); ?></th>
                                        <th><?= __('Tiêu đề'); ?></th>
                                        <th><?= __('Nội dung'); ?></th>
                                        <th><?= __('Trạng thái'); ?></th>
                                        <th><?= __('Lần thử'); ?></th>
                                        <th><?= __('Thời gian tạo'); ?></th>
                                        <th><?= __('Thời gian gửi'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                        <tr>
                                            <td><?= $row['id']; ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['to_name'] ?? ''); ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($row['to_email']); ?></small>
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm content-preview" rows="1" readonly style="min-width: 150px; cursor: pointer;" onclick="showContentModal('<?= __('Tiêu đề'); ?>', this.value)"><?= htmlspecialchars($row['subject'] ?? ''); ?></textarea>
                                            </td>
                                            <td>
                                                <textarea class="form-control form-control-sm content-preview" rows="2" readonly style="min-width: 200px; cursor: pointer;" onclick="showContentModal('<?= __('Nội dung'); ?>', this.value)"><?= htmlspecialchars($row['body'] ?? ''); ?></textarea>
                                            </td>
                                            <td>
                                                <?php
                                                switch ($row['status']) {
                                                    case 'pending':
                                                        $status_class = 'warning';
                                                        $status_text = __('Đang chờ');
                                                        break;
                                                    case 'sent':
                                                        $status_class = 'success';
                                                        $status_text = __('Đã gửi');
                                                        break;
                                                    case 'failed':
                                                        $status_class = 'danger';
                                                        $status_text = __('Thất bại');
                                                        break;
                                                    default:
                                                        $status_class = 'secondary';
                                                        $status_text = $row['status'];
                                                }
                                                ?>
                                                <span class="badge bg-<?= $status_class; ?>-transparent"><?= $status_text; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark"><?= $row['attempts'] ?? 0; ?>/<?= $row['max_attempts'] ?? 3; ?></span>
                                            </td>
                                            <td>
                                                <small><?= $row['created_at'] ?? '-'; ?></small>
                                            </td>
                                            <td>
                                                <small><?= $row['sent_at'] ?? '-'; ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info"><?= __('Hiển thị'); ?> <?= $limit; ?> <?= __('trên tổng'); ?> <?= format_cash($totalDatatable); ?> <?= __('kết quả'); ?></p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal dọn dẹp Email Queue -->
<div class="modal fade" id="cleanupEmailQueueModal" tabindex="-1" aria-labelledby="cleanupEmailQueueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cleanupEmailQueueModalLabel">
                    <i class="ri-delete-bin-line text-danger me-2"></i><?= __('Dọn dẹp Email Queue'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning border-0">
                    <i class="ri-error-warning-line me-2"></i>
                    <?= __('Lưu ý: Thao tác này sẽ xóa vĩnh viễn các bản ghi và không thể hoàn tác.'); ?>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="cleanupAllEmailQueue">
                    <label class="form-check-label fw-medium text-danger" for="cleanupAllEmailQueue">
                        <i class="ri-delete-bin-line me-1"></i><?= __('Xóa toàn bộ từ trước đến giờ'); ?>
                    </label>
                </div>
                <div class="mb-3" id="cleanupDaysWrapperEmailQueue">
                    <label for="cleanupDaysEmailQueue" class="form-label fw-medium"><?= __('Xóa bản ghi cũ hơn'); ?></label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="cleanupDaysEmailQueue" value="30" min="1" max="365" placeholder="30">
                        <span class="input-group-text"><?= __('ngày'); ?></span>
                    </div>
                    <div class="form-text">
                        <i class="ri-information-line me-1"></i>
                        <?= __('Ví dụ: Nhập 30 sẽ xóa tất cả bản ghi cũ hơn 30 ngày trở về trước.'); ?>
                    </div>
                </div>
                <div id="cleanupPreviewEmailQueue" class="d-none">
                    <div class="alert alert-info-transparent border mb-0">
                        <i class="ri-file-list-3-line me-2"></i>
                        <span id="cleanupPreviewTextEmailQueue"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i><?= __('Hủy'); ?>
                </button>
                <button type="button" class="btn btn-danger" id="confirmCleanupBtnEmailQueue">
                    <i class="ri-delete-bin-line me-1"></i><?= __('Xóa bản ghi'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết nội dung -->
<div class="modal" id="contentDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contentDetailModalTitle">
                    <i class="ri-file-text-line me-2"></i><?= __('Chi tiết nội dung'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="contentDetailModalBody" class="p-3 bg-light rounded" style="white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i><?= __('Đóng'); ?>
                </button>
                <button type="button" class="btn btn-info" id="btnPreviewHtml" onclick="previewHtmlContent()">
                    <i class="ri-eye-line me-1"></i><?= __('Xem trước'); ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="copyContentToClipboard()">
                    <i class="ri-file-copy-line me-1"></i><?= __('Sao chép'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem trước HTML -->
<div class="modal" id="htmlPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white">
                    <i class="ri-mail-line me-2"></i><?= __('Xem trước Email'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="htmlPreviewFrame" style="width: 100%; height: 70vh; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i><?= __('Đóng'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var currentContentForPreview = '';

    function showContentModal(title, content) {
        currentContentForPreview = content;
        $('#contentDetailModalTitle').html('<i class="ri-file-text-line me-2"></i>' + title);
        $('#contentDetailModalBody').text(content);
        var isHtml = /<[a-z][\s\S]*>/i.test(content);
        $('#btnPreviewHtml').toggle(isHtml);
        var modal = new bootstrap.Modal(document.getElementById('contentDetailModal'));
        modal.show();
    }

    function previewHtmlContent() {
        var content = currentContentForPreview;
        var contentModal = bootstrap.Modal.getInstance(document.getElementById('contentDetailModal'));
        if (contentModal) contentModal.hide();
        var iframe = document.getElementById('htmlPreviewFrame');
        var doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(content);
        doc.close();
        var previewModal = new bootstrap.Modal(document.getElementById('htmlPreviewModal'));
        previewModal.show();
    }

    function copyContentToClipboard() {
        var content = $('#contentDetailModalBody').text();
        navigator.clipboard.writeText(content).then(function() {
            Swal.fire({ icon: 'success', title: '<?= __('Thành công'); ?>', text: '<?= __('Đã sao chép nội dung vào clipboard'); ?>', timer: 1500, showConfirmButton: false });
        }).catch(function() {
            Swal.fire({ icon: 'error', title: '<?= __('Lỗi'); ?>', text: '<?= __('Không thể sao chép nội dung'); ?>' });
        });
    }
</script>

<script>
    $(document).ready(function() {
        var cleanupModal = document.getElementById('cleanupEmailQueueModal');
        var $cleanupDays = $('#cleanupDaysEmailQueue');
        var $cleanupPreview = $('#cleanupPreviewEmailQueue');
        var $cleanupPreviewText = $('#cleanupPreviewTextEmailQueue');
        var $confirmBtn = $('#confirmCleanupBtnEmailQueue');
        var $cleanupAll = $('#cleanupAllEmailQueue');
        var $daysWrapper = $('#cleanupDaysWrapperEmailQueue');
        var previewTimeout = null;

        // Toggle xóa toàn bộ
        $cleanupAll.on('change', function() {
            if (this.checked) {
                $daysWrapper.slideUp(200);
                updatePreview();
            } else {
                $daysWrapper.slideDown(200);
                updatePreview();
            }
        });

        function updatePreview() {
            var isAll = $cleanupAll.is(':checked');
            var days = isAll ? 0 : (parseInt($cleanupDays.val()) || 0);
            if (!isAll && days < 1) { $cleanupPreview.addClass('d-none'); return; }
            $.ajax({
                url: '<?= BASE_URL('ajaxs/admin/view.php'); ?>',
                type: 'POST',
                dataType: 'json',
                data: { action: 'previewCleanupEmailQueue', days: days },
                success: function(resp) {
                    if (resp.status === 'success') {
                        var label = isAll ? '<?= __('Sẽ xóa toàn bộ'); ?> ' : '<?= __('Sẽ xóa'); ?> ';
                        $cleanupPreviewText.text(label + resp.count + ' <?= __('bản ghi'); ?>');
                        $cleanupPreview.removeClass('d-none');
                    } else { $cleanupPreview.addClass('d-none'); }
                }
            });
        }

        $cleanupDays.on('input', function() { clearTimeout(previewTimeout); previewTimeout = setTimeout(updatePreview, 500); });

        if (cleanupModal) {
            cleanupModal.addEventListener('shown.bs.modal', function() {
                $cleanupAll.prop('checked', false);
                $daysWrapper.show();
                $cleanupDays.val(30).focus();
                updatePreview();
            });
            cleanupModal.addEventListener('hidden.bs.modal', function() {
                $cleanupPreview.addClass('d-none');
                $confirmBtn.prop('disabled', false).html('<i class="ri-delete-bin-line me-1"></i><?= __('Xóa bản ghi'); ?>');
            });
        }

        $confirmBtn.on('click', function() {
            var isAll = $cleanupAll.is(':checked');
            var days = isAll ? 0 : (parseInt($cleanupDays.val()) || 0);
            if (!isAll && days < 1) { Swal.fire({ icon: 'warning', title: '<?= __('Cảnh báo'); ?>', text: '<?= __('Vui lòng nhập số ngày hợp lệ'); ?>' }); return; }

            var confirmText = isAll
                ? '<?= __('Bạn có chắc chắn muốn xóa TOÀN BỘ bản ghi Email Queue?'); ?>'
                : '<?= __('Bạn có chắc chắn muốn xóa tất cả bản ghi cũ hơn'); ?> ' + days + ' <?= __('ngày?'); ?>';

            Swal.fire({
                icon: 'warning', title: '<?= __('Xác nhận xóa'); ?>',
                text: confirmText,
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d',
                confirmButtonText: '<?= __('Xóa'); ?>', cancelButtonText: '<?= __('Hủy'); ?>'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $confirmBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span><?= __('Đang xóa...'); ?>');
                    $.ajax({
                        url: '<?= BASE_URL('ajaxs/admin/remove.php'); ?>',
                        type: 'POST', dataType: 'json',
                        data: { action: 'cleanupEmailQueue', days: days },
                        success: function(resp) {
                            if (resp.status === 'success') {
                                Swal.fire({ icon: 'success', title: '<?= __('Thành công'); ?>', text: resp.msg, timer: 2000, showConfirmButton: false }).then(function() { location.reload(); });
                            } else {
                                Swal.fire({ icon: 'error', title: '<?= __('Lỗi'); ?>', text: resp.msg });
                                $confirmBtn.prop('disabled', false).html('<i class="ri-delete-bin-line me-1"></i><?= __('Xóa bản ghi'); ?>');
                            }
                        },
                        error: function() {
                            Swal.fire({ icon: 'error', title: '<?= __('Lỗi'); ?>', text: '<?= __('Không thể kết nối đến server'); ?>' });
                            $confirmBtn.prop('disabled', false).html('<i class="ri-delete-bin-line me-1"></i><?= __('Xóa bản ghi'); ?>');
                        }
                    });
                }
            });
        });
    });
</script>

<?php
require_once(__DIR__ . '/footer.php');
?>
