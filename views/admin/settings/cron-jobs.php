<?php if (!defined('IN_SITE')) { die('The Request Not Found'); } ?>

                                    <div class="tab-pane text-muted show active" id="cron-jobs" role="tabpanel">
                                        <h4><?= __('Cron Jobs'); ?></h4>
                                        <div class="alert alert-info border-0 mb-4">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="ri-information-line fs-16"></i>
                                                </div>
                                                <div>
                                                    <strong><?= __('Hướng dẫn:'); ?></strong> <?= __('Thiết lập các Cron Jobs sau trên hosting/server để hệ thống hoạt động tự động. Nhấn nút Copy để sao chép link.'); ?>
                                                    <br><small><?= __('Tham khảo hướng dẫn chi tiết tại:'); ?> <a href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/" target="_blank" class="text-primary"><?= __('CMSNT Help'); ?></a></small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12">
                                                <div class="card custom-card">
                                                    <div class="card-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th width="25%"><?= __('Tên Cron Job'); ?></th>
                                                                        <th width="35%"><?= __('Đường dẫn'); ?></th>
                                                                        <th width="15%" class="text-center"><?= __('Thời gian khuyến nghị'); ?></th>
                                                                        <th width="15%"><?= __('Lần chạy cuối'); ?></th>
                                                                        <th width="10%" class="text-center"><?= __('Thao tác'); ?></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    // Danh sách tất cả các setting cho cron jobs cần kiểm tra
                                                                    $cronSettings = [
                                                                        'check_time_cron_cron',
                                                                        'check_time_cron_bank',
                                                                        'check_time_cron_momo',
                                                                        'check_time_cron_thesieure',
                                                                        'check_time_cron_task',
                                                                        'check_time_cron_sending_email',
                                                                        'check_time_cron_email_queue',
                                                                        'check_time_cron_telegram_queue',
                                                                        'time_cron_checklive_gmail',
                                                                        'time_cron_checklive_hotmail',
                                                                        'time_cron_checklive_clone',
                                                                        'time_cron_checklive_instagram',
                                                                        'check_time_cron_gmail',
                                                                        'check_time_cron_hotmail',
                                                                        'check_time_cron_clone',
                                                                        'check_time_cron_instagram',
                                                                        'time_cron_checklive_tiktok',
                                                                        'check_time_cron_tiktok'
                                                                    ];

                                                                    // Kiểm tra và tạo các setting nếu chưa tồn tại
                                                                    foreach ($cronSettings as $setting) {
                                                                        if ($CMSNT->num_rows_safe(" SELECT * FROM `settings` WHERE `name` = ? ", [$setting]) == 0) {
                                                                            $CMSNT->insert("settings", [
                                                                                'name'  => $setting,
                                                                                'value' => '0'
                                                                            ]);
                                                                        }
                                                                    }

                                                                    // Nhóm cron jobs theo loại
                                                                    $cronGroups = [
                                                                        'general' => [
                                                                            'title' => __('Cron Job Chung'),
                                                                            'jobs' => [
                                                                                [
                                                                                    'name' => __('Cron Job Chính'),
                                                                                    'description' => __('Bắt buộc CRON để hệ thống xử lý tự động'),
                                                                                    'path' => 'cron/cron.php',
                                                                                    'recommended_time' => __('5 phút'),
                                                                                    'setting_name' => 'check_time_cron_cron'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Cron Job Bank'),
                                                                                    'description' => __('Xử lý nạp tiền tự động qua ngân hàng'),
                                                                                    'path' => 'cron/bank.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'check_time_cron_bank'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Cron Job MOMO'),
                                                                                    'description' => __('Xử lý nạp tiền tự động qua MOMO'),
                                                                                    'path' => 'cron/momo.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'check_time_cron_momo'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Cron Job Thesieure'),
                                                                                    'description' => __('Xử lý nạp tiền tự động qua Thesieure'),
                                                                                    'path' => 'cron/thesieure.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'check_time_cron_thesieure'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Task Automation'),
                                                                                    'description' => __('Xử lý các tác vụ tự động'),
                                                                                    'path' => 'cron/task.php',
                                                                                    'recommended_time' => __('5 phút'),
                                                                                    'setting_name' => 'check_time_cron_task'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Gửi Email'),
                                                                                    'description' => __('Xử lý gửi email hàng loạt'),
                                                                                    'path' => 'cron/sending_email.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'check_time_cron_sending_email'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Email Queue'),
                                                                                    'description' => __('Xử lý hàng đợi email (đơn hàng, cảnh báo đăng nhập...)'),
                                                                                    'path' => 'cron/process_email_queue.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'check_time_cron_email_queue'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Telegram Queue'),
                                                                                    'description' => __('Xử lý hàng đợi gửi thông báo Telegram (đơn hàng, nạp tiền...)'),
                                                                                    'path' => 'cron/process_telegram_queue.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'check_time_cron_telegram_queue'
                                                                                ]
                                                                            ]
                                                                        ],
                                                                        'checklive' => [
                                                                            'title' => __('Cron Job Check Live'),
                                                                            'jobs' => [
                                                                                [
                                                                                    'name' => __('Check live Gmail'),
                                                                                    'description' => __('Kiểm tra tài khoản Gmail còn sống hay đã chết'),
                                                                                    'path' => 'cron/checklive/gmail.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'time_cron_checklive_gmail'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Check live Hotmail'),
                                                                                    'description' => __('Kiểm tra tài khoản Hotmail còn sống hay đã chết'),
                                                                                    'path' => 'cron/checklive/hotmail.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'time_cron_checklive_hotmail'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Check live Clone'),
                                                                                    'description' => __('Kiểm tra tài khoản Clone còn sống hay đã chết'),
                                                                                    'path' => 'cron/checklive/clone.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'time_cron_checklive_clone'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Check live Instagram'),
                                                                                    'description' => __('Kiểm tra tài khoản Instagram còn sống hay đã chết'),
                                                                                    'path' => 'cron/checklive/instagram.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'time_cron_checklive_instagram'
                                                                                ],
                                                                                [
                                                                                    'name' => __('Check live Tiktok'),
                                                                                    'description' => __('Kiểm tra tài khoản Tiktok còn sống hay đã chết'),
                                                                                    'path' => 'cron/checklive/tiktok.php',
                                                                                    'recommended_time' => __('1 phút'),
                                                                                    'setting_name' => 'time_cron_checklive_tiktok'
                                                                                ]
                                                                            ]
                                                                        ],
                                                                        'suppliers' => [
                                                                            'title' => __('Cron Job Suppliers API'),
                                                                            'jobs' => []
                                                                        ]
                                                                    ];

                                                                    // Lấy danh sách tất cả các file cron suppliers
                                                                    $supplierFiles = glob(__DIR__ . '/../../cron/suppliers/*.php');
                                                                    foreach ($supplierFiles as $file) {
                                                                        $fileName = basename($file);
                                                                        if ($fileName == 'index.html') continue;

                                                                        $supplierKey = str_replace('.php', '', $fileName);
                                                                        $settingName = 'time_cron_suppliers_' . $supplierKey;


                                                                        // Lấy tên từ database nếu có
                                                                        $supplierName = strtoupper($supplierKey);

                                                                        $cronGroups['suppliers']['jobs'][] = [
                                                                            'name' => strtoupper($supplierKey),
                                                                            'description' => $supplierName,
                                                                            'path' => 'cron/suppliers/' . $fileName,
                                                                            'recommended_time' => __('1-5 phút'),
                                                                            'setting_name' => $settingName
                                                                        ];
                                                                    }

                                                                    $globalIndex = 0;
                                                                    foreach ($cronGroups as $groupKey => $group):
                                                                        if (empty($group['jobs'])) continue;
                                                                    ?>
                                                                        <!-- Header nhóm -->
                                                                        <tr>
                                                                            <td colspan="5" class="bg-primary text-white">
                                                                                <strong><i class="ri-folder-line"></i> <?= $group['title']; ?></strong>
                                                                            </td>
                                                                        </tr>
                                                                        <?php
                                                                        foreach ($group['jobs'] as $job):
                                                                            $cronUrl = base_url($job['path'] . '?key=' . $CMSNT->site('key_cron_job'));
                                                                            $lastRun = $CMSNT->site($job['setting_name']);
                                                                            $lastRunFormatted = $lastRun && $lastRun > 0 ? timeAgo($lastRun) : __('Chưa chạy');

                                                                            // Kiểm tra cron job chạy trong 5 phút gần nhất (300 giây)
                                                                            $isActive = $lastRun && $lastRun > 0 && (time() - $lastRun) <= 300;
                                                                            $bgColor = $isActive ? 'rgba(25, 135, 84, 0.12)' : 'rgba(220, 53, 69, 0.12)';
                                                                        ?>
                                                                            <tr>
                                                                                <td style="background-color: <?= $bgColor; ?>">
                                                                                    <div>
                                                                                        <strong class="text-primary"><?= $job['name']; ?></strong>
                                                                                        <br><small class="text-muted"><?= $job['description']; ?></small>
                                                                                    </div>
                                                                                </td>
                                                                                <td style="background-color: <?= $bgColor; ?>">
                                                                                    <div class="input-group">
                                                                                        <input type="text" class="form-control form-control-sm"
                                                                                            id="cronUrl<?= $globalIndex; ?>"
                                                                                            value="<?= $cronUrl; ?>"
                                                                                            readonly
                                                                                            style="font-size: 12px;">
                                                                                        <button class="btn btn-outline-secondary btn-sm"
                                                                                            type="button"
                                                                                            onclick="copyToClipboard('cronUrl<?= $globalIndex; ?>')"
                                                                                            title="<?= __('Copy đường dẫn'); ?>">
                                                                                            <i class="ri-file-copy-line"></i>
                                                                                        </button>
                                                                                    </div>
                                                                                </td>
                                                                                <td class="text-center" style="background-color: <?= $bgColor; ?>">
                                                                                    <span class="badge bg-danger"><?= $job['recommended_time']; ?></span>
                                                                                </td>
                                                                                <td style="background-color: <?= $bgColor; ?>">
                                                                                    <small class="<?= ($lastRun && $lastRun > 0) ? 'text-success' : 'text-warning'; ?>">
                                                                                        <?= $lastRunFormatted; ?>
                                                                                    </small>
                                                                                </td>
                                                                                <td class="text-center" style="background-color: <?= $bgColor; ?>">
                                                                                    <a href="<?= $cronUrl; ?>" target="_blank"
                                                                                        class="btn btn-sm btn-primary"
                                                                                        title="<?= __('Chạy thử'); ?>">
                                                                                        <i class="ri-play-line"></i>
                                                                                    </a>
                                                                                </td>
                                                                            </tr>
                                                                    <?php
                                                                            $globalIndex++;
                                                                        endforeach;
                                                                    endforeach;
                                                                    ?>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <div class="mt-4">
                                                            <div class="alert alert-warning border-0">
                                                                <div class="d-flex">
                                                                    <div class="me-2">
                                                                        <i class="ri-alert-line fs-16"></i>
                                                                    </div>
                                                                    <div>
                                                                        <strong><?= __('Lưu ý quan trọng:'); ?></strong>
                                                                        <ul class="mb-0 mt-2">
                                                                            <li><?= __('Cài đặt Cron Job dưới đây để hệ thống tự động xử lý công việc'); ?></li>
                                                                            <li><?= __('Khuyến nghị cấu hình trên máy chủ dùng wget hoặc curl để gọi URL'); ?></li>
                                                                            <li><?= __('Ví dụ: wget -q -O /dev/null'); ?> <code><?= base_url('cron/cron.php?key=' . $CMSNT->site('key_cron_job')); ?></code> <?= __('hoặc curl'); ?> <code><?= base_url('cron/cron.php?key=' . $CMSNT->site('key_cron_job')); ?></code></li>
                                                                            <li><?= __('Thời gian "Lần chạy cuối" sẽ cập nhật sau mỗi lần cron job được thực thi'); ?></li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        // SỬ DỤNG localStorage để nhớ item đang mở
                                        document.addEventListener('DOMContentLoaded', function() {
                                            // Đọc localStorage để xem có item nào cần mở
                                            const activeAddon = localStorage.getItem('activeAddon');
                                            if (activeAddon) {
                                                const collapseElement = document.querySelector(activeAddon);
                                                if (collapseElement) {
                                                    // Tạo instance Bootstrap Collapse, toggle: false để không auto
                                                    const collapseInstance = new bootstrap.Collapse(
                                                        collapseElement, {
                                                            toggle: false
                                                        });
                                                    collapseInstance.show();
                                                }
                                            }

                                            // Bắt sự kiện khi item mở
                                            const allCollapses = document.querySelectorAll('.accordion-collapse');
                                            allCollapses.forEach(function(collapseEl) {
                                                collapseEl.addEventListener('shown.bs.collapse', function(
                                                    e) {
                                                    localStorage.setItem('activeAddon', '#' + e
                                                        .target.id);
                                                });
                                                // Tuỳ chọn: nếu muốn xoá khi đóng => 'hidden.bs.collapse'
                                                // collapseEl.addEventListener('hidden.bs.collapse', function(e) {
                                                //   localStorage.removeItem('activeAddon');
                                                // });
                                            });
                                        });
                                    </script>


