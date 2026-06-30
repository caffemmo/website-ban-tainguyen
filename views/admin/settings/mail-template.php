<?php if (!defined('IN_SITE')) { die('The Request Not Found'); } ?>

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
        'action'        => __('Thay đổi mẫu thông báo Mail')
    ]);

    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }

    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Thay đổi mẫu thông báo Mail'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}
?>
                                    <div class="tab-pane text-muted show active" id="mail-template" role="tabpanel">
                                        <h4>Nội dung thông báo Mail</h4>
                                        <form action="" method="POST">
                                            <div class="row push mb-3">
                                                <div class="col-md-12">
                                                    <table class="mb-3 table table-bordered table-striped table-hover">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th colspan="2" class="text-center">
                                                                    Để mặc định nếu bạn không có nhu cầu tùy chỉnh<br>
                                                                    <small>Xóa toàn bộ nội dung trong ô Subject nếu
                                                                        không muốn bật thông báo</small>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>Thông tin đơn hàng</td>
                                                                <td>
                                                                    <div class="input-group mb-2">
                                                                        <span class="input-group-text"
                                                                            id="basic-addon1">Subject</span>
                                                                        <input class="form-control"
                                                                            name="email_temp_subject_buy_order"
                                                                            value="<?= $CMSNT->site('email_temp_subject_buy_order'); ?>">
                                                                    </div>
                                                                    <textarea class="form-control mb-2"
                                                                        id="email_temp_content_buy_order" rows="6"
                                                                        name="email_temp_content_buy_order"><?= $CMSNT->site('email_temp_content_buy_order'); ?></textarea>
                                                                    <div
                                                                        class="accordion accordion-customicon1 accordion-primary">
                                                                        <div class="accordion-item">
                                                                            <h2 class="accordion-header">
                                                                                <button
                                                                                    class="accordion-button collapsed"
                                                                                    type="button"
                                                                                    data-bs-toggle="collapse"
                                                                                    data-bs-target="#email_temp_content_buy_order"
                                                                                    aria-expanded="false"
                                                                                    aria-controls="email_temp_content_buy_order">
                                                                                    Văn bản thay thế
                                                                                </button>
                                                                            </h2>
                                                                            <div id="email_temp_content_buy_order"
                                                                                class="accordion-collapse collapse">
                                                                                <div class="accordion-body">
                                                                                    <ul>
                                                                                        <li><b>{domain}</b> => Link
                                                                                            Website.</li>
                                                                                        <li><b>{title}</b> => Tên
                                                                                            website.</li>
                                                                                        <li><b>{username}</b> => Tên
                                                                                            khách hàng.</li>
                                                                                        <li><b>{ip}</b> => Địa chỉ IP.
                                                                                        </li>
                                                                                        <li><b>{device}</b> => Thiết bị.
                                                                                        </li>
                                                                                        <li><b>{time}</b> => Thời gian.
                                                                                        </li>
                                                                                        <li><b>{product}</b> => Tên sản
                                                                                            phẩm.</li>
                                                                                        <li><b>{amount}</b> => Số lượng
                                                                                            đã mua.</li>
                                                                                        <li><b>{trans_id}</b> => Mã đơn
                                                                                            hàng.</li>
                                                                                        <li><b>{pay}</b> => Số tiền đã
                                                                                            thanh toán.</li>
                                                                                    </ul>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Thông báo đăng nhập</td>
                                                                <td>
                                                                    <div class="input-group mb-2">
                                                                        <span class="input-group-text"
                                                                            id="basic-addon1">Subject</span>
                                                                        <input class="form-control"
                                                                            name="email_temp_subject_warning_login"
                                                                            value="<?= $CMSNT->site('email_temp_subject_warning_login'); ?>">
                                                                    </div>
                                                                    <textarea class="form-control mb-2"
                                                                        id="email_temp_content_warning_login" rows="6"
                                                                        name="email_temp_content_warning_login"><?= $CMSNT->site('email_temp_content_warning_login'); ?></textarea>
                                                                    <div
                                                                        class="accordion accordion-customicon1 accordion-primary">
                                                                        <div class="accordion-item">
                                                                            <h2 class="accordion-header">
                                                                                <button
                                                                                    class="accordion-button collapsed"
                                                                                    type="button"
                                                                                    data-bs-toggle="collapse"
                                                                                    data-bs-target="#email_temp_content_warning_login"
                                                                                    aria-expanded="false"
                                                                                    aria-controls="email_temp_content_warning_login">
                                                                                    Văn bản thay thế
                                                                                </button>
                                                                            </h2>
                                                                            <div id="email_temp_content_warning_login"
                                                                                class="accordion-collapse collapse">
                                                                                <div class="accordion-body">
                                                                                    <ul>
                                                                                        <li><b>{domain}</b> => Link
                                                                                            Website.</li>
                                                                                        <li><b>{title}</b> => Tên
                                                                                            website.</li>
                                                                                        <li><b>{username}</b> => Tên
                                                                                            khách hàng.</li>
                                                                                        <li><b>{ip}</b> => Địa chỉ IP.
                                                                                        </li>
                                                                                        <li><b>{device}</b> => Thiết bị.
                                                                                        </li>
                                                                                        <li><b>{time}</b> => Thời gian.
                                                                                        </li>
                                                                                    </ul>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Gửi OTP xác minh đăng nhập</td>
                                                                <td>
                                                                    <div class="input-group mb-2">
                                                                        <span class="input-group-text"
                                                                            id="basic-addon1">Subject</span>
                                                                        <input class="form-control"
                                                                            name="email_temp_subject_otp_mail"
                                                                            value="<?= $CMSNT->site('email_temp_subject_otp_mail'); ?>">
                                                                    </div>
                                                                    <textarea class="form-control mb-2" rows="6"
                                                                        id="email_temp_content_otp_mail"
                                                                        name="email_temp_content_otp_mail"><?= $CMSNT->site('email_temp_content_otp_mail'); ?></textarea>
                                                                    <div
                                                                        class="accordion accordion-customicon1 accordion-primary">
                                                                        <div class="accordion-item">
                                                                            <h2 class="accordion-header">
                                                                                <button
                                                                                    class="accordion-button collapsed"
                                                                                    type="button"
                                                                                    data-bs-toggle="collapse"
                                                                                    data-bs-target="#email_temp_content_otp_mail"
                                                                                    aria-expanded="false"
                                                                                    aria-controls="email_temp_content_otp_mail">
                                                                                    Văn bản thay thế
                                                                                </button>
                                                                            </h2>
                                                                            <div id="email_temp_content_otp_mail"
                                                                                class="accordion-collapse collapse">
                                                                                <div class="accordion-body">
                                                                                    <ul>
                                                                                        <li><b>{domain}</b> => Link
                                                                                            Website.</li>
                                                                                        <li><b>{title}</b> => Tên
                                                                                            website.</li>
                                                                                        <li><b>{username}</b> => Tên
                                                                                            khách hàng.</li>
                                                                                        <li><b>{otp}</b> => Mã OTP.
                                                                                        </li>
                                                                                        <li><b>{ip}</b> => Địa chỉ IP.
                                                                                        </li>
                                                                                        <li><b>{device}</b> => Thiết bị.
                                                                                        </li>
                                                                                        <li><b>{time}</b> => Thời gian.
                                                                                        </li>
                                                                                    </ul>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Khôi phục mật khẩu</td>
                                                                <td>
                                                                    <div class="input-group mb-2">
                                                                        <span class="input-group-text"
                                                                            id="basic-addon1">Subject</span>
                                                                        <input class="form-control"
                                                                            name="email_temp_subject_forgot_password"
                                                                            value="<?= $CMSNT->site('email_temp_subject_forgot_password'); ?>">
                                                                    </div>
                                                                    <textarea class="form-control mb-2" rows="6"
                                                                        id="email_temp_content_forgot_password"
                                                                        name="email_temp_content_forgot_password"><?= $CMSNT->site('email_temp_content_forgot_password'); ?></textarea>
                                                                    <div
                                                                        class="accordion accordion-customicon1 accordion-primary">
                                                                        <div class="accordion-item">
                                                                            <h2 class="accordion-header">
                                                                                <button
                                                                                    class="accordion-button collapsed"
                                                                                    type="button"
                                                                                    data-bs-toggle="collapse"
                                                                                    data-bs-target="#email_temp_content_forgot_password"
                                                                                    aria-expanded="false"
                                                                                    aria-controls="email_temp_content_forgot_password">
                                                                                    Văn bản thay thế
                                                                                </button>
                                                                            </h2>
                                                                            <div id="email_temp_content_forgot_password"
                                                                                class="accordion-collapse collapse">
                                                                                <div class="accordion-body">
                                                                                    <ul>
                                                                                        <li><b>{domain}</b> => Link
                                                                                            Website.</li>
                                                                                        <li><b>{title}</b> => Tên
                                                                                            website.</li>
                                                                                        <li><b>{username}</b> => Tên
                                                                                            khách hàng.</li>
                                                                                        <li><b>{link}</b> => Link xác
                                                                                            minh.
                                                                                        </li>
                                                                                        <li><b>{ip}</b> => Địa chỉ IP.
                                                                                        </li>
                                                                                        <li><b>{device}</b> => Thiết bị.
                                                                                        </li>
                                                                                        <li><b>{time}</b> => Thời gian.
                                                                                        </li>
                                                                                    </ul>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <button type="submit" name="SaveSettings"
                                                class="btn btn-primary w-100 mb-3">
                                                <i class="fa fa-fw fa-save me-1"></i> <?= __('Save'); ?>
                                            </button>
                                        </form>
                                    </div>

<script>
    CKEDITOR.replace("email_temp_content_warning_login");
    CKEDITOR.replace("email_temp_content_forgot_password");
    CKEDITOR.replace("email_temp_content_otp_mail");
    CKEDITOR.replace("email_temp_content_buy_order");
</script>
