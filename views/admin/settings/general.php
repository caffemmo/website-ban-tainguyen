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
        'action'        => __('Thay đổi cài đặt chung')
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
    $my_text = str_replace('{action}', __('Thay đổi cài đặt chung'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    admin_msg_success("Lưu thành công!", "", 1000);
}
?>
<div class="tab-pane text-muted show active" id="cai-dat-chung" role="tabpanel">
    <!-- Page Header -->
    <div class="d-flex align-items-center mb-4">
        <div class="flex-shrink-0">
            <div class="avatar avatar-md bg-primary-transparent rounded-circle">
                <i class="fas fa-cogs fs-18 text-primary"></i>
            </div>
        </div>
        <div class="flex-grow-1 ms-3">
            <h4 class="mb-1"><?= __('Cài đặt chung'); ?></h4>
            <p class="text-muted mb-0"><?= __('Cấu hình thông tin cơ bản, giao diện và các tùy chọn hiển thị cho website'); ?></p>
        </div>
    </div>

    <form action="" method="POST">
        <div class="row">
            <!-- === CỘT TRÁI === -->
            <div class="col-xl-6">
                <!-- Card: Thông tin Website (SEO) -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <i class="fas fa-globe fs-16"></i>
                            </div>
                            <div>
                                <span class="fw-semibold fs-15 text-uppercase"><?= __('Thông tin Website (SEO)'); ?></span>
                                <br><small class="opacity-75"><?= __('Tiêu đề, mô tả và từ khóa cho công cụ tìm kiếm'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-link me-1 text-danger"></i><?= __('Allowed Domains'); ?></label>
                            <input type="text" name="domains" value="<?= $CMSNT->site('domains'); ?>" class="form-control">
                            <div class="form-text text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Không thay đổi nếu không hiểu rõ, phí khôi phục 100.000đ 1 lần</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-heading me-1 text-primary"></i><?= __('Title'); ?></label>
                            <input type="text" name="title" value="<?= $CMSNT->site('title'); ?>" class="form-control" placeholder="<?= __('Tiêu đề website'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-file-alt me-1 text-primary"></i><?= __('Description'); ?></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="<?= __('Mô tả ngắn về website'); ?>"><?= $CMSNT->site('description'); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-tags me-1 text-primary"></i><?= __('Keywords'); ?></label>
                            <textarea name="keywords" class="form-control" rows="2" placeholder="<?= __('Từ khóa SEO, cách nhau bởi dấu phẩy'); ?>"><?= $CMSNT->site('keywords'); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-user me-1 text-primary"></i><?= __('Author'); ?></label>
                                    <input type="text" name="author" value="<?= $CMSNT->site('author'); ?>" class="form-control" placeholder="<?= __('Tên tác giả'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-clock me-1 text-primary"></i><?= __('Timezone'); ?></label>
                                    <input type="text" name="timezone" value="<?= $CMSNT->site('timezone'); ?>" class="form-control" placeholder="Asia/Ho_Chi_Minh">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Thông tin liên hệ -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <i class="fas fa-address-book fs-16"></i>
                            </div>
                            <div>
                                <span class="fw-semibold fs-15 text-uppercase"><?= __('Thông tin liên hệ'); ?></span>
                                <br><small class="opacity-75"><?= __('Email, hotline, địa chỉ và mạng xã hội'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-envelope me-1 text-success"></i><?= __('Email'); ?></label>
                            <input type="text" name="email" value="<?= $CMSNT->site('email'); ?>" class="form-control mb-2" placeholder="<?= __('Địa chỉ email'); ?>">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><?= __('Icon'); ?></span>
                                <input type="text" name="icon_email" value='<?= $CMSNT->site('icon_email'); ?>' class="form-control">
                                <span class="input-group-text"><?= $CMSNT->site('icon_email'); ?></span>
                            </div>
                        </div>
                        <!-- Hotline -->
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-phone me-1 text-success"></i><?= __('Hotline'); ?></label>
                            <input type="text" name="hotline" value="<?= $CMSNT->site('hotline'); ?>" class="form-control mb-2" placeholder="<?= __('Số điện thoại'); ?>">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><?= __('Icon'); ?></span>
                                <input type="text" name="icon_hotline" value='<?= $CMSNT->site('icon_hotline'); ?>' class="form-control">
                                <span class="input-group-text"><?= $CMSNT->site('icon_hotline'); ?></span>
                            </div>
                        </div>
                        <!-- Địa chỉ -->
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-map-marker-alt me-1 text-success"></i><?= __('Địa chỉ'); ?></label>
                            <input type="text" name="address" value="<?= $CMSNT->site('address'); ?>" class="form-control mb-2" placeholder="<?= __('Địa chỉ liên hệ'); ?>">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><?= __('Icon'); ?></span>
                                <input type="text" name="icon_address" value='<?= $CMSNT->site('icon_address'); ?>' class="form-control">
                                <span class="input-group-text"><?= $CMSNT->site('icon_address'); ?></span>
                            </div>
                        </div>
                        <!-- Fanpage -->
                        <div class="mb-0">
                            <label class="form-label fw-medium"><i class="fab fa-facebook me-1 text-success"></i><?= __('Fanpage'); ?></label>
                            <input type="text" name="fanpage" value="<?= $CMSNT->site('fanpage'); ?>" class="form-control" placeholder="https://facebook.com/...">
                        </div>
                    </div>
                </div>

                <!-- Card: Giao diện & Màu sắc -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-info-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <i class="fas fa-palette fs-16"></i>
                            </div>
                            <div>
                                <span class="fw-semibold fs-15 text-uppercase"><?= __('Giao diện & Màu sắc'); ?></span>
                                <br><small class="opacity-75"><?= __('Màu chủ đạo, font chữ và tuỳ chỉnh giao diện'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-fill-drip me-1 text-info"></i><?= __('Màu chủ đạo'); ?></label>
                                    <input type="color" class="form-control form-control-color w-100 border-0" name="theme_color"
                                        value="<?= $CMSNT->site('theme_color'); ?>" title="Choose your color">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-tint me-1 text-info"></i><?= __('Màu phụ'); ?></label>
                                    <input type="color" class="form-control form-control-color w-100 border-0" name="theme_color1"
                                        value="<?= $CMSNT->site('theme_color1'); ?>" title="Choose your color">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-font me-1 text-info"></i>Font Family</label>
                            <input type="text" name="font_family" value="<?= $CMSNT->site('font_family'); ?>" class="form-control">
                            <div class="form-text"><i class="fas fa-book-open me-1"></i><a class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-thay-doi-font-chu-cho-website/" target="_blank"><?= __('Hướng dẫn sử dụng'); ?></a></div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-medium"><i class="fas fa-wrench me-1 text-info"></i>ON/OFF menu Công cụ</label>
                            <select class="form-select" name="status_menu_tools">
                                <option <?= $CMSNT->site('status_menu_tools') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                <option <?= $CMSNT->site('status_menu_tools') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                            </select>
                            <img src="<?= base_url('mod/img/demo-menu-tools.webp'); ?>" class="mt-2 rounded" width="100%">
                        </div>
                    </div>
                </div>
            </div>

            <!-- === CỘT PHẢI === -->
            <div class="col-xl-6">
                <!-- Card: Trạng thái & Hệ thống -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-danger-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <i class="fas fa-server fs-16"></i>
                            </div>
                            <div>
                                <span class="fw-semibold fs-15 text-uppercase"><?= __('Trạng thái & Hệ thống'); ?></span>
                                <br><small class="opacity-75"><?= __('Bảo trì, cập nhật, debug và cấu hình hệ thống'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-power-off me-1 text-danger"></i><?= __('Trạng thái website'); ?></label>
                                    <select class="form-select" name="status">
                                        <option <?= $CMSNT->site('status') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('status') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                    <div class="form-text"><?= __('Chọn OFF nếu bạn muốn bật chế độ bảo trì.'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-cloud-download-alt me-1 text-danger"></i><?= __('Cập nhật tự động'); ?></label>
                                    <select class="form-select" name="status_update">
                                        <option <?= $CMSNT->site('status_update') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('status_update') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-bug me-1 text-danger"></i>Debug Auto Bank</label>
                                    <select class="form-select" name="debug_auto_bank">
                                        <option <?= $CMSNT->site('debug_auto_bank') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('debug_auto_bank') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-exchange-alt me-1 text-danger"></i>Debug API Suppliers</label>
                                    <select class="form-select" name="debug_api_suppliers">
                                        <option <?= $CMSNT->site('debug_api_suppliers') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('debug_api_suppliers') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-text text-danger mb-3"><i class="fas fa-exclamation-triangle me-1"></i><?= __('Không bật ON khi chưa được CMSNT yêu cầu'); ?></div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-file-code me-1 text-danger"></i>ON/OFF Tài liệu API</label>
                                    <select class="form-select" name="api_status">
                                        <option <?= $CMSNT->site('api_status') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('api_status') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-blog me-1 text-danger"></i>ON/OFF menu Blogs</label>
                                    <select class="form-select" name="blog_status">
                                        <option <?= $CMSNT->site('blog_status') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('blog_status') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Cấu hình nghiệp vụ -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-warning-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <i class="fas fa-shopping-cart fs-16"></i>
                            </div>
                            <div>
                                <span class="fw-semibold fs-15 text-uppercase"><?= __('Cấu hình nghiệp vụ'); ?></span>
                                <br><small class="opacity-75"><?= __('Mua hàng, thuế VAT, hoàn tiền và các tùy chọn khác'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-coins me-1 text-warning"></i>ON/OFF Cộng tiền cho người bán</label>
                            <select class="form-select" name="cong_tien_nguoi_ban">
                                <option <?= $CMSNT->site('cong_tien_nguoi_ban') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                <option <?= $CMSNT->site('cong_tien_nguoi_ban') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                            </select>
                            <div class="form-text"><?= __('Nếu bạn ON, admin nào tạo ra sản phẩm sẽ được cộng tiền khi có khách mua.'); ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-stopwatch me-1 text-warning"></i>Thời gian mua hàng cách nhau</label>
                                    <div class="input-group">
                                        <input name="thoi_gian_mua_cach_nhau" type="text" class="form-control"
                                            value="<?= $CMSNT->site('thoi_gian_mua_cach_nhau'); ?>" required>
                                        <span class="input-group-text">Giây</span>
                                    </div>
                                    <div class="form-text">Nhập 0 nếu không giới hạn.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-list-ol me-1 text-warning"></i>Limit Check live</label>
                                    <div class="input-group">
                                        <input name="limit_check_live_clone" type="number" class="form-control"
                                            value="<?= $CMSNT->site('limit_check_live_clone'); ?>" required>
                                        <span class="input-group-text">tài khoản</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-receipt me-1 text-warning"></i>Thuế VAT</label>
                                    <div class="input-group">
                                        <input name="tax_vat" type="text" class="form-control"
                                            value="<?= $CMSNT->site('tax_vat'); ?>" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Để trống nếu không thu thuế VAT.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-file-invoice me-1 text-warning"></i>Bắt buộc nhập thông tin VAT</label>
                                    <select class="form-select" name="popup_vat">
                                        <option <?= $CMSNT->site('popup_vat') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                        <option <?= $CMSNT->site('popup_vat') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-undo me-1 text-warning"></i>Tự động hoàn tiền khi lỗi API</label>
                            <select class="form-select" name="auto_refund_order_failed_api">
                                <option <?= $CMSNT->site('auto_refund_order_failed_api') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                <option <?= $CMSNT->site('auto_refund_order_failed_api') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                            </select>
                            <div class="form-text"><span class="text-danger fw-bold">Lưu ý:</span> nếu ON đồng nghĩa với việc bạn chấp nhận rủi ro khi API có lỗi nghiêm trọng.</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-lock me-1 text-warning"></i>Lock rows khi mua hàng</label>
                                    <select class="form-select" name="isForUpdateBuy">
                                        <option value="0" <?= $CMSNT->site('isForUpdateBuy') == 0 ? 'selected' : ''; ?>>OFF</option>
                                        <option value="1" <?= $CMSNT->site('isForUpdateBuy') == 1 ? 'selected' : ''; ?>>ON</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-file-invoice-dollar me-1 text-warning"></i>Menu xuất hóa đơn VAT</label>
                                    <select class="form-select" name="sidebar_vat_invoice_status">
                                        <option <?= $CMSNT->site('sidebar_vat_invoice_status') == 1 ? 'selected' : ''; ?> value="1"><?= __('Hiển thị'); ?></option>
                                        <option <?= $CMSNT->site('sidebar_vat_invoice_status') == 0 ? 'selected' : ''; ?> value="0"><?= __('Ẩn'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Thông báo nổi & Chính sách -->
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-secondary-gradient border-bottom-0">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <i class="fas fa-bell fs-16"></i>
                            </div>
                            <div>
                                <span class="fw-semibold fs-15 text-uppercase"><?= __('Thông báo & Chính sách'); ?></span>
                                <br><small class="opacity-75"><?= __('Popup, thông báo, trang chính sách và đăng ký'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-info-circle me-1 text-secondary"></i><?= __('Thông báo phía trên góc trái màn hình'); ?></label>
                            <textarea class="form-control" name="notice_top_left" rows="2"><?= $CMSNT->site('notice_top_left'); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-home me-1 text-secondary"></i><?= __('Thông báo ngoài trang chủ'); ?></label>
                            <textarea id="notice_home" name="notice_home"><?= $CMSNT->site('notice_home'); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-history me-1 text-secondary"></i><?= __('Thông báo trang lịch sử đơn hàng'); ?></label>
                            <textarea id="notice_orders" name="notice_orders"><?= $CMSNT->site('notice_orders'); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-window-restore me-1 text-secondary"></i>ON/OFF Thông báo nổi</label>
                                    <select class="form-select" name="popup_status">
                                        <option <?= $CMSNT->site('popup_status') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('popup_status') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium"><i class="fas fa-clipboard-check me-1 text-secondary"></i>Yêu cầu đọc chính sách khi đăng ký</label>
                                    <select class="form-select" name="isConfirmPolicyRegister">
                                        <option <?= $CMSNT->site('isConfirmPolicyRegister') == 1 ? 'selected' : ''; ?> value="1">ON</option>
                                        <option <?= $CMSNT->site('isConfirmPolicyRegister') == 0 ? 'selected' : ''; ?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-window-maximize me-1 text-secondary"></i><?= __('Thông báo nổi ngoài trang chủ'); ?></label>
                            <textarea id="popup_noti" name="popup_noti"><?= $CMSNT->site('popup_noti'); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-gavel me-1 text-secondary"></i><?= __('Nội dung chính sách khi đăng ký'); ?></label>
                            <textarea id="policy_register" name="policy_register"><?= $CMSNT->site('policy_register'); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === FULL WIDTH: Nội dung trang === -->
            <div class="col-12">
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, #833AB4, #C13584, #E1306C); border-bottom: 0;">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <i class="fas fa-file-alt fs-16"></i>
                            </div>
                            <div>
                                <span class="fw-semibold fs-15 text-uppercase"><?= __('Nội dung các trang'); ?></span>
                                <br><small class="opacity-75"><?= __('Liên hệ, chính sách, FAQ'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-phone-alt me-1 text-primary"></i><?= __('Nội dung trang liên hệ'); ?></label>
                            <textarea id="page_contact" name="page_contact"><?= $CMSNT->site('page_contact'); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium"><i class="fas fa-shield-alt me-1 text-success"></i><?= __('Nội dung trang chính sách'); ?></label>
                            <textarea id="page_policy" name="page_policy"><?= $CMSNT->site('page_policy'); ?></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-medium"><i class="fas fa-question-circle me-1 text-info"></i><?= __('Nội dung trang FAQ'); ?></label>
                            <textarea id="page_faq" name="page_faq"><?= $CMSNT->site('page_faq'); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === FULL WIDTH: Script/HTML === -->
            <div class="col-12">
                <div class="card custom-card border-0 shadow-sm mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); border-bottom: 0;">
                        <div class="card-title text-white mb-0 d-flex align-items-center">
                            <div class="avatar avatar-sm bg-white-transparent me-2">
                                <i class="fas fa-code fs-16"></i>
                            </div>
                            <div>
                                <span class="fw-semibold fs-15 text-uppercase"><?= __('Tùy chỉnh Script/HTML'); ?></span>
                                <br><small class="opacity-75"><?= __('Cấu hình các script và HTML tùy chỉnh cho website'); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <label class="form-label fw-medium"><i class="fas fa-arrow-up me-1 text-primary"></i><?= __('Script/HTML Header trang khách'); ?></label>
                                <small class="text-muted d-block mb-2"><?= __('Hiển thị trong thẻ &lt;head&gt; của trang khách'); ?></small>
                                <textarea rows="8" name="javascript_header" id="javascript_header" class="form-control"><?= $CMSNT->site('javascript_header'); ?></textarea>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label fw-medium"><i class="fas fa-arrow-down me-1 text-warning"></i><?= __('Script/HTML Footer trang khách'); ?></label>
                                <small class="text-muted d-block mb-2"><?= __('Hiển thị cuối trang khách trước thẻ &lt;/body&gt;'); ?></small>
                                <textarea rows="8" name="javascript_footer" id="javascript_footer" class="form-control"><?= $CMSNT->site('javascript_footer'); ?></textarea>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label fw-medium"><i class="fas fa-id-card me-1 text-success"></i><?= __('Script/HTML Footer Card trang khách'); ?></label>
                                <small class="text-muted d-block mb-2"><?= __('Hiển thị trong phần footer card'); ?></small>
                                <textarea rows="8" name="footer_card" id="footer_card" class="form-control"><?= $CMSNT->site('footer_card'); ?></textarea>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label fw-medium"><i class="fas fa-user-shield me-1 text-danger"></i><?= __('Script/HTML Footer trang quản trị'); ?></label>
                                <small class="text-muted d-block mb-2"><?= __('Hiển thị cuối trang admin trước thẻ &lt;/body&gt;'); ?></small>
                                <textarea rows="8" name="script_footer_admin" id="script_footer_admin" class="form-control"><?= $CMSNT->site('script_footer_admin'); ?></textarea>
                            </div>
                        </div>
                        <!-- Lưu ý -->
                        <div class="alert alert-light border">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-lightbulb text-warning me-2 fs-16 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading mb-2"><i class="fas fa-info-circle me-1"></i><?= __('Lưu ý quan trọng'); ?></h6>
                                    <ul class="mb-0 small">
                                        <li><?= __('Header Script: Dành cho Google Analytics, Facebook Pixel, Meta tags...'); ?></li>
                                        <li><?= __('Footer Script: Dành cho chat plugin, tracking, popup...'); ?></li>
                                        <li><?= __('Footer Card: Script hiển thị trong phần footer card của trang khách'); ?></li>
                                        <li><?= __('Admin Footer: Script chỉ hiển thị trong trang quản trị'); ?></li>
                                        <li class="text-danger"><?= __('Cẩn thận với script có thể ảnh hưởng đến bảo mật website'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
            <div class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                <?= __('Các thay đổi sẽ được áp dụng ngay sau khi lưu'); ?>
            </div>
            <button type="submit" name="SaveSettings" class="btn btn-primary btn-lg px-5">
                <i class="fa fa-fw fa-save me-2"></i>
                <?= __('Lưu cài đặt chung'); ?>
            </button>
        </div>
    </form>
</div>

<script>
    // Khởi tạo CKEditor cho các textarea rich-text
    CKEDITOR.replace("popup_noti");
    CKEDITOR.replace("page_faq");
    CKEDITOR.replace("page_policy");
    CKEDITOR.replace("page_contact");
    CKEDITOR.replace("notice_home");
    CKEDITOR.replace("notice_orders");
    CKEDITOR.replace("policy_register");

    // Khởi tạo CodeMirror cho các textarea code
    setTimeout(function() {
        var codeMirrorConfig = {
            lineNumbers: true,
            mode: "htmlmixed",
            theme: "monokai",
            matchBrackets: true,
            autoCloseTags: true,
            lineWrapping: true
        };

        ['javascript_header', 'javascript_footer', 'footer_card', 'script_footer_admin'].forEach(function(id) {
            if (document.getElementById(id)) {
                CodeMirror.fromTextArea(document.getElementById(id), codeMirrorConfig);
            }
        });
    }, 100);
</script>
