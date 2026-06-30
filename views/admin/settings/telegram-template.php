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
        'action'        => __('Thay đổi mẫu thông báo Telegram')
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
    $my_text = str_replace('{action}', __('Thay đổi mẫu thông báo Telegram'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}
?>
                                    <div class="tab-pane text-muted show active" id="telegram-template" role="tabpanel">
                                        <h4>Nội dung thông báo Telegram</h4>
                                        <form action="" method="POST">
                                            <div class="row push mb-3">
                                                <div class="col-md-12">
                                                    <table class="mb-3 table table-bordered table-striped table-hover">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th colspan="2" class="text-center">
                                                                    Để mặc định nếu bạn không có nhu cầu tùy chỉnh<br>
                                                                    <small>Xóa toàn bộ nội dung trong ô nếu không muốn
                                                                        bật thông báo</small>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td><?= __('Thông báo mua hàng'); ?></td>
                                                                <td>
                                                                    <textarea class="form-control mb-2" rows="3"
                                                                        name="noti_buy_product"><?= $CMSNT->site('noti_buy_product'); ?></textarea>
                                                                    <ul>
                                                                        <li><b>{domain}</b> => Tên website của quý
                                                                            khách.</li>
                                                                        <li><b>{username}</b> => Tên thành viên.</li>
                                                                        <li><b>{trans_id}</b> => Mã đơn hàng.</li>
                                                                        <li><b>{product}</b> => Sản phẩm mua.</li>
                                                                        <li><b>{amount}</b> => Số lượng mua.</li>
                                                                        <li><b>{pay}</b> => Số tiền thanh toán.</li>
                                                                        <li><b>{ip}</b> => Địa chỉ IP mua hàng.
                                                                        </li>
                                                                        <li><b>{time}</b> => Thời gian mua hàng</li>
                                                                    </ul>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><?= __('Thông báo nạp tiền'); ?></td>
                                                                <td>
                                                                    <textarea class="form-control mb-2" rows="3"
                                                                        name="noti_recharge"><?= $CMSNT->site('noti_recharge'); ?></textarea>
                                                                    <ul>
                                                                        <li><b>{domain}</b> => Tên website của quý
                                                                            khách.</li>
                                                                        <li><b>{username}</b> => Tên khách hàng nạp.
                                                                        </li>
                                                                        <li><b>{method}</b> => Phương thức nạp.</li>
                                                                        <li><b>{amount}</b> => Số tiền nạp.</li>
                                                                        <li><b>{price}</b> => Thực nhận.</li>
                                                                        <li><b>{time}</b> => Thời gian.</li>
                                                                    </ul>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><?= __('Thông báo hành động'); ?></td>
                                                                <td>
                                                                    <textarea class="form-control mb-2" rows="3"
                                                                        name="noti_action"><?= $CMSNT->site('noti_action'); ?></textarea>
                                                                    <ul>
                                                                        <li><b>{domain}</b> => Tên website của quý
                                                                            khách.</li>
                                                                        <li><b>{username}</b> => Tên thành viên.</li>
                                                                        <li><b>{action}</b> => Hành động của thành viên.
                                                                        </li>
                                                                        <li><b>{ip}</b> => Địa chỉ IP của thành viên.
                                                                        </li>
                                                                        <li><b>{time}</b> => Thời gian.</li>
                                                                    </ul>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><?= __('Thông báo hoàn tiền đơn hàng'); ?></td>
                                                                <td>
                                                                    <textarea class="form-control mb-2" rows="3"
                                                                        name="noti_refund_orders"><?= $CMSNT->site('noti_refund_orders'); ?></textarea>
                                                                    <ul>
                                                                        <li><b>{domain}</b> => Tên website của quý
                                                                            khách.</li>
                                                                        <li><b>{username}</b> => Tên thành viên.</li>
                                                                        <li><b>{action}</b> => Hành động của thành viên.
                                                                        </li>
                                                                        <li><b>{ip}</b> => Địa chỉ IP của thành viên.
                                                                        </li>
                                                                        <li><b>{time}</b> => Thời gian.</li>
                                                                    </ul>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><?= __('Thông báo rút số dư hoa hồng'); ?></td>
                                                                <td>
                                                                    <textarea class="form-control mb-2" rows="3"
                                                                        name="noti_affiliate_withdraw"><?= $CMSNT->site('noti_affiliate_withdraw'); ?></textarea>
                                                                    <ul>
                                                                        <li><b>{domain}</b> => Tên website của quý
                                                                            khách.</li>
                                                                        <li><b>{username}</b> => Tên thành viên rút.
                                                                        </li>
                                                                        <li><b>{bank}</b> => Tên ngân hàng nhận tiền.
                                                                        </li>
                                                                        <li><b>{account_number}</b> => Số tài khoản nhận
                                                                            tiền.</li>
                                                                        <li><b>{account_name}</b> => Tên chủ tài khoản.
                                                                        </li>
                                                                        <li><b>{amount}</b> => Số dư cần rút.</li>
                                                                        <li><b>{ip}</b> => Địa chỉ IP của thành viên.
                                                                        </li>
                                                                        <li><b>{time}</b> => Thời gian.</li>
                                                                    </ul>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><?= __('Thông báo khi API hết số dư'); ?></td>
                                                                <td>
                                                                    <textarea class="form-control mb-2" rows="3"
                                                                        name="noti_api_out_of_money"><?= $CMSNT->site('noti_api_out_of_money'); ?></textarea>
                                                                    <ul>
                                                                        <li><b>{domain}</b> => Tên website của quý
                                                                            khách.</li>
                                                                        <li><b>{username}</b> => Tên thành viên mua hàng.
                                                                        </li>
                                                                        <li><b>{product_name}</b> => Tên sản phẩm cần mua.
                                                                        </li>
                                                                        <li><b>{supplier_name}</b> => Tên nhà cung cấp.</li>
                                                                        <li><b>{product_id}</b> => ID sản phẩm cần mua.</li>
                                                                        <li><b>{pay}</b> => Số tiền đơn hàng.
                                                                        </li>
                                                                        <li><b>{amount}</b> => Số lượng cần mua.</li>
                                                                        <li><b>{ip}</b> => Địa chỉ IP của thành viên mua hàng.</li>
                                                                        <li><b>{time}</b> => Thời gian.</li>
                                                                    </ul>
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
