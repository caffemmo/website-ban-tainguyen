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
        'action'        => __('Thay đổi cài đặt giao dịch gần đây')
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
    $my_text = str_replace('{action}', __('Thay đổi cài đặt giao dịch gần đây'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}
?>
                                    <div class="tab-pane text-muted show active" id="giao-dich-gan-day" role="tabpanel">
                                        <h4><?= __('Tùy chỉnh giao dịch gần đây'); ?></h4>
                                        <form action="" method="POST">
                                            <div class="row push mb-3">
                                                <div class="col-md-6">
                                                    <table class="mb-3 table table-bordered table-striped table-hover">
                                                        <tbody>
                                                            <tr>
                                                                <td><?= __('ON/OFF giao dịch gần đây'); ?></td>
                                                                <td>
                                                                    <select class="form-control"
                                                                        name="status_giao_dich_gan_day">
                                                                        <option
                                                                            <?= $CMSNT->site('status_giao_dich_gan_day') == 1 ? 'selected' : ''; ?>
                                                                            value="1">ON
                                                                        </option>
                                                                        <option
                                                                            <?= $CMSNT->site('status_giao_dich_gan_day') == 0 ? 'selected' : ''; ?>
                                                                            value="0">
                                                                            OFF
                                                                        </option>
                                                                    </select>
                                                                    <img src="<?= base_url('mod/img/demo-gd-gan-day.webp'); ?>"
                                                                        width="500px">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <center class="mb-2">Nội dung giao dịch mua hàng gần
                                                                        đây
                                                                    </center>
                                                                    <textarea class="form-control mb-2"
                                                                        id="content_gd_mua_gan_day" rows="2"
                                                                        name="content_gd_mua_gan_day"><?= $CMSNT->site('content_gd_mua_gan_day'); ?></textarea>
                                                                    <div
                                                                        class="accordion accordion-customicon1 accordion-primary">
                                                                        <div class="accordion-item">
                                                                            <h2 class="accordion-header">
                                                                                <button
                                                                                    class="accordion-button collapsed"
                                                                                    type="button"
                                                                                    data-bs-toggle="collapse"
                                                                                    data-bs-target="#content_gd_mua_gan_day"
                                                                                    aria-expanded="false"
                                                                                    aria-controls="content_gd_mua_gan_day">
                                                                                    Văn bản thay thế
                                                                                </button>
                                                                            </h2>
                                                                            <div id="content_gd_mua_gan_day"
                                                                                class="accordion-collapse collapse">
                                                                                <div class="accordion-body">
                                                                                    <ul>
                                                                                        <li><b>{username}</b> => Tên
                                                                                            user mua hàng.</li>
                                                                                        <li><b>{amount}</b> => Số lượng
                                                                                            mua.</li>
                                                                                        <li><b>{product_Name}</b> => Tên
                                                                                            sản phẩm.</li>
                                                                                        <li><b>{price}</b> => Giá bán.
                                                                                    </ul>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <center class="mb-2">Nội dung giao dịch nạp tiền gần
                                                                        đây
                                                                    </center>
                                                                    <textarea class="form-control mb-2"
                                                                        id="content_gd_nap_tien_gan_day" rows="2"
                                                                        name="content_gd_nap_tien_gan_day"><?= $CMSNT->site('content_gd_nap_tien_gan_day'); ?></textarea>
                                                                    <div
                                                                        class="accordion accordion-customicon1 accordion-primary">
                                                                        <div class="accordion-item">
                                                                            <h2 class="accordion-header">
                                                                                <button
                                                                                    class="accordion-button collapsed"
                                                                                    type="button"
                                                                                    data-bs-toggle="collapse"
                                                                                    data-bs-target="#content_gd_nap_tien_gan_day"
                                                                                    aria-expanded="false"
                                                                                    aria-controls="content_gd_nap_tien_gan_day">
                                                                                    Văn bản thay thế
                                                                                </button>
                                                                            </h2>
                                                                            <div id="content_gd_nap_tien_gan_day"
                                                                                class="accordion-collapse collapse">
                                                                                <div class="accordion-body">
                                                                                    <ul>
                                                                                        <li><b>{username}</b> => Tên
                                                                                            user nạp tiền.</li>
                                                                                        <li><b>{amount}</b> => Số tiền
                                                                                            nạp.</li>
                                                                                        <li><b>{method}</b> => Phương
                                                                                            thức nạp.</li>
                                                                                        <li><b>{received}</b> => Thực
                                                                                            nhận.
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
                                                <div class="col-md-6">
                                                    <table class="mb-3 table table-bordered table-striped table-hover">
                                                        <tbody>
                                                            <tr>
                                                                <td>ON/OFF tạo giao dịch ảo</td>
                                                                <td>
                                                                    <select class="form-control"
                                                                        name="status_tao_gd_ao">
                                                                        <option
                                                                            <?= $CMSNT->site('status_tao_gd_ao') == 1 ? 'selected' : ''; ?>
                                                                            value="1">ON
                                                                        </option>
                                                                        <option
                                                                            <?= $CMSNT->site('status_tao_gd_ao') == 0 ? 'selected' : ''; ?>
                                                                            value="0">
                                                                            OFF
                                                                        </option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Số lượng mua ảo tối thiểu
                                                                </td>
                                                                <td>
                                                                    <input type="number" class="form-control"
                                                                        value="<?= $CMSNT->site('sl_mua_toi_thieu_gd_ao'); ?>"
                                                                        name="sl_mua_toi_thieu_gd_ao">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Số lượng mua ảo tối đa
                                                                </td>
                                                                <td>
                                                                    <input type="number" class="form-control"
                                                                        value="<?= $CMSNT->site('sl_mua_toi_da_gd_ao'); ?>"
                                                                        name="sl_mua_toi_da_gd_ao">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>ON/OFF tạo giao dịch ảo sản phẩm hết hàng</td>
                                                                <td>
                                                                    <select class="form-control"
                                                                        name="tao_gd_ao_sp_het_hang">
                                                                        <option
                                                                            <?= $CMSNT->site('tao_gd_ao_sp_het_hang') == 1 ? 'selected' : ''; ?>
                                                                            value="1">ON
                                                                        </option>
                                                                        <option
                                                                            <?= $CMSNT->site('tao_gd_ao_sp_het_hang') == 0 ? 'selected' : ''; ?>
                                                                            value="0">
                                                                            OFF
                                                                        </option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Tốc độ giao dịch mua ảo
                                                                </td>
                                                                <td>
                                                                    <input type="number" class="form-control"
                                                                        value="<?= $CMSNT->site('toc_do_gd_mua_ao'); ?>"
                                                                        name="toc_do_gd_mua_ao">
                                                                    <small>Tốc độ càng thấp, thời gian tạo giao dịch ảo
                                                                        càng nhanh.</small>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Mệnh giá nạp ảo ngẫu nhiên
                                                                </td>
                                                                <td>
                                                                    <textarea class="form-control" rows="3"
                                                                        name="menh_gia_nap_ao_ngau_nhien"><?= $CMSNT->site('menh_gia_nap_ao_ngau_nhien'); ?></textarea>
                                                                    <small>1 dòng 1 dữ liệu.</small>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Phương thức nạp ảo
                                                                </td>
                                                                <td>
                                                                    <textarea class="form-control" rows="3"
                                                                        name="method_nap_ao"><?= $CMSNT->site('method_nap_ao'); ?></textarea>
                                                                    <small>1 dòng 1 dữ liệu.</small>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Tốc độ giao dịch nạp ảo
                                                                </td>
                                                                <td>
                                                                    <input type="number" class="form-control"
                                                                        value="<?= $CMSNT->site('toc_do_gd_nap_ao'); ?>"
                                                                        name="toc_do_gd_nap_ao">
                                                                    <small>Tốc độ càng thấp, thời gian tạo giao dịch ảo
                                                                        càng nhanh.</small>
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
