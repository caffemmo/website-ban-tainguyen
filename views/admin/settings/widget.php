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
        'action'        => __('Thay đổi cài đặt Widget')
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
    $my_text = str_replace('{action}', __('Thay đổi cài đặt Widget'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}
?>
                                    <div class="tab-pane text-muted show active" id="widget" role="tabpanel">
                                        <h4><?= __('Tùy chỉnh Widget'); ?></h4>
                                        <form action="" method="POST">
                                            <div class="row push mb-3">
                                                <div class="col-md-6">
                                                    <table class="mb-3 table table-bordered table-striped table-hover">
                                                        <tbody>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <select class="form-control mb-1"
                                                                        name="widget_zalo1_status">
                                                                        <option
                                                                            <?= $CMSNT->site('widget_zalo1_status') == 1 ? 'selected' : ''; ?>
                                                                            value="1">ON
                                                                        </option>
                                                                        <option
                                                                            <?= $CMSNT->site('widget_zalo1_status') == 0 ? 'selected' : ''; ?>
                                                                            value="0">
                                                                            OFF
                                                                        </option>
                                                                    </select>
                                                                    <img src="<?= base_url('mod/img/demo-widget-zalo1.png'); ?>"
                                                                        width="500px">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Số điện thoại
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control"
                                                                        value="<?= $CMSNT->site('widget_zalo1_sdt'); ?>"
                                                                        name="widget_zalo1_sdt">
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <table class="mb-3 table table-bordered table-striped table-hover">
                                                        <tbody>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <select class="form-control mb-1"
                                                                        name="widget_fbzalo2_status">
                                                                        <option
                                                                            <?= $CMSNT->site('widget_fbzalo2_status') == 1 ? 'selected' : ''; ?>
                                                                            value="1">ON
                                                                        </option>
                                                                        <option
                                                                            <?= $CMSNT->site('widget_fbzalo2_status') == 0 ? 'selected' : ''; ?>
                                                                            value="0">
                                                                            OFF
                                                                        </option>
                                                                    </select>
                                                                    <img src="<?= base_url('mod/img/demo-widget-fbzalo2.png'); ?>"
                                                                        width="200px">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Link Zalo
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control"
                                                                        value="<?= $CMSNT->site('widget_fbzalo2_zalo'); ?>"
                                                                        name="widget_fbzalo2_zalo">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Link Facebook
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control"
                                                                        value="<?= $CMSNT->site('widget_fbzalo2_fb'); ?>"
                                                                        name="widget_fbzalo2_fb">
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="mb-3 table table-bordered table-striped table-hover">
                                                        <tbody>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <select class="form-control mb-1"
                                                                        name="widget_phone1_status">
                                                                        <option
                                                                            <?= $CMSNT->site('widget_phone1_status') == 1 ? 'selected' : ''; ?>
                                                                            value="1">ON
                                                                        </option>
                                                                        <option
                                                                            <?= $CMSNT->site('widget_phone1_status') == 0 ? 'selected' : ''; ?>
                                                                            value="0">
                                                                            OFF
                                                                        </option>
                                                                    </select>
                                                                    <img src="<?= base_url('mod/img/demo-widget-phone1.png'); ?>"
                                                                        width="500px">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Số điện thoại
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control"
                                                                        value="<?= $CMSNT->site('widget_phone1_sdt'); ?>"
                                                                        name="widget_phone1_sdt">
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
