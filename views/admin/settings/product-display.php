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
        'action'        => __('Thay đổi cài đặt hiển thị sản phẩm')
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
    $my_text = str_replace('{action}', __('Thay đổi cài đặt hiển thị sản phẩm'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}
?>
<div class="tab-pane text-muted show active" id="hien-thi-san-pham" role="tabpanel">
    <h4><?= __('Tùy chỉnh giao diện hiển thị sản phẩm'); ?></h4>
    <form action="" method="POST">
        <div class="row push mb-3">
            <div class="col-md-6">
                <table class="mb-3 table table-bordered table-striped table-hover">
                    <tbody>
                        <tr>
                            <td><?= __('Menu chuyên mục thanh bên'); ?></td>
                            <td>
                                <select class="form-control"
                                    name="menu_category_right">
                                    <option
                                        <?= $CMSNT->site('menu_category_right') == 1 ? 'selected' : ''; ?>
                                        value="1"><?= __('Hiển thị bên phải'); ?>
                                    </option>
                                    <option
                                        <?= $CMSNT->site('menu_category_right') == 2 ? 'selected' : ''; ?>
                                        value="2"><?= __('Hiển thị bên trái'); ?>
                                    </option>
                                    <option
                                        <?= $CMSNT->site('menu_category_right') == 0 ? 'selected' : ''; ?>
                                        value="0">
                                        <?= __('Ẩn'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('Ảnh sản phẩm'); ?></td>
                            <td>
                                <select class="form-control"
                                    name="product_photo_display">
                                    <option
                                        <?= $CMSNT->site('product_photo_display') == 1 ? 'selected' : ''; ?>
                                        value="1"><?= __('Hiển thị'); ?>
                                    </option>
                                    <option
                                        <?= $CMSNT->site('product_photo_display') == 0 ? 'selected' : ''; ?>
                                        value="0">
                                        <?= __('Ẩn'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <!-- <tr>
                                                                <td><?= __('Đánh giá và reviews'); ?></td>
                                                                <td>
                                                                    <select class="form-control" disabled
                                                                        name="product_rating_display">
                                                                        <option
                                                                            <?= $CMSNT->site('product_rating_display') == 1 ? 'selected' : ''; ?>
                                                                            value="1"><?= __('Hiển thị'); ?>
                                                                        </option>
                                                                        <option
                                                                            <?= $CMSNT->site('product_rating_display') == 0 ? 'selected' : ''; ?>
                                                                            value="0">
                                                                            <?= __('Ẩn'); ?>
                                                                        </option>
                                                                    </select>
                                                                </td>
                                                            </tr> -->
                        <tr>
                            <td><?= __('Hiển thị số lượng đã bán'); ?></td>
                            <td>
                                <select class="form-control"
                                    name="product_sold_display">
                                    <option
                                        <?= $CMSNT->site('product_sold_display') == 1 ? 'selected' : ''; ?>
                                        value="1"><?= __('Hiển thị'); ?>
                                    </option>
                                    <option
                                        <?= $CMSNT->site('product_sold_display') == 0 ? 'selected' : ''; ?>
                                        value="0">
                                        <?= __('Ẩn'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('Ẩn sản phẩm khỏi trang chủ khi hết hàng'); ?>
                            </td>
                            <td>
                                <select class="form-control"
                                    name="product_hide_outstock">
                                    <option
                                        <?= $CMSNT->site('product_hide_outstock') == 1 ? 'selected' : ''; ?>
                                        value="1">ON
                                    </option>
                                    <option
                                        <?= $CMSNT->site('product_hide_outstock') == 0 ? 'selected' : ''; ?>
                                        value="0">
                                        OFF
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('ON/OFF Hiển thị cột UID trong đơn hàng'); ?>
                            </td>
                            <td>
                                <select class="form-control" name="is_uid_visible">
                                    <option
                                        <?= $CMSNT->site('is_uid_visible') == 1 ? 'selected' : ''; ?>
                                        value="1">ON
                                    </option>
                                    <option
                                        <?= $CMSNT->site('is_uid_visible') == 0 ? 'selected' : ''; ?>
                                        value="0">
                                        OFF
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="mb-3 table table-bordered table-striped table-hover">
                    <tbody>
                        <tr>
                            <td><?= __('Loại hiển thị'); ?></td>
                            <td>
                                <select class="form-control"
                                    name="type_show_product">
                                    <option
                                        <?= $CMSNT->site('type_show_product') == 'BOX' ? 'selected' : ''; ?>
                                        value="BOX">BOX (1 dòng 2 sản phẩm, không hiển thị ảnh)
                                    </option>
                                    <option
                                        <?= $CMSNT->site('type_show_product') == 'LIST' ? 'selected' : ''; ?>
                                        value="LIST">
                                        LIST (1 dòng 1 sản phẩm, không hiển thị ảnh)
                                    </option>
                                    <option
                                        <?= $CMSNT->site('type_show_product') == 'BOX_4' ? 'selected' : ''; ?>
                                        value="BOX_4">BOX 4 (hiển thị ảnh sản phẩm bên trên 1 dòng 1 sản phẩm)
                                    </option>
                                    <option
                                        <?= $CMSNT->site('type_show_product') == 'BOX_5' ? 'selected' : ''; ?>
                                        value="BOX_5">BOX 5 (hiển thị ảnh sản phẩm bên trái, 1 dòng 2 sản phẩm)
                                    </option>
                                    <option
                                        <?= $CMSNT->site('type_show_product') == 'BOX_6' ? 'selected' : ''; ?>
                                        value="BOX_6">BOX 6 (hiển thị ảnh sản phẩm bên trái, 1 dòng 1 sản phẩm)
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('Số sản phẩm hiển thị tối đa của 1 chuyên mục tại trang chủ'); ?>
                            </td>
                            <td>
                                <input type="number" class="form-control"
                                    value="<?= $CMSNT->site('max_show_product_home'); ?>"
                                    name="max_show_product_home">
                            </td>
                        </tr>
                        <tr>
                            <td>Sắp xếp sản phẩm</td>
                            <td>
                                <select class="form-control"
                                    name="order_by_product_home">
                                    <option
                                        <?= $CMSNT->site('order_by_product_home') == 1 ? 'selected' : ''; ?>
                                        value="1">Theo số ưu tiên (stt)
                                    </option>
                                    <option
                                        <?= $CMSNT->site('order_by_product_home') == 2 ? 'selected' : ''; ?>
                                        value="2"> Giá thấp đến cao
                                    </option>
                                    <option
                                        <?= $CMSNT->site('order_by_product_home') == 3 ? 'selected' : ''; ?>
                                        value="3"> Giá cao đến thấp
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('ON/OFF cột số dư bên phải'); ?></td>
                            <td>
                                <select class="form-control"
                                    name="cot_so_du_ben_phai">
                                    <option
                                        <?= $CMSNT->site('cot_so_du_ben_phai') == 1 ? 'selected' : ''; ?>
                                        value="1"><?= __('Hiển thị'); ?>
                                    </option>
                                    <option
                                        <?= $CMSNT->site('cot_so_du_ben_phai') == 0 ? 'selected' : ''; ?>
                                        value="0">
                                        <?= __('Ẩn'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('ON/OFF Nút chuyên mục ở Trang chủ'); ?></td>
                            <td>
                                <select class="form-control"
                                    name="show_btn_category_home">
                                    <option
                                        <?= $CMSNT->site('show_btn_category_home') == 1 ? 'selected' : ''; ?>
                                        value="1"><?= __('Hiển thị'); ?>
                                    </option>
                                    <option
                                        <?= $CMSNT->site('show_btn_category_home') == 0 ? 'selected' : ''; ?>
                                        value="0">
                                        <?= __('Ẩn'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?= __('ON/OFF Đăng nhập mới được phép xem sản phẩm'); ?>
                            </td>
                            <td>
                                <select class="form-control"
                                    name="isLoginRequiredToViewProduct">
                                    <option
                                        <?= $CMSNT->site('isLoginRequiredToViewProduct') == 1 ? 'selected' : ''; ?>
                                        value="1"><?= __('ON'); ?>
                                    </option>
                                    <option
                                        <?= $CMSNT->site('isLoginRequiredToViewProduct') == 0 ? 'selected' : ''; ?>
                                        value="0">
                                        <?= __('OFF'); ?>
                                    </option>
                                </select>
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