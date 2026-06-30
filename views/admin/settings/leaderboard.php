<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>

<?php
// Xử lý lưu cài đặt Bảng xếp hạng
if (isset($_POST['SaveLeaderboardSettings'])) {
    // Kiểm tra quyền admin chỉnh sửa cài đặt
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }

    // Chặn thay đổi trên site demo
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }

    // Ghi log hành động admin
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Thay đổi cài đặt Bảng xếp hạng')
    ]);

    // Lưu trạng thái ON/OFF
    $leaderboard_status = validate_int($_POST['leaderboard_status'] ?? 0, 0, 1);
    if ($leaderboard_status === false) $leaderboard_status = 0;
    $CMSNT->update("settings", ['value' => $leaderboard_status], "`name` = ?", ['leaderboard_status']);

    // Lưu các khoảng thời gian được chọn (checkbox → CSV)
    $allowed_periods = ['daily', 'weekly', 'monthly', 'all_time'];
    $selected_periods = [];
    if (!empty($_POST['leaderboard_periods']) && is_array($_POST['leaderboard_periods'])) {
        foreach ($_POST['leaderboard_periods'] as $period) {
            // Chỉ cho phép giá trị trong whitelist
            if (in_array($period, $allowed_periods)) {
                $selected_periods[] = $period;
            }
        }
    }
    // Mặc định chọn tất cả nếu không chọn gì
    $periods_csv = !empty($selected_periods) ? implode(',', $selected_periods) : implode(',', $allowed_periods);
    $CMSNT->update("settings", ['value' => $periods_csv], "`name` = ?", ['leaderboard_periods']);

    // Lưu số lượng user hiển thị trong BXH
    $leaderboard_limit = validate_int($_POST['leaderboard_limit'] ?? 10, 5, 50);
    if ($leaderboard_limit === false) $leaderboard_limit = 10;
    $CMSNT->update("settings", ['value' => $leaderboard_limit], "`name` = ?", ['leaderboard_limit']);

    // Gửi thông báo Telegram cho admin
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Thay đổi cài đặt Bảng xếp hạng'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}

// Lấy giá trị hiện tại của cấu hình BXH
$current_periods = explode(',', $CMSNT->site('leaderboard_periods') ?: 'daily,weekly,monthly,all_time');
?>

<div class="tab-pane text-muted show active" id="leaderboard" role="tabpanel">
    <h4><i class="fa-solid fa-ranking-star me-2"></i><?= __('Cấu hình Bảng xếp hạng'); ?></h4>
    <p class="text-muted mb-4"><?= __('Widget Bảng xếp hạng hiển thị top người dùng mua hàng nhiều nhất trên trang khách.'); ?></p>

    <form action="" method="POST">
        <div class="row mb-4">
            <!-- Cột trái: Trạng thái & Thời gian -->
            <div class="col-md-6">
                <div class="card border mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fa-solid fa-toggle-on me-2"></i><?= __('Trạng thái Widget'); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><?= __('Hiển thị Widget BXH ngoài trang khách'); ?></label>
                            <select class="form-control" name="leaderboard_status" id="leaderboard_status">
                                <option value="1" <?= $CMSNT->site('leaderboard_status') == 1 ? 'selected' : ''; ?>>
                                    ✅ ON - <?= __('Hiển thị'); ?>
                                </option>
                                <option value="0" <?= $CMSNT->site('leaderboard_status') == 0 ? 'selected' : ''; ?>>
                                    ❌ OFF - <?= __('Ẩn'); ?>
                                </option>
                            </select>
                            <small class="text-muted"><?= __('Khi bật, nút BXH sẽ xuất hiện ở góc phải màn hình trên nút "Back to Top".'); ?></small>
                        </div>
                    </div>
                </div>

                <div class="card border mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fa-solid fa-clock me-2"></i><?= __('Khoảng thời gian BXH'); ?></h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3"><?= __('Chọn các tab thời gian sẽ hiển thị trong modal BXH (chọn 1 hoặc nhiều):'); ?></p>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="leaderboard_periods[]"
                                value="daily" id="period_daily"
                                <?= in_array('daily', $current_periods) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="period_daily">
                                <i class="fa-solid fa-calendar-day me-1 text-info"></i> <?= __('Theo Ngày'); ?>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="leaderboard_periods[]"
                                value="weekly" id="period_weekly"
                                <?= in_array('weekly', $current_periods) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="period_weekly">
                                <i class="fa-solid fa-calendar-week me-1 text-primary"></i> <?= __('Theo Tuần'); ?>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="leaderboard_periods[]"
                                value="monthly" id="period_monthly"
                                <?= in_array('monthly', $current_periods) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="period_monthly">
                                <i class="fa-solid fa-calendar-alt me-1 text-success"></i> <?= __('Theo Tháng'); ?>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="leaderboard_periods[]"
                                value="all_time" id="period_all_time"
                                <?= in_array('all_time', $current_periods) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="period_all_time">
                                <i class="fa-solid fa-infinity me-1 text-warning"></i> <?= __('Toàn thời gian'); ?>
                            </label>
                        </div>

                        <small class="text-muted d-block mt-2">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            <?= __('Nếu không chọn gì, mặc định sẽ hiển thị tất cả các tab.'); ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Cấu hình khác -->
            <div class="col-md-6">
                <div class="card border mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fa-solid fa-sliders me-2"></i><?= __('Cấu hình hiển thị'); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><?= __('Số lượng hiển thị (Top N)'); ?></label>
                            <input type="number" class="form-control" name="leaderboard_limit"
                                value="<?= $CMSNT->site('leaderboard_limit') ?: 10; ?>"
                                min="5" max="50" step="1">
                            <small class="text-muted"><?= __('Số lượng user hiển thị trong BXH (tối thiểu 5, tối đa 50).'); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Preview BXH -->
                <div class="card border mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fa-solid fa-eye me-2"></i><?= __('Xem trước Widget'); ?></h6>
                    </div>
                    <div class="card-body text-center">
                        <div style="position:relative; display:inline-block;">
                            <!-- Nút BXH preview -->
                            <div style="width:48px; height:48px; border-radius:50%; background:linear-gradient(135deg, #FFD700, #FFA500); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; box-shadow: 0 4px 15px rgba(255,165,0,0.4); cursor:pointer;">
                                <i class="fa-solid fa-trophy" style="color:#fff; font-size:20px;"></i>
                            </div>
                            <small class="text-muted"><?= __('Nút sẽ nằm phía trên nút "Back to Top"'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" name="SaveLeaderboardSettings" class="btn btn-primary w-100 mb-3">
            <i class="fa fa-fw fa-save me-1"></i> <?= __('Lưu cấu hình'); ?>
        </button>
    </form>

    <!-- ========== SECTION XEM BXH TRỰC TIẾP ========== -->
    <div class="card border mt-4">
        <div class="card-header bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0">
                <i class="fa-solid fa-ranking-star me-2 text-warning"></i><?= __('Xem Bảng xếp hạng'); ?>
                <span class="text-muted fw-normal ms-2" id="admin-lb-period-label" style="font-size:13px;"></span>
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadAdminLeaderboard(currentAdminLBPeriod)">
                <i class="fa-solid fa-rotate me-1"></i><?= __('Làm mới'); ?>
            </button>
        </div>
        <div class="card-body p-0">
            <!-- Tabs chọn khoảng thời gian -->
            <div class="admin-lb-tabs border-bottom" style="overflow-x:auto; white-space:nowrap; padding:12px 16px 0;">
                <button type="button" class="btn btn-sm btn-outline-primary me-1 mb-2 admin-lb-tab active" data-period="today">
                    <i class="fa-solid fa-calendar-day me-1"></i><?= __('Hôm nay'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-2 admin-lb-tab" data-period="yesterday">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i><?= __('Hôm qua'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-outline-info me-1 mb-2 admin-lb-tab" data-period="this_week">
                    <i class="fa-solid fa-calendar-week me-1"></i><?= __('Tuần này'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning me-1 mb-2 admin-lb-tab" data-period="last_week">
                    <i class="fa-solid fa-calendar-minus me-1"></i><?= __('Tuần trước'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success me-1 mb-2 admin-lb-tab" data-period="this_month">
                    <i class="fa-solid fa-calendar-alt me-1"></i><?= __('Tháng này'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger me-1 mb-2 admin-lb-tab" data-period="last_month">
                    <i class="fa-solid fa-calendar-xmark me-1"></i><?= __('Tháng trước'); ?>
                </button>
                <button type="button" class="btn btn-sm btn-outline-dark me-1 mb-2 admin-lb-tab" data-period="all_time">
                    <i class="fa-solid fa-infinity me-1"></i><?= __('Toàn thời gian'); ?>
                </button>
            </div>

            <!-- Loading state -->
            <div id="admin-lb-loading" class="text-center py-5" style="display:none;">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-muted mt-2 mb-0"><?= __('Đang tải dữ liệu BXH...'); ?></p>
            </div>

            <!-- Bảng dữ liệu BXH -->
            <div id="admin-lb-content" class="table-responsive" style="display:none;">
                <table class="table table-hover table-striped mb-0" id="admin-lb-table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:60px;">#</th>
                            <th><?= __('Username'); ?></th>
                            <th><?= __('Họ tên'); ?></th>
                            <th><?= __('Email'); ?></th>
                            <th class="text-center"><?= __('Số đơn'); ?></th>
                            <th class="text-end"><?= __('Tổng chi tiêu'); ?></th>
                            <th class="text-center" style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="admin-lb-tbody"></tbody>
                </table>
            </div>

            <!-- Empty state -->
            <div id="admin-lb-empty" class="text-center py-5" style="display:none;">
                <i class="fa-solid fa-chart-simple fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted mb-0"><?= __('Chưa có dữ liệu xếp hạng trong khoảng thời gian này.'); ?></p>
            </div>
        </div>
    </div>

    <style>
        /* ===== ADMIN LEADERBOARD TABS ===== */
        .admin-lb-tab.active {
            /* Tab đang chọn nổi bật hơn bằng filled style */
            color: #fff !important;
        }

        .admin-lb-tab[data-period="today"].active {
            background-color: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
        }

        .admin-lb-tab[data-period="yesterday"].active {
            background-color: var(--bs-secondary) !important;
            border-color: var(--bs-secondary) !important;
        }

        .admin-lb-tab[data-period="this_week"].active {
            background-color: var(--bs-info) !important;
            border-color: var(--bs-info) !important;
        }

        .admin-lb-tab[data-period="last_week"].active {
            background-color: var(--bs-warning) !important;
            border-color: var(--bs-warning) !important;
            color: #000 !important;
        }

        .admin-lb-tab[data-period="this_month"].active {
            background-color: var(--bs-success) !important;
            border-color: var(--bs-success) !important;
        }

        .admin-lb-tab[data-period="last_month"].active {
            background-color: var(--bs-danger) !important;
            border-color: var(--bs-danger) !important;
        }

        .admin-lb-tab[data-period="all_time"].active {
            background-color: var(--bs-dark) !important;
            border-color: var(--bs-dark) !important;
        }

        /* Hàng top 3 nổi bật */
        #admin-lb-tbody .admin-lb-rank-1 {
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.15), transparent) !important;
        }

        #admin-lb-tbody .admin-lb-rank-2 {
            background: linear-gradient(90deg, rgba(192, 192, 192, 0.15), transparent) !important;
        }

        #admin-lb-tbody .admin-lb-rank-3 {
            background: linear-gradient(90deg, rgba(205, 127, 50, 0.15), transparent) !important;
        }

        /* Badge rank */
        .admin-lb-rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 14px;
        }

        .admin-lb-rank-badge.gold {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #fff;
        }

        .admin-lb-rank-badge.silver {
            background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
            color: #fff;
        }

        .admin-lb-rank-badge.bronze {
            background: linear-gradient(135deg, #CD7F32, #A0522D);
            color: #fff;
        }

        .admin-lb-rank-badge.normal {
            background: #f0f0f0;
            color: #666;
        }
    </style>

    <script>
        // Biến lưu period đang chọn
        var currentAdminLBPeriod = 'today';

        /**
         * Tải dữ liệu BXH admin theo khoảng thời gian
         * Gọi AJAX đến endpoint get_admin_leaderboard với period tương ứng
         */
        function loadAdminLeaderboard(period) {
            currentAdminLBPeriod = period;

            // Cập nhật UI tabs - đánh dấu tab đang active
            document.querySelectorAll('.admin-lb-tab').forEach(function(t) {
                t.classList.remove('active');
            });
            var activeTab = document.querySelector('.admin-lb-tab[data-period="' + period + '"]');
            if (activeTab) activeTab.classList.add('active');

            // Hiển thị loading, ẩn content và empty
            $('#admin-lb-loading').show();
            $('#admin-lb-content').hide();
            $('#admin-lb-empty').hide();

            $.ajax({
                url: '<?= base_url("ajaxs/admin/view.php"); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_admin_leaderboard',
                    token: '<?= $getUser['token']; ?>',
                    period: period
                },
                success: function(res) {
                    $('#admin-lb-loading').hide();

                    if (res.status === 'success') {
                        // Cập nhật label khoảng thời gian
                        $('#admin-lb-period-label').text('(' + res.period_label + ')');

                        if (res.data && res.data.length > 0) {
                            // Render bảng dữ liệu BXH
                            var tbody = '';
                            res.data.forEach(function(item) {
                                // Xác định class cho hàng top 3
                                var rowClass = item.rank <= 3 ? ' admin-lb-rank-' + item.rank : '';

                                // Render badge rank với icon đặc biệt cho top 3
                                var rankBadge = '';
                                if (item.rank === 1) {
                                    rankBadge = '<span class="admin-lb-rank-badge gold"><i class="fa-solid fa-crown"></i></span>';
                                } else if (item.rank === 2) {
                                    rankBadge = '<span class="admin-lb-rank-badge silver"><i class="fa-solid fa-medal"></i></span>';
                                } else if (item.rank === 3) {
                                    rankBadge = '<span class="admin-lb-rank-badge bronze"><i class="fa-solid fa-award"></i></span>';
                                } else {
                                    rankBadge = '<span class="admin-lb-rank-badge normal">' + item.rank + '</span>';
                                }

                                tbody += '<tr class="' + rowClass + '">';
                                tbody += '<td class="text-center align-middle">' + rankBadge + '</td>';
                                tbody += '<td class="align-middle"><strong>' + escapeHtmlAdmin(item.username) + '</strong></td>';
                                tbody += '<td class="align-middle">' + escapeHtmlAdmin(item.fullname) + '</td>';
                                tbody += '<td class="align-middle"><small class="text-muted">' + escapeHtmlAdmin(item.email) + '</small></td>';
                                tbody += '<td class="text-center align-middle"><span class="badge bg-info-transparent">' + item.total_orders + '</span></td>';
                                tbody += '<td class="text-end align-middle"><strong class="text-success">' + item.total_spent + '</strong></td>';
                                tbody += '<td class="text-center align-middle">';
                                tbody += '<a href="<?= base_url_admin("user-edit&id="); ?>' + item.id + '" class="btn btn-sm btn-icon btn-outline-primary" title="<?= __("Xem user"); ?>">';
                                tbody += '<i class="fa-solid fa-eye"></i></a>';
                                tbody += '</td>';
                                tbody += '</tr>';
                            });

                            $('#admin-lb-tbody').html(tbody);
                            $('#admin-lb-content').show();
                        } else {
                            // Không có dữ liệu
                            $('#admin-lb-empty').show();
                        }
                    } else {
                        // Lỗi từ server
                        $('#admin-lb-empty').html(
                            '<i class="fa-solid fa-exclamation-triangle fa-3x text-danger mb-3 d-block"></i>' +
                            '<p class="text-danger mb-0">' + (res.msg || '<?= __("Có lỗi xảy ra"); ?>') + '</p>'
                        ).show();
                    }
                },
                error: function() {
                    $('#admin-lb-loading').hide();
                    $('#admin-lb-empty').html(
                        '<i class="fa-solid fa-exclamation-triangle fa-3x text-danger mb-3 d-block"></i>' +
                        '<p class="text-danger mb-0"><?= __("Không thể tải dữ liệu BXH."); ?></p>'
                    ).show();
                }
            });
        }

        /**
         * Escape HTML để chống XSS khi render dữ liệu vào DOM
         */
        function escapeHtmlAdmin(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        // Gắn sự kiện click cho các tab khoảng thời gian
        document.querySelectorAll('.admin-lb-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                loadAdminLeaderboard(this.dataset.period);
            });
        });

        // Tải BXH mặc định (hôm nay) khi trang load xong
        $(document).ready(function() {
            loadAdminLeaderboard('today');
        });
    </script>
</div>