<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Telegram Logs',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co'
];
$body['header'] = '
 
';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
// Kiểm tra quyền
if (checkPermission($getUser['admin'], 'view_telegram_logs') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

// Thiết lập giới hạn phân trang
if (isset($_GET['limit'])) {
    $limit = intval(check_string($_GET['limit']));
} else {
    $limit = 10;
}
if (isset($_GET['page'])) {
    $page = intval(check_string($_GET['page']));
} else {
    $page = 1;
}
$from = ($page - 1) * $limit;

// Xây dựng câu WHERE cho bộ lọc
$where = " `id` > 0 ";

// Lấy dữ liệu từ GET để lọc
$usernameParam = '';
$commandParam  = '';
$typeParam     = '';
if (!empty($_GET['username'])) {
    $usernameParam = check_string($_GET['username']);
    $where .= " AND `username` LIKE '%$usernameParam%' ";
}
if (!empty($_GET['command'])) {
    $commandParam = check_string($_GET['command']);
    $where .= " AND `command` LIKE '%$commandParam%' ";
}
if (!empty($_GET['type'])) {
    $typeParam = check_string($_GET['type']);
    $where .= " AND `type` = '$typeParam' ";
}

// Lấy danh sách logs
$listDatatable = $CMSNT->get_list(" 
    SELECT * 
    FROM `telegram_logs` 
    WHERE $where 
    ORDER BY `id` DESC 
    LIMIT $from, $limit 
");
$totalDatatable = $CMSNT->num_rows("
    SELECT * 
    FROM `telegram_logs` 
    WHERE $where
");
$urlDatatable = pagination(
    base_url_admin("telegram-logs&limit=$limit&username=$usernameParam&command=$commandParam&type=$typeParam&"),
    $from,
    $totalDatatable,
    $limit
);
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-clock-rotate-left"></i> Nhật ký sử dụng Bot quản lý hệ thống
            </h1>
            <?php if (checkPermission($getUser['admin'], 'delete_telegram_logs') == true): ?>
                <div>
                    <button class="btn btn-danger btn-sm" onclick="cleanupTelegramLogs()">
                        <i class="fa-solid fa-broom"></i> <?= __('Dọn dẹp nhật ký Telegram'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            TELEGRAM LOGS
                        </div>
                    </div>
                    <div class="card-body">

                        <!-- FORM TÌM KIẾM -->
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <input type="hidden" name="module" value="admin">
                            <input type="hidden" name="action" value="telegram-logs">

                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $usernameParam; ?>"
                                        name="username" placeholder="Username">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= $commandParam; ?>"
                                        name="command" placeholder="Command">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select name="type" class="form-select form-select-sm">
                                        <option value="">-- Loại --</option>
                                        <option <?= $typeParam == 'message' ? 'selected' : ''; ?> value="message">Message
                                        </option>
                                        <option <?= $typeParam == 'callback' ? 'selected' : ''; ?> value="callback">Callback
                                        </option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary">
                                        <i class="fa fa-search"></i> Tìm kiếm
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?= base_url_admin('telegram-logs'); ?>">
                                        <i class="fa fa-trash"></i> Clear filter
                                    </a>
                                </div>
                            </div>

                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                        <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                        <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                        <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1.000</option>
                                        <option <?= $limit == 5000 ? 'selected' : ''; ?> value="5000">5.000</option>
                                        <option <?= $limit == 10000 ? 'selected' : ''; ?> value="10000">10.000</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <!-- END FORM TÌM KIẾM -->

                        <!-- BẢNG KẾT QUẢ -->
                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="15%">Username Telegram</th>
                                        <th width="15%">Command</th>
                                        <th>Params</th>
                                        <th width="10%">Type</th>
                                        <th width="15%">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($listDatatable): ?>
                                        <?php foreach ($listDatatable as $row): ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td>
                                                    <b>
                                                        <a href="https://t.me/<?= htmlspecialchars($row['username']); ?>"
                                                            target="_blank" class="text-primary">
                                                            @<?= htmlspecialchars($row['username']); ?>
                                                        </a>
                                                    </b>
                                                </td>

                                                <td>/<?= htmlspecialchars($row['command']); ?></td>
                                                <td>
                                                    <code><?= htmlspecialchars($row['params']); ?></code>
                                                </td>
                                                <td>
                                                    <?php if ($row['type'] == 'message'): ?>
                                                        <span class="badge bg-primary"><?= htmlspecialchars($row['type']); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info"><?= htmlspecialchars($row['type']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark" data-bs-toggle="tooltip"
                                                        title="<?= timeAgo(strtotime($row['time'])); ?>">
                                                        <?= $row['time']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Không có dữ liệu</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- PHÂN TRANG -->
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">
                                    Hiển thị <?= $limit; ?> / <?= format_cash($totalDatatable); ?> kết quả
                                </p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                            </div>
                        </div>
                        <!-- END PHÂN TRANG -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
require_once(__DIR__ . '/footer.php');
?>

<?php if (checkPermission($getUser['admin'], 'delete_logs') == true): ?>
    <script>
        // Hàm dọn dẹp nhật ký Telegram
        function cleanupTelegramLogs() {
            Swal.fire({
                title: '<?= __('Dọn dẹp nhật ký Telegram'); ?>',
                html: '<div class="text-start">' +
                    '<p class="mb-3"><?= __('Nhập số ngày cần giữ lại nhật ký. Tất cả nhật ký từ số ngày trở lên sẽ bị xóa.'); ?></p>' +
                    '<p class="text-muted small mb-3"><?= __('Ví dụ: Nhập 30 sẽ xóa tất cả nhật ký từ 30 ngày trở lên'); ?></p>' +
                    '<input type="number" id="daysToKeep" class="form-control" min="1" placeholder="<?= __('Số ngày cần giữ lại'); ?>" value="30">' +
                    '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<?= __('Xóa'); ?>',
                cancelButtonText: '<?= __('Hủy'); ?>',
                inputValidator: (value) => {
                    if (!value || value < 1) {
                        return '<?= __('Vui lòng nhập số ngày hợp lệ (tối thiểu 1 ngày)'); ?>'
                    }
                },
                preConfirm: () => {
                    const days = document.getElementById('daysToKeep').value;
                    if (!days || days < 1) {
                        Swal.showValidationMessage('<?= __('Vui lòng nhập số ngày hợp lệ (tối thiểu 1 ngày)'); ?>');
                        return false;
                    }
                    return days;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const daysToKeep = result.value;

                    // Xác nhận lần nữa
                    Swal.fire({
                        title: '<?= __('Xác nhận xóa'); ?>',
                        text: '<?= __('Bạn có chắc chắn muốn xóa tất cả nhật ký Telegram từ'); ?> ' + daysToKeep + ' <?= __('ngày trở lên? Hành động này không thể hoàn tác!'); ?>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<?= __('Xác nhận xóa'); ?>',
                        cancelButtonText: '<?= __('Hủy'); ?>'
                    }).then((confirmResult) => {
                        if (confirmResult.isConfirmed) {
                            // Hiển thị loading
                            Swal.fire({
                                title: '<?= __('Đang xử lý'); ?>...',
                                text: '<?= __('Vui lòng đợi'); ?>...',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            $.ajax({
                                url: '<?= base_url('ajaxs/admin/remove.php'); ?>',
                                method: 'POST',
                                dataType: 'JSON',
                                data: {
                                    action: 'cleanupTelegramLogs',
                                    token: '<?= $getUser['token']; ?>',
                                    days_to_keep: daysToKeep
                                },
                                success: function(response) {
                                    if (response.status === 'success') {
                                        Swal.fire({
                                            title: '<?= __('Thành công'); ?>',
                                            text: response.msg,
                                            icon: 'success',
                                            confirmButtonText: '<?= __('OK'); ?>'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: '<?= __('Lỗi'); ?>',
                                            text: response.msg,
                                            icon: 'error',
                                            confirmButtonText: '<?= __('OK'); ?>'
                                        });
                                    }
                                },
                                error: function() {
                                    Swal.fire({
                                        title: '<?= __('Lỗi'); ?>',
                                        text: '<?= __('Có lỗi xảy ra khi xóa nhật ký Telegram'); ?>',
                                        icon: 'error',
                                        confirmButtonText: '<?= __('OK'); ?>'
                                    });
                                }
                            });
                        }
                    });
                }
            });
        }
    </script>
<?php endif; ?>