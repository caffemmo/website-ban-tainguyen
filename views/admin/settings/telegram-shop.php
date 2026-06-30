<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>

<?php
// Xử lý lưu cài đặt
if (isset($_POST['SaveSettings'])) {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Thay đổi cài đặt Telegram Shop')
    ]);

    $settings_to_save = ['telegram_shop_status', 'telegram_shop_license', 'telegram_shop_bot_token', 'telegram_shop_webhook_code'];
    foreach ($_POST as $key => $value) {
        if (in_array($key, $settings_to_save)) {
            $CMSNT->update("settings", array(
                'value' => check_string($value)
            ), " `name` = '$key' ");
        }
    }

    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Thay đổi cài đặt Telegram Shop'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}


// Kiểm tra License
$checkKey = checkAddonLicense($CMSNT->site('telegram_shop_license'), 'SHOPCLONE7_BOTTELEGRAMSHOPPING');

// Kiểm tra file entry point đã được cài đặt chưa (file không đi kèm bản phân phối chính)
$addonInstalled = file_exists(__DIR__ . '/../../../telegram-shop.php');
?>

<div class="tab-pane text-muted show active" id="telegram-shop" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0"><?= __('Cấu hình Telegram Shop'); ?></h4>
        <?php if ($checkKey['status'] == true && $addonInstalled): ?>
            <div class="d-flex gap-2">
                <button type="button" id="btnBroadcast" class="btn btn-info m-0" data-bs-toggle="modal" data-bs-target="#modalBroadcast">
                    <i class="fa fa-fw fa-bullhorn me-1"></i> <?= __('Gửi thông báo'); ?>
                </button>
                <button type="button" id="btnSetWebhook" class="btn btn-warning m-0">
                    <i class="fa fa-fw fa-sync me-1"></i> <?= __('Cập nhật Webhook'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
    <form action="" method="POST">
        <div class="row push mb-3">
            <div class="col-md-12">
                <div class="card p-3 shadow-none border">

                    <?php if ($checkKey['status'] != true): ?>
                        <!-- Giao diện khi chưa kích hoạt bản quyền -->

                        <!-- Ảnh giới thiệu Addon Telegram Shop, tỷ lệ 4:3 -->
                        <div class="row mb-4">
                            <div class="col-sm-12">
                                <a href="https://client.cmsnt.co/cart.php?a=add&pid=116" target="_blank" rel="noopener noreferrer" style="display: block;">
                                    <div style="position: relative; width: 100%; padding-top: 56.25%; overflow: hidden; border-radius: 8px;">
                                        <img src="<?= base_url('mod/img/addon-telegram-shopping.webp') ?>" alt="Telegram Shop Addon" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label class="col-sm-3 col-form-label"><?= __('Trạng thái'); ?></label>
                            <div class="col-sm-9">
                                <select class="form-control" name="telegram_shop_status">
                                    <option <?= $CMSNT->site('telegram_shop_status') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                    <option <?= $CMSNT->site('telegram_shop_status') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Giấy phép kích hoạt Addon</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Nhập mã giấy phép (License Key)" value="<?= $CMSNT->site('telegram_shop_license'); ?>" name="telegram_shop_license">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3"></div>
                            <div class="col-sm-9">
                                <div class="alert alert-warning mb-0 border-warning border">
                                    <strong>Chú ý:</strong> Bạn cần phải mua giấy phép kích hoạt <a target="_blank" class="text-primary" href="https://client.cmsnt.co/cart.php?a=add&pid=116">Addon Telegram Shop</a> trước khi sử dụng.
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <?php if (!$addonInstalled): ?>
                            <!-- License đã active nhưng chưa cài file Addon (telegram-shop.php) -->
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?= __('Trạng thái'); ?></label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="telegram_shop_status">
                                        <option <?= $CMSNT->site('telegram_shop_status') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('telegram_shop_status') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label">Giấy phép Addon</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= $CMSNT->site('telegram_shop_license'); ?>" name="telegram_shop_license" readonly>
                                        <span class="input-group-text bg-success text-white"><i class="fa fa-check-circle me-1"></i> Active</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"></div>
                                <div class="col-sm-9">
                                    <div class="alert alert-danger mb-0 border-danger border">
                                        <i class="fa fa-exclamation-circle me-2"></i>
                                        <strong><?= __('Chưa cài đặt Addon!'); ?></strong><br>
                                        <?= __('Giấy phép đã được kích hoạt nhưng hệ thống chưa tìm thấy file Addon Telegram Shop.'); ?><br>
                                        <?= __('Vui lòng liên hệ'); ?> <a href="https://cmsnt.co" target="_blank" class="fw-bold text-danger">CMSNT.CO</a> <?= __('để được hỗ trợ cài đặt Addon.'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Giao diện khi đã kích hoạt + đã cài file Addon -->
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?= __('Trạng thái'); ?></label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="telegram_shop_status">
                                        <option <?= $CMSNT->site('telegram_shop_status') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('telegram_shop_status') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label">Giấy phép Addon</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= $CMSNT->site('telegram_shop_license'); ?>" name="telegram_shop_license" readonly>
                                        <span class="input-group-text bg-success text-white"><i class="fa fa-check-circle me-1"></i> Active</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label">Token Bot Telegram</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" value="<?= $CMSNT->site('telegram_shop_bot_token'); ?>" name="telegram_shop_bot_token" placeholder="VD: 123456789:ABCdefGHIjklMNOpqrSTUvwxYZ">
                                    <small class="text-muted">Lấy mã Token từ @BotFather trên Telegram</small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label">Mã bảo mật Webhook</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" value="<?= $CMSNT->site('telegram_shop_webhook_code'); ?>" name="telegram_shop_webhook_code">
                                    <small class="text-muted">Được sử dụng làm query params để tránh làm giả request từ bên thứ ba.</small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-sm-3 col-form-label">URL Webhook</label>
                                <div class="col-sm-9">
                                    <?php $webhook_display = base_url('telegram-shop.php?code=' . $CMSNT->site('telegram_shop_webhook_code')); ?>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="<?= $webhook_display; ?>" readonly>
                                        <button class="btn btn-outline-primary copy" type="button" data-clipboard-text="<?= $webhook_display; ?>" onclick="copy()">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Nhấn "Cập nhật Webhook" bên trên để hệ thống tự động gán URL này cho Bot của bạn.</small>
                                </div>
                            </div>

                        <?php endif; ?><!-- kết thúc if $addonInstalled -->
                    <?php endif; ?><!-- kết thúc if checkKey -->

                </div>
            </div>
        </div>
        <button type="submit" name="SaveSettings" class="btn btn-primary w-100 mb-3">
            <i class="fa fa-fw fa-save me-1"></i> <?= __('Save'); ?>
        </button>
    </form>
</div>

<?php if ($checkKey['status'] == true && $addonInstalled): ?>
    <!-- Modal: Gửi thông báo broadcast cho user Telegram Shopping -->
    <div class="modal fade" id="modalBroadcast" tabindex="-1" aria-labelledby="modalBroadcastLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBroadcastLabel">
                        <i class="fa fa-bullhorn me-2 text-info"></i> <?= __('Gửi thông báo đến Telegram Shopping'); ?>
                    </h5>
                    <button type="button" class="btn-close" id="btnBroadcastClose" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- [PHẦN FORM] - Hiển thị khi chưa gửi -->
                    <div id="broadcastForm">
                        <div class="alert alert-info border-info border mb-3">
                            <i class="fa fa-info-circle me-1"></i>
                            <?= __('Thông báo sẽ gửi đến <b>TẤT CẢ</b> khách hàng đã kết nối Bot qua Telegram (có <code>telegram_chat_id</code>). <b>Không áp dụng</b> với khách hàng chỉ đăng ký tại website.'); ?>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold"><?= __('Nội dung thông báo'); ?></label>
                            <textarea id="broadcastMessage" class="form-control" rows="8" maxlength="4000" placeholder="<?= 'Nhập nội dung thông báo... Hỗ trợ HTML: <b>đậm</b>, <i>nghiêng</i>, <code>code</code>, <a href=\'url\'>link</a>'; ?>"></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted"><?= __('Hỗ trợ HTML Telegram: &lt;b&gt;, &lt;i&gt;, &lt;u&gt;, &lt;s&gt;, &lt;code&gt;, &lt;pre&gt;, &lt;a href&gt;'); ?></small>
                                <small class="text-muted"><span id="broadcastCounter">0</span>/4000</small>
                            </div>
                        </div>

                        <div class="alert alert-warning border-warning border mb-0 small">
                            <i class="fa fa-exclamation-triangle me-1"></i>
                            <?= __('Việc gửi có thể tốn từ vài giây đến vài phút tuỳ số lượng user. <b>Vui lòng không đóng cửa sổ cho đến khi có kết quả.</b>'); ?>
                        </div>
                    </div>

                    <!-- [PHẦN LOADING] - Hiển thị khi đang gửi (thay thế form) -->
                    <div id="broadcastLoading" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-info" style="width: 4rem; height: 4rem; border-width: 0.4rem;" role="status">
                            <span class="visually-hidden"><?= __('Đang gửi...'); ?></span>
                        </div>
                        <h5 class="mt-4 mb-2 text-info fw-bold">
                            <i class="fa fa-paper-plane me-1"></i> <?= __('Đang gửi thông báo...'); ?>
                        </h5>
                        <p class="text-muted mb-1">
                            <?= __('Hệ thống đang gửi tin nhắn đến từng user qua Telegram API.'); ?>
                        </p>
                        <p class="text-muted mb-0 small">
                            <i class="fa fa-hourglass-half me-1"></i>
                            <?= __('Vui lòng không đóng hoặc refresh cửa sổ. Thời gian có thể mất vài phút tuỳ số lượng user.'); ?>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnBroadcastCancel" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i> <?= __('Huỷ'); ?>
                    </button>
                    <button type="button" id="btnSendBroadcast" class="btn btn-info">
                        <i class="fa fa-paper-plane me-1"></i> <?= __('Gửi thông báo'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<script type="text/javascript">
    new ClipboardJS(".copy");

    function copy() {
        showMessage("<?= __('Đã sao chép vào bộ nhớ tạm'); ?>", 'success');
    }

    // Bộ đếm ký tự cho textarea broadcast
    $(document).on('input', '#broadcastMessage', function() {
        $('#broadcastCounter').text($(this).val().length);
    });

    // Chuyển modal sang chế độ đang gửi:
    // - Ẩn form, hiện overlay loading
    // - Khoá mọi cách đóng modal (X, Huỷ, ESC, click outside) để tránh ngắt request
    function broadcastLockModal() {
        $('#broadcastForm').hide();
        $('#broadcastLoading').show();
        $('#btnBroadcastClose').prop('disabled', true).addClass('d-none');
        $('#btnBroadcastCancel').prop('disabled', true);
        $('#btnSendBroadcast').prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin me-1"></i> <?= __('Đang gửi...'); ?>');

        var modalEl = document.getElementById('modalBroadcast');
        var modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance._config.backdrop = 'static';
            modalInstance._config.keyboard = false;
        }
    }

    // Khôi phục modal về trạng thái bình thường (hiển thị form, mở lại các nút đóng)
    function broadcastUnlockModal() {
        $('#broadcastLoading').hide();
        $('#broadcastForm').show();
        $('#btnBroadcastClose').prop('disabled', false).removeClass('d-none');
        $('#btnBroadcastCancel').prop('disabled', false);
        $('#btnSendBroadcast').prop('disabled', false)
            .html('<i class="fa fa-paper-plane me-1"></i> <?= __('Gửi thông báo'); ?>');

        var modalEl = document.getElementById('modalBroadcast');
        var modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance._config.backdrop = true;
            modalInstance._config.keyboard = true;
        }
    }

    // Gửi thông báo broadcast
    $(document).on('click', '#btnSendBroadcast', function() {
        var message = $('#broadcastMessage').val().trim();
        if (message.length < 5) {
            showMessage("<?= __('Nội dung thông báo quá ngắn (tối thiểu 5 ký tự)'); ?>", 'error');
            return;
        }

        // Xác nhận trước khi gửi vì hành động này không thể hoàn tác
        if (!confirm("<?= __('Xác nhận gửi thông báo này đến TẤT CẢ user Telegram Shopping?'); ?>")) {
            return;
        }

        broadcastLockModal();

        $.ajax({
            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
            method: "POST",
            dataType: "JSON",
            // Broadcast có thể chạy lâu với nhiều user → tăng timeout client lên 10 phút
            timeout: 600000,
            data: {
                action: 'telegram_shop_broadcast',
                message: message
            },
            success: function(respone) {
                broadcastUnlockModal();
                if (respone.status == 'success') {
                    showMessage(respone.msg, 'success');
                    $('#modalBroadcast').modal('hide');
                    $('#broadcastMessage').val('');
                    $('#broadcastCounter').text('0');
                } else {
                    showMessage(respone.msg, 'error');
                }
            },
            error: function(xhr, status) {
                broadcastUnlockModal();
                // Timeout thường xảy ra khi server chạy quá 10 phút → khuyên admin check log
                if (status === 'timeout') {
                    showMessage("<?= __('Quá thời gian chờ. Broadcast có thể vẫn đang chạy nền — kiểm tra log để xác nhận.'); ?>", 'error');
                } else {
                    showMessage("<?= __('Lỗi kết nối máy chủ'); ?>", 'error');
                }
            }
        });
    });

    $("#btnSetWebhook").on("click", function() {
        $('#btnSetWebhook').html('<i class="fa fa-spinner fa-spin"></i> Đang xử lý...').prop('disabled', true);
        $.ajax({
            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'set_telegram_shop_webhook'
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    showMessage(respone.msg, 'success');
                } else {
                    showMessage(respone.msg, 'error');
                }
                $('#btnSetWebhook').html('<i class="fa fa-fw fa-sync me-1"></i> <?= __('Cập nhật Webhook'); ?>').prop('disabled', false);
            },
            error: function() {
                showMessage("Lỗi kết nối máy chủ", 'error');
                $('#btnSetWebhook').html('<i class="fa fa-fw fa-sync me-1"></i> <?= __('Cập nhật Webhook'); ?>').prop('disabled', false);
            }
        });
    });
</script>