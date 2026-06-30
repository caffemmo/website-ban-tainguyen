<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>

<div class="tab-pane text-muted show active" id="addons" role="tabpanel">
    <h4 class="mb-4"><i class="fa-solid fa-puzzle-piece text-info me-2"></i>Cửa hàng Addon</h4>


    <!-- BẮT ĐẦU ACCORDION (danh sách Addon) -->
    <div class="accordion" id="accordionAddons">

        <!-- ADDON CTV PANEL -->
        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingCtvPanel">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseCtvPanel" aria-expanded="false"
                    aria-controls="collapseCtvPanel">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-users fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">CTV Panel - Hệ Thống Cộng Tác Viên</h5>
                            <small class="text-muted">Quản lý cộng tác viên, sản phẩm và rút tiền hoa hồng</small>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapseCtvPanel" class="accordion-collapse collapse border-top"
                aria-labelledby="headingCtvPanel" data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <!-- NỘI DUNG CHI TIẾT -->
                    <div class="row g-4">

                        <!-- CỘT DEMO -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-play-circle text-primary me-2"></i>
                                        DEMO CTV Panel
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Phần demo GIF -->
                                    <div class="position-relative overflow-hidden rounded-3"
                                        style="padding-top: 100%">
                                        <img src="https://i.postimg.cc/FKHgKQ6F/A-nh-ma-n-hi-nh-2025-09-11-lu-c-18-46-55.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                            alt="Demo CTV Panel" loading="lazy">
                                    </div>

                                    <!-- Phần tính năng nổi bật -->
                                    <div class="mt-4">
                                        <h6 class="fw-semibold mb-3">Tính năng nổi
                                            bật</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li class="d-flex mb-2">
                                                <i
                                                    class="fas fa-check-circle text-success me-2 mt-1"></i>
                                                <span>Quản lý cộng tác viên chuyên nghiệp.</span>
                                            </li>
                                            <li class="d-flex mb-2">
                                                <i
                                                    class="fas fa-check-circle text-success me-2 mt-1"></i>
                                                <span>CTV có thể thêm sản phẩm và quản lý đơn hàng.</span>
                                            </li>
                                            <li class="d-flex mb-2">
                                                <i
                                                    class="fas fa-check-circle text-success me-2 mt-1"></i>
                                                <span>Hệ thống rút tiền hoa hồng tự động.</span>
                                            </li>
                                            <li class="d-flex mb-2">
                                                <i
                                                    class="fas fa-check-circle text-success me-2 mt-1"></i>
                                                <span>Thống kê doanh thu và báo cáo chi tiết.</span>
                                            </li>
                                            <li class="d-flex mb-2">
                                                <i
                                                    class="fas fa-check-circle text-success me-2 mt-1"></i>
                                                <span>Giao diện thân thiện, dễ sử dụng.</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Phần giá bán Addon -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div
                                            class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Giá bán
                                                    Addon</h6>
                                                <small class="text-muted">Bản quyền
                                                    vĩnh viễn, hỗ trợ trọn
                                                    đời</small>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="fs-5 fw-bold text-primary">
                                                    1.500.000đ
                                                </div>
                                                <small
                                                    class="text-danger text-decoration-line-through">
                                                    2.000.000đ
                                                </small>
                                            </div>
                                        </div>
                                        <a href="https://client.cmsnt.co/store/license-source-code/addon-ctv-panel-shopclone-v7"
                                            target="_blank"
                                            class="btn btn-success w-100 mt-3">
                                            <i
                                                class="fas fa-shopping-cart me-2"></i>
                                            Mua Ngay
                                        </a>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i
                                                    class="fas fa-shield-alt me-1"></i>
                                                Thanh toán an toàn và tự động 24/7
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CỘT MÔ TẢ CHI TIẾT -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Chi Tiết
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-4">CTV Panel là hệ thống quản lý cộng tác viên chuyên nghiệp, cho phép bạn mở rộng kinh doanh thông qua mạng lưới cộng tác viên. CTV có thể thêm sản phẩm, quản lý đơn hàng và rút tiền hoa hồng một cách dễ dàng.</p>

                                    <h6 class="fw-semibold mb-3">Chức năng chính:</h6>
                                    <ul class="list-unstyled mb-4">
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-user-plus text-primary me-2 mt-1"></i>
                                            <span><strong>Quản lý CTV:</strong> Thêm, sửa, xóa cộng tác viên</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-box text-warning me-2 mt-1"></i>
                                            <span><strong>Quản lý sản phẩm:</strong> CTV có thể thêm và quản lý sản phẩm</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-shopping-cart text-info me-2 mt-1"></i>
                                            <span><strong>Quản lý đơn hàng:</strong> Theo dõi và xử lý đơn hàng của CTV</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-money-bill-wave text-success me-2 mt-1"></i>
                                            <span><strong>Rút tiền hoa hồng:</strong> Hệ thống rút tiền tự động</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-chart-bar text-danger me-2 mt-1"></i>
                                            <span><strong>Thống kê:</strong> Báo cáo doanh thu chi tiết</span>
                                        </li>
                                    </ul>

                                    <div class="alert alert-info">
                                        <i class="fas fa-lightbulb me-2"></i>
                                        <strong>Lưu ý:</strong> Addon này yêu cầu bật tính năng "Cộng tiền người bán" trong cài đặt hệ thống để hoạt động.
                                    </div>

                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Yêu cầu:</strong> Cần có license ShopClone v7 để sử dụng addon này.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADDON 1 -->



        <!-- ADDON XI-PAY -->
        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingXipay">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseXipay" aria-expanded="false"
                    aria-controls="collapseXipay">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-money-check-alt fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">Tích Hợp Thanh
                                Toán Qua XiPay (China)</h5>
                            <small class="text-muted">Hỗ trợ thanh toán qua AliPay &
                                WeChatPay</small>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapseXipay" class="accordion-collapse collapse border-top"
                aria-labelledby="headingXipay" data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <!-- NỘI DUNG CHI TIẾT -->
                    <div class="row g-4">
                        <!-- Cột mô tả addon -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>Addon này cho phép bạn tích hợp cổng thanh
                                        toán XiPay trực tiếp vào website của mình.
                                        Với tích hợp này, khách hàng của bạn có thể
                                        thanh toán nhanh chóng qua AliPay hoặc
                                        WeChatPay một cách an toàn và tiện lợi.</p>
                                    <ul class="list-unstyled">
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Thanh toán nhanh chóng qua
                                                XiPay.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Hỗ trợ cả AliPay và
                                                WeChatPay.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Dễ dàng tích hợp vào website hiện
                                                tại.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Cột demo và giá bán -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-play-circle text-primary me-2"></i>
                                        Demo & Giá Bán
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Phần demo ảnh -->
                                    <div class="position-relative overflow-hidden rounded-3"
                                        style="padding-top: 56.25%;">
                                        <img src="https://i.imgur.com/tEqnBN5.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                            alt="Demo Addon XiPay">
                                    </div>

                                    <!-- Phần giá bán -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div
                                            class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Giá bán
                                                    Addon</h6>
                                                <small class="text-muted">Bản quyền
                                                    vĩnh viễn, hỗ trợ cấu hình API
                                                    lần đầu, các lần cấu hình API hộ
                                                    tiếp theo sẽ tính phí 300.000đ /
                                                    lần.</small>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="fs-5 fw-bold text-primary">
                                                    1.200.000đ</div>
                                                <small
                                                    class="text-danger text-decoration-line-through">1.500.000đ</small>
                                            </div>
                                        </div>
                                        <!-- Nút mua hàng tích hợp thanh toán qua XiPay -->
                                        <a href="https://client.cmsnt.co/cart.php?a=add&pid=77"
                                            target="_blank"
                                            class="btn btn-primary w-100 mt-3">
                                            <i
                                                class="fas fa-shopping-cart me-2"></i>
                                            Mua Ngay
                                        </a>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i
                                                    class="fas fa-shield-alt me-1"></i>
                                                Thanh toán an toàn và tự động 24/7
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingKorapay">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseKorapay" aria-expanded="false"
                    aria-controls="collapseKorapay">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-credit-card fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">Tích Hợp Thanh
                                Toán Qua Korapay (Africa)</h5>
                            <small class="text-muted">Hỗ trợ đa kênh thanh toán tại
                                Africa như:
                                Ngân hàng, thẻ tín dụng, ví điện tử & Mobile
                                Money</small>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapseKorapay" class="accordion-collapse collapse border-top"
                aria-labelledby="headingKorapay" data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <!-- NỘI DUNG CHI TIẾT -->
                    <div class="row g-4">
                        <!-- Cột mô tả addon -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>Addon này cho phép bạn tích hợp cổng thanh
                                        toán Korapay trực tiếp vào website của mình.
                                        Khách hàng của bạn có thể thanh toán qua
                                        nhiều kênh như chuyển khoản ngân hàng, thẻ
                                        tín dụng, ví điện tử và Mobile Money. Giao
                                        dịch được xử lý an toàn và nhanh chóng, mang
                                        lại trải nghiệm thanh toán tiện lợi.</p>
                                    <ul class="list-unstyled">
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Thanh toán đa kênh linh
                                                hoạt.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Giao dịch được xử lý an toàn và
                                                nhanh chóng.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Dễ dàng tích hợp và tùy chỉnh theo
                                                yêu cầu.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Cột demo và giá bán -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-play-circle text-primary me-2"></i>
                                        Demo & Giá Bán
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Phần demo ảnh -->
                                    <div class="position-relative overflow-hidden rounded-3"
                                        style="padding-top: 56.25%;">
                                        <img src="https://i.imgur.com/O9QQRc5.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                            alt="Demo Addon Korapay">
                                    </div>

                                    <!-- Phần giá bán -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div
                                            class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Giá bán
                                                    Addon</h6>
                                                <small class="text-muted">Bản quyền
                                                    vĩnh viễn. Hỗ trợ cấu hình API
                                                    miễn phí cho lần đầu, các lần hỗ
                                                    trợ sau tính phí 300.000đ 1
                                                    lần.</small>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="fs-5 fw-bold text-primary">
                                                    1.200.000đ</div>
                                                <small
                                                    class="text-danger text-decoration-line-through">1.500.000đ</small>
                                            </div>
                                        </div>
                                        <!-- Nút mua hàng tích hợp thanh toán qua Korapay -->
                                        <a href="https://client.cmsnt.co/cart.php?a=add&pid=79"
                                            target="_blank"
                                            class="btn btn-primary w-100 mt-3">
                                            <i
                                                class="fas fa-shopping-cart me-2"></i>
                                            Mua Ngay
                                        </a>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i
                                                    class="fas fa-shield-alt me-1"></i>
                                                Thanh toán an toàn và tự động 24/7
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingTmweasyapi">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseTmweasyapi" aria-expanded="false"
                    aria-controls="collapseTmweasyapi">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-globe-asia fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">
                                Tích Hợp Thanh Toán Qua Tmweasyapi (Thailand)
                            </h5>
                            <small class="text-muted">
                                Hỗ trợ đa kênh thanh toán tại Thái Lan: Bank,
                                PromptPay, e-Wallet, TrueMoney Wallet
                            </small>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapseTmweasyapi"
                class="accordion-collapse collapse border-top"
                aria-labelledby="headingTmweasyapi"
                data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <!-- NỘI DUNG CHI TIẾT -->
                    <div class="row g-4">
                        <!-- Cột mô tả addon -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        Addon này cho phép bạn tích hợp cổng thanh
                                        toán
                                        <strong>Tmweasyapi (Thailand)</strong>
                                        trực tiếp vào website của mình. Khách hàng
                                        của bạn
                                        có thể thanh toán qua nhiều kênh như ngân
                                        hàng nội địa Thái Lan,
                                        PromptPay QR code, TrueMoney, và ví điện tử
                                        khác.
                                        Giao dịch được xử lý an toàn và nhanh chóng,
                                        mang lại trải nghiệm thanh toán tiện lợi.
                                    </p>
                                    <ul class="list-unstyled">
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Hỗ trợ thanh toán qua PromptPay,
                                                TrueMoney Wallet, mobile
                                                banking.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Giao dịch bảo mật, xác nhận qua
                                                API tự động.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Dễ dàng triển khai, quản lý các
                                                giao dịch.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- Cột demo và giá bán -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-play-circle text-primary me-2"></i>
                                        Demo & Giá Bán
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Phần demo ảnh -->
                                    <div class="position-relative overflow-hidden rounded-3"
                                        style="padding-top: 56.25%;">
                                        <img src="https://i.imgur.com/8rnKeuE.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                            alt="Demo Addon Tmweasyapi">
                                    </div>
                                    <!-- Phần giá bán -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div
                                            class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Giá bán
                                                    Addon</h6>
                                                <small class="text-muted">
                                                    Bản quyền vĩnh viễn. Cấu hình
                                                    API hộ miễn phí lần đầu, lần thứ
                                                    2 sẽ tính phí 300.000đ / lần cấu
                                                    hình hộ.
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="fs-5 fw-bold text-primary">
                                                    1.200.000đ</div>
                                                <small
                                                    class="text-danger text-decoration-line-through">1.500.000đ</small>
                                            </div>
                                        </div>
                                        <!-- Nút mua hàng -->
                                        <a href="https://client.cmsnt.co/cart.php?a=add&pid=80"
                                            target="_blank"
                                            class="btn btn-primary w-100 mt-3">
                                            <i
                                                class="fas fa-shopping-cart me-2"></i>
                                            Mua Ngay
                                        </a>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i
                                                    class="fas fa-shield-alt me-1"></i>
                                                Thanh toán an toàn và tự động 24/7
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.row -->
                </div><!-- /.accordion-body -->
            </div><!-- /.accordion-collapse -->
        </div><!-- /.accordion-item -->

        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingOpenPix">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseOpenPix" aria-expanded="false"
                    aria-controls="collapseOpenPix">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-globe fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">
                                Tích Hợp Thanh Toán Qua OpenPix (Brazil)
                            </h5>
                            <small class="text-muted">
                                Hỗ trợ thanh toán nhanh chóng và an toàn qua OpenPix
                            </small>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapseOpenPix" class="accordion-collapse collapse border-top"
                aria-labelledby="headingOpenPix" data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <!-- NỘI DUNG CHI TIẾT -->
                    <div class="row g-4">
                        <!-- Cột mô tả addon -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        Addon này cho phép bạn tích hợp cổng thanh
                                        toán
                                        <strong>OpenPix</strong>
                                        trực tiếp vào website của mình. Khách hàng
                                        của bạn
                                        có thể thanh toán một cách nhanh chóng và
                                        an toàn.
                                        Giao dịch được xử lý an toàn và nhanh chóng,
                                        mang lại trải nghiệm thanh toán tiện lợi.
                                    </p>
                                    <ul class="list-unstyled">
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Hỗ trợ thanh toán nhanh chóng qua
                                                OpenPix.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Giao dịch bảo mật, xác nhận qua
                                                API tự động.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Dễ dàng triển khai, quản lý các
                                                giao dịch.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- Cột demo và giá bán -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-play-circle text-primary me-2"></i>
                                        Demo & Giá Bán
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Phần demo ảnh -->
                                    <div class="position-relative overflow-hidden rounded-3"
                                        style="padding-top: 56.25%;">
                                        <img src="https://i.imgur.com/YBkHmXi.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                            alt="Demo Addon OpenPix">
                                    </div>
                                    <!-- Phần giá bán -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div
                                            class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Giá bán
                                                    Addon</h6>
                                                <small class="text-muted">
                                                    Bản quyền vĩnh viễn. Cấu hình
                                                    API hộ miễn phí lần đầu, lần thứ
                                                    2 sẽ tính phí 300.000đ / lần cấu
                                                    hình hộ.
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="fs-5 fw-bold text-primary">
                                                    1.200.000đ</div>
                                                <small
                                                    class="text-danger text-decoration-line-through">1.500.000đ</small>
                                            </div>
                                        </div>
                                        <!-- Nút mua hàng -->
                                        <a href="https://client.cmsnt.co/cart.php?a=add&pid=81"
                                            target="_blank"
                                            class="btn btn-primary w-100 mt-3">
                                            <i
                                                class="fas fa-shopping-cart me-2"></i>
                                            Mua Ngay
                                        </a>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i
                                                    class="fas fa-shield-alt me-1"></i>
                                                Thanh toán an toàn và tự động 24/7
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.row -->
                </div><!-- /.accordion-body -->
            </div><!-- /.accordion-collapse -->
        </div><!-- /.accordion-item -->

        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingBakong">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseBakong" aria-expanded="false"
                    aria-controls="collapseBakong">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-wallet fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">
                                Tích Hợp Thanh Toán Qua Bakong Wallet (Cambodia)
                            </h5>
                            <small class="text-muted">
                                Hỗ trợ thanh toán nhanh chóng và an toàn qua Bakong Wallet
                            </small>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapseBakong" class="accordion-collapse collapse border-top"
                aria-labelledby="headingBakong" data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <!-- NỘI DUNG CHI TIẾT -->
                    <div class="row g-4">
                        <!-- Cột mô tả addon -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        Addon này cho phép bạn tích hợp cổng thanh
                                        toán
                                        <strong>Bakong Wallet</strong>
                                        trực tiếp vào website của mình. Khách hàng
                                        của bạn
                                        có thể thanh toán một cách nhanh chóng và
                                        an toàn.
                                        Giao dịch được xử lý an toàn và nhanh chóng,
                                        mang lại trải nghiệm thanh toán tiện lợi.
                                    </p>
                                    <ul class="list-unstyled">
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Hỗ trợ thanh toán nhanh chóng qua
                                                Bakong Wallet.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Giao dịch bảo mật, xác nhận qua
                                                API tự động.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Dễ dàng triển khai, quản lý các
                                                giao dịch.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- Cột demo và giá bán -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-play-circle text-primary me-2"></i>
                                        Demo & Giá Bán
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Phần demo ảnh -->
                                    <div class="position-relative overflow-hidden rounded-3"
                                        style="padding-top: 56.25%;">
                                        <img src="https://i.imgur.com/lyY2Lzp.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                            alt="Demo Addon Bakong">
                                    </div>
                                    <!-- Phần giá bán -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div
                                            class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Giá bán
                                                    Addon</h6>
                                                <small class="text-muted">
                                                    Bản quyền vĩnh viễn. Cấu hình
                                                    API hộ miễn phí lần đầu, lần thứ
                                                    2 sẽ tính phí 300.000đ / lần cấu
                                                    hình hộ.
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="fs-5 fw-bold text-primary">
                                                    1.000.000đ</div>
                                                <small
                                                    class="text-danger text-decoration-line-through">1.500.000đ</small>
                                            </div>
                                        </div>
                                        <!-- Nút mua hàng -->
                                        <a href="https://client.cmsnt.co/cart.php?a=add&pid=82"
                                            target="_blank"
                                            class="btn btn-primary w-100 mt-3">
                                            <i
                                                class="fas fa-shopping-cart me-2"></i>
                                            Mua Ngay
                                        </a>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i
                                                    class="fas fa-shield-alt me-1"></i>
                                                Thanh toán an toàn và tự động 24/7
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.row -->
                </div><!-- /.accordion-body -->
            </div><!-- /.accordion-collapse -->
        </div><!-- /.accordion-item -->

        <!-- ADDON POCKETFI -->
        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingPocketfi">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapsePocketfi" aria-expanded="false"
                    aria-controls="collapsePocketfi">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-wallet fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">
                                Tích Hợp Thanh Toán Qua PocketFi (Nigeria)
                            </h5>
                            <small class="text-muted">
                                Hỗ trợ thanh toán nhanh chóng và an toàn qua PocketFi tại Nigeria
                            </small>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapsePocketfi" class="accordion-collapse collapse border-top"
                aria-labelledby="headingPocketfi" data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <!-- NỘI DUNG CHI TIẾT -->
                    <div class="row g-4">
                        <!-- Cột mô tả addon -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        Addon này cho phép bạn tích hợp cổng thanh
                                        toán
                                        <strong>PocketFi (Nigeria)</strong>
                                        trực tiếp vào website của mình. Khách hàng
                                        của bạn
                                        có thể thanh toán một cách nhanh chóng và
                                        an toàn thông qua nhiều kênh thanh toán phổ biến tại Nigeria.
                                        Giao dịch được xử lý an toàn và nhanh chóng,
                                        mang lại trải nghiệm thanh toán tiện lợi.
                                    </p>
                                    <ul class="list-unstyled">
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Hỗ trợ thanh toán nhanh chóng qua
                                                PocketFi.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Giao dịch bảo mật, xác nhận qua
                                                API tự động.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Dễ dàng triển khai, quản lý các
                                                giao dịch.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- Cột demo và giá bán -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-play-circle text-primary me-2"></i>
                                        Demo & Giá Bán
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Phần demo ảnh -->
                                    <div class="position-relative overflow-hidden rounded-3"
                                        style="padding-top: 56.25%;">
                                        <img src="https://i.postimg.cc/mDf9Fyt4/A-nh-ma-n-hi-nh-2025-12-23-lu-c-22-36-12.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-contain bg-light p-4"
                                            alt="Demo Addon PocketFi">
                                    </div>
                                    <!-- Phần giá bán -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div
                                            class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Giá bán
                                                    Addon</h6>
                                                <small class="text-muted">
                                                    Bản quyền vĩnh viễn. Cấu hình
                                                    API hộ miễn phí lần đầu, lần thứ
                                                    2 sẽ tính phí 300.000đ / lần cấu
                                                    hình hộ.
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="fs-5 fw-bold text-primary">
                                                    1.200.000đ</div>
                                                <small
                                                    class="text-danger text-decoration-line-through">1.500.000đ</small>
                                            </div>
                                        </div>
                                        <!-- Nút mua hàng -->
                                        <a href="https://client.cmsnt.co/cart.php?a=add&pid=96"
                                            target="_blank"
                                            class="btn btn-success w-100 mt-3">
                                            <i
                                                class="fas fa-shopping-cart me-2"></i>
                                            Mua Ngay
                                        </a>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i
                                                    class="fas fa-shield-alt me-1"></i>
                                                Thanh toán an toàn và tự động 24/7
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.row -->
                </div><!-- /.accordion-body -->
            </div><!-- /.accordion-collapse -->
        </div><!-- /.accordion-item -->

        <!-- ADDON PAYMENTPOINT -->
        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingPaymentpoint">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapsePaymentpoint" aria-expanded="false"
                    aria-controls="collapsePaymentpoint">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-credit-card fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">
                                Tích Hợp Thanh Toán Qua PaymentPoint (Nigeria)
                            </h5>
                            <small class="text-muted">
                                Hỗ trợ thanh toán qua tài khoản ảo Virtual Account tại Nigeria
                            </small>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapsePaymentpoint" class="accordion-collapse collapse border-top"
                aria-labelledby="headingPaymentpoint" data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <!-- NỘI DUNG CHI TIẾT -->
                    <div class="row g-4">
                        <!-- Cột mô tả addon -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        Addon này cho phép bạn tích hợp cổng thanh
                                        toán
                                        <strong>PaymentPoint (Nigeria)</strong>
                                        trực tiếp vào website của mình. Khách hàng
                                        của bạn
                                        có thể tạo tài khoản ảo (Virtual Account) và
                                        chuyển khoản qua các ngân hàng như PalmPay, OPay.
                                        Giao dịch được xử lý an toàn và nhanh chóng qua webhook.
                                    </p>
                                    <ul class="list-unstyled">
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Tạo tài khoản ảo Virtual Account tự động.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Hỗ trợ nhiều ngân hàng: PalmPay, OPay.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i
                                                class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Xác nhận giao dịch tự động qua webhook.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- Cột demo và giá bán -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div
                                    class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i
                                            class="fas fa-play-circle text-primary me-2"></i>
                                        Demo & Giá Bán
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Phần demo ảnh -->
                                    <div class="position-relative overflow-hidden rounded-3"
                                        style="padding-top: 56.25%;">
                                        <img src="https://i.postimg.cc/jqNRS5pX/A-nh-ma-n-hi-nh-2026-01-02-lu-c-12-41-39.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-contain bg-light p-4"
                                            alt="Demo Addon PaymentPoint">
                                    </div>
                                    <!-- Phần giá bán -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div
                                            class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Giá bán
                                                    Addon</h6>
                                                <small class="text-muted">
                                                    Bản quyền vĩnh viễn. Cấu hình
                                                    API hộ miễn phí lần đầu, lần thứ
                                                    2 sẽ tính phí 300.000đ / lần cấu
                                                    hình hộ.
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="fs-5 fw-bold text-primary">
                                                    1.200.000đ</div>
                                                <small
                                                    class="text-danger text-decoration-line-through">1.500.000đ</small>
                                            </div>
                                        </div>
                                        <!-- Nút mua hàng -->
                                        <a href="https://client.cmsnt.co/cart.php?a=add&pid=97"
                                            target="_blank"
                                            class="btn btn-success w-100 mt-3">
                                            <i
                                                class="fas fa-shopping-cart me-2"></i>
                                            Mua Ngay
                                        </a>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i
                                                    class="fas fa-shield-alt me-1"></i>
                                                Thanh toán an toàn và tự động 24/7
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.row -->
                </div><!-- /.accordion-body -->
            </div><!-- /.accordion-collapse -->
        </div><!-- /.accordion-item -->





        <!-- ADDON PREVIEW UID -->
        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingPreviewUid">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapsePreviewUid" aria-expanded="false"
                    aria-controls="collapsePreviewUid">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-info text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fas fa-eye fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">Xem Trước UID</h5>
                            <small class="text-muted">Cho phép khách hàng xem trước UID trước khi mua hàng</small>
                        </div>
                        <?php
                        $checkPreviewUidAddon = checkAddonLicense($CMSNT->site('preview_uid_license'), 'SHOPCLONE7_PREVIEW_UID');
                        if ($checkPreviewUidAddon['status'] == true): ?>
                            <span class="badge bg-success ms-2">Đã kích hoạt</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2">Chưa kích hoạt</span>
                        <?php endif; ?>
                    </div>
                </button>
            </h2>
            <div id="collapsePreviewUid"
                class="accordion-collapse collapse border-top"
                aria-labelledby="headingPreviewUid"
                data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <div class="row g-4">
                        <!-- Cột mô tả -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        Addon này cho phép bạn bật tính năng
                                        <strong>xem trước UID</strong>
                                        cho từng sản phẩm. Khi bật, khách hàng có thể
                                        xem trước UID trước khi quyết định mua hàng.
                                    </p>
                                    <ul class="list-unstyled">
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Cấu hình ON/OFF cho từng sản phẩm riêng biệt.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Tùy chọn xuất hiện tại trang Thêm/Sửa sản phẩm.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Mặc định OFF, an toàn cho mọi sản phẩm.</span>
                                        </li>
                                    </ul>

                                    <!-- Phần demo ảnh -->
                                    <div class="position-relative overflow-hidden rounded-3 mb-3" style="padding-top: 56.25%;">
                                        <img src="https://i.postimg.cc/SNYYKPPs/A-nh-ma-n-hi-nh-2026-03-23-lu-c-19-44-33.png"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover"
                                            alt="Demo Addon Xem Trước UID" loading="lazy">
                                    </div>

                                    <!-- Phần giá bán Addon -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="fw-semibold mb-1">Giá bán Addon</h6>
                                                <small class="text-muted">Bản quyền vĩnh viễn, hỗ trợ trọn đời</small>
                                            </div>
                                        </div>
                                        <a href="https://client.cmsnt.co/cart.php?a=add&pid=114&carttpl=standard_cart"
                                            target="_blank"
                                            class="btn btn-info w-100 mt-3">
                                            <i class="fas fa-shopping-cart me-2"></i>
                                            Mua Ngay
                                        </a>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-shield-alt me-1"></i>
                                                Thanh toán an toàn và tự động 24/7
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cột kích hoạt -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-key text-warning me-2"></i>
                                        Kích Hoạt Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="preview_uid_license">Giấy phép kích hoạt</label>
                                        <input type="text" class="form-control"
                                            id="preview_uid_license"
                                            placeholder="Nhập license key..."
                                            value="<?= $CMSNT->site('preview_uid_license'); ?>">
                                    </div>

                                    <?php if ($checkPreviewUidAddon['status'] == true): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <?= $checkPreviewUidAddon['msg']; ?>
                                        </div>
                                    <?php elseif (!empty($CMSNT->site('preview_uid_license'))): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-times-circle me-2"></i>
                                            <?= $checkPreviewUidAddon['msg']; ?>
                                        </div>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-primary w-100" id="btnSavePreviewUidLicense">
                                        <i class="fas fa-save me-2"></i>
                                        Lưu License Key
                                    </button>

                                    <div class="alert alert-info mt-3 mb-0">
                                        <i class="fas fa-lightbulb me-2"></i>
                                        <strong>Hướng dẫn:</strong> Sau khi kích hoạt, vào trang Thêm/Sửa sản phẩm để bật tùy chọn "Xem trước UID" cho từng sản phẩm.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var btnSave = document.getElementById('btnSavePreviewUidLicense');
                if (btnSave) {
                    btnSave.addEventListener('click', function() {
                        var licenseKey = document.getElementById('preview_uid_license').value;
                        btnSave.disabled = true;
                        btnSave.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Đang lưu...';
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
                            method: "POST",
                            dataType: "JSON",
                            data: {
                                action: 'updateSetting',
                                name: 'preview_uid_license',
                                value: licenseKey
                            },
                            success: function(result) {
                                if (result.status == 'success') {
                                    showMessage(result.msg, 'success');
                                    setTimeout(function() {
                                        location.reload();
                                    }, 1000);
                                } else {
                                    showMessage(result.msg || 'Có lỗi xảy ra', 'error');
                                }
                            },
                            error: function() {
                                showMessage('Đã xảy ra lỗi khi lưu', 'error');
                            },
                            complete: function() {
                                btnSave.disabled = false;
                                btnSave.innerHTML = '<i class="fas fa-save me-2"></i>Lưu License Key';
                            }
                        });
                    });
                }
            });
        </script>

        <!-- ADDON BOT QUẢN LÝ TELEGRAM -->
        <?php
        // Kiểm tra license addon Bot Quản Lý Telegram để quyết định hiển thị form cấu hình
        $checkBotQuanLyAddon = checkAddonLicense($CMSNT->site('telegram_assistant_LicenseKey'), 'SHOPCLONE7_BOTQUANLY');
        // URL webhook cố định trỏ về endpoint api/webhook_bot_telegram.php (đường dẫn do core quản lý)
        $botQuanLyWebhookUrl = base_url('api/webhook_bot_telegram.php');
        ?>
        <div class="accordion-item border-0 mb-3">
            <h2 class="accordion-header" id="headingBotQuanLy">
                <button
                    class="accordion-button collapsed bg-gradient-primary-hover shadow-sm"
                    type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseBotQuanLy" aria-expanded="false"
                    aria-controls="collapseBotQuanLy">
                    <div class="d-flex align-items-center w-100">
                        <div class="me-3 bg-info text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 36px; height: 36px;">
                            <i class="fa-brands fa-telegram fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-0 fw-semibold text-dark">Bot Quản Lý Telegram</h5>
                            <small class="text-muted">Ra lệnh quản trị website qua Telegram: cộng/trừ tiền, khoá user, xem doanh thu, đơn hàng...</small>
                        </div>
                        <?php if ($checkBotQuanLyAddon['status'] == true): ?>
                            <span class="badge bg-success ms-2">Đã kích hoạt</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2">Chưa kích hoạt</span>
                        <?php endif; ?>
                    </div>
                </button>
            </h2>
            <div id="collapseBotQuanLy" class="accordion-collapse collapse border-top"
                aria-labelledby="headingBotQuanLy" data-bs-parent="#accordionAddons">
                <div class="accordion-body pt-4">
                    <div class="row g-4">

                        <!-- Cột mô tả + demo + giá -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        Mô Tả Addon
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p>
                                        Addon cho phép bạn <strong>quản trị toàn bộ website qua Telegram</strong>.
                                        Chỉ cần tạo 1 Bot, cấp quyền cho username Telegram của bạn, bạn có thể
                                        cộng/trừ tiền user, khoá/mở user, xem đơn hàng, thống kê doanh thu, top users...
                                        ngay trên điện thoại mà không cần đăng nhập Admin Panel.
                                    </p>
                                    <ul class="list-unstyled mb-3">
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Quản lý user: cộng/trừ tiền, khoá, đổi mật khẩu, xem log.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Quản lý đơn hàng: xem đơn gần đây, tra chi tiết 1 đơn.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Thống kê nhanh: doanh thu ngày/tuần/tháng, top nạp tiền, top sản phẩm.</span>
                                        </li>
                                        <li class="d-flex mb-2">
                                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                            <span>Bảo mật với Secret Token, chỉ username trong whitelist mới có quyền gọi lệnh.</span>
                                        </li>
                                    </ul>

                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-terminal me-2"></i>
                                        <strong>Một số lệnh chính:</strong>
                                        <code>/help</code>, <code>/addfund</code>, <code>/removefund</code>,
                                        <code>/balance</code>, <code>/orders</code>, <code>/revenuetoday</code>,
                                        <code>/topusers</code>, <code>/siteinfo</code>...
                                    </div>

                                    <?php if ($checkBotQuanLyAddon['status'] != true): ?>
                                        <!-- Phần giá bán Addon - chỉ hiện khi chưa kích hoạt -->
                                        <div class="mt-4 pt-3 border-top">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <h6 class="fw-semibold mb-1">Giá bán Addon</h6>
                                                    <small class="text-muted">Bản quyền vĩnh viễn, hỗ trợ trọn đời.</small>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fs-5 fw-bold text-primary">500.000đ</div>
                                                    <small class="text-danger text-decoration-line-through">800.000đ</small>
                                                </div>
                                            </div>
                                            <a href="https://client.cmsnt.co/cart.php?a=add&pid=90"
                                                target="_blank"
                                                class="btn btn-info w-100 mt-3">
                                                <i class="fas fa-shopping-cart me-2"></i>
                                                Mua Ngay
                                            </a>
                                            <div class="text-center mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-shield-alt me-1"></i>
                                                    Thanh toán an toàn và tự động 24/7
                                                </small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Hướng dẫn ngắn khi đã active -->
                                        <div class="alert alert-success mb-0">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>Đã kích hoạt!</strong> Cấu hình Bot Token + danh sách username bên phải, sau đó nhấn
                                            <em>"Cập nhật Webhook"</em> để Bot bắt đầu nhận lệnh.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Cột cấu hình / kích hoạt -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-hover">
                                <div class="card-header bg-transparent border-bottom py-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fas fa-cog text-warning me-2"></i>
                                        <?= $checkBotQuanLyAddon['status'] == true ? 'Cấu Hình Bot' : 'Kích Hoạt Addon'; ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- License Key: luôn hiển thị để admin nhập/cập nhật -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="telegram_assistant_LicenseKey">Giấy phép kích hoạt</label>
                                        <input type="text" class="form-control"
                                            id="telegram_assistant_LicenseKey"
                                            placeholder="Nhập license key Addon Bot Quản Lý..."
                                            value="<?= $CMSNT->site('telegram_assistant_LicenseKey'); ?>">
                                    </div>

                                    <?php if ($checkBotQuanLyAddon['status'] == true): ?>
                                        <div class="alert alert-success py-2">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <?= $checkBotQuanLyAddon['msg']; ?>
                                        </div>
                                    <?php elseif (!empty($CMSNT->site('telegram_assistant_LicenseKey'))): ?>
                                        <div class="alert alert-danger py-2">
                                            <i class="fas fa-times-circle me-2"></i>
                                            <?= $checkBotQuanLyAddon['msg']; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($checkBotQuanLyAddon['status'] == true): ?>
                                        <!-- Trạng thái bật/tắt Bot - quyết định webhook có xử lý request hay không -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="telegram_assistant_status">Trạng thái</label>
                                            <select class="form-control" id="telegram_assistant_status">
                                                <option value="1" <?= $CMSNT->site('telegram_assistant_status') == 1 ? 'selected' : ''; ?>>ON - Bật</option>
                                                <option value="0" <?= $CMSNT->site('telegram_assistant_status') == 0 ? 'selected' : ''; ?>>OFF - Tắt</option>
                                            </select>
                                        </div>

                                        <!-- Bot Token lấy từ @BotFather trên Telegram -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="telegram_assistant_token">Bot Token</label>
                                            <input type="text" class="form-control"
                                                id="telegram_assistant_token"
                                                placeholder="VD: 123456789:ABCdefGHIjklMNOpqrSTUvwxYZ"
                                                value="<?= $CMSNT->site('telegram_assistant_token'); ?>">
                                            <small class="text-muted">Tạo Bot mới từ <a href="https://t.me/BotFather" target="_blank">@BotFather</a> để lấy Token.</small>
                                        </div>

                                        <!-- Danh sách username Telegram được phép ra lệnh, phân tách bằng dấu phẩy -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="telegram_assistant_list_username">Username Telegram được phép</label>
                                            <input type="text" class="form-control"
                                                id="telegram_assistant_list_username"
                                                placeholder="VD: username1,username2,username3"
                                                value="<?= $CMSNT->site('telegram_assistant_list_username'); ?>">
                                            <small class="text-muted">Nhập username Telegram (KHÔNG có @), phân tách bằng dấu phẩy. Chỉ các username này mới được quyền ra lệnh cho Bot.</small>
                                        </div>

                                        <!-- Secret Token: Telegram sẽ gửi kèm header X-Telegram-Bot-Api-Secret-Token để webhook xác thực -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold" for="telegram_assistant_secret_token">Secret Token (chống giả mạo request)</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    id="telegram_assistant_secret_token"
                                                    value="<?= $CMSNT->site('telegram_assistant_secret_token'); ?>"
                                                    readonly>
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="copyBotQuanLyField('telegram_assistant_secret_token')" title="Sao chép">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" type="button"
                                                    id="btnRegenTelegramSecret" title="Tạo lại Secret Token">
                                                    <i class="fa fa-rotate"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Sau khi "Tạo lại", phải nhấn "Cập nhật Webhook" để đồng bộ lên Telegram.</small>
                                        </div>

                                        <!-- URL Webhook cố định - admin copy và nhấn cập nhật để đăng ký lên Telegram -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">URL Webhook</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    id="telegram_assistant_webhook_url"
                                                    value="<?= $botQuanLyWebhookUrl; ?>" readonly>
                                                <button class="btn btn-outline-primary" type="button"
                                                    onclick="copyBotQuanLyField('telegram_assistant_webhook_url')" title="Sao chép">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Nhấn "Cập nhật Webhook" bên dưới để hệ thống tự đăng ký URL này cho Bot.</small>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-primary flex-grow-1" id="btnSaveBotQuanLySettings">
                                                <i class="fas fa-save me-2"></i>Lưu cấu hình
                                            </button>
                                            <button type="button" class="btn btn-warning" id="btnSetBotQuanLyWebhook">
                                                <i class="fa fa-sync me-2"></i>Cập nhật Webhook
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary w-100" id="btnSaveBotQuanLyLicense">
                                            <i class="fas fa-save me-2"></i>Lưu License Key
                                        </button>
                                        <div class="alert alert-warning mt-3 mb-0 py-2">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Vui lòng kích hoạt License hợp lệ để mở phần cấu hình Bot.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Hàm copy dùng riêng cho addon Bot Quản Lý (không phụ thuộc ClipboardJS vì lib này chỉ load ở tab telegram-shop)
            function copyBotQuanLyField(inputId) {
                var el = document.getElementById(inputId);
                if (!el) return;
                el.select();
                el.setSelectionRange(0, 99999); // fallback cho mobile khi navigator.clipboard không khả dụng
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(el.value);
                    } else {
                        document.execCommand('copy');
                    }
                    showMessage('Đã sao chép vào bộ nhớ tạm', 'success');
                } catch (e) {
                    showMessage('Không thể sao chép, vui lòng copy thủ công', 'error');
                }
            }

            // Khối JS quản lý toàn bộ thao tác của Addon Bot Quản Lý Telegram
            document.addEventListener('DOMContentLoaded', function() {
                // Nút lưu riêng license khi addon CHƯA active (form cấu hình chưa hiển thị)
                var btnSaveLicense = document.getElementById('btnSaveBotQuanLyLicense');
                if (btnSaveLicense) {
                    btnSaveLicense.addEventListener('click', function() {
                        var licenseKey = document.getElementById('telegram_assistant_LicenseKey').value;
                        btnSaveLicense.disabled = true;
                        btnSaveLicense.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Đang lưu...';
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
                            method: "POST",
                            dataType: "JSON",
                            data: {
                                action: 'update_telegram_assistant_settings',
                                telegram_assistant_LicenseKey: licenseKey
                            },
                            success: function(result) {
                                if (result.status == 'success') {
                                    showMessage(result.msg, 'success');
                                    setTimeout(function() { location.reload(); }, 800);
                                } else {
                                    showMessage(result.msg || 'Có lỗi xảy ra', 'error');
                                }
                            },
                            error: function() { showMessage('Đã xảy ra lỗi khi lưu', 'error'); },
                            complete: function() {
                                btnSaveLicense.disabled = false;
                                btnSaveLicense.innerHTML = '<i class="fas fa-save me-2"></i>Lưu License Key';
                            }
                        });
                    });
                }

                // Nút lưu cấu hình đầy đủ (khi addon đã active) - gửi kèm cả license để admin có thể đổi license mới
                var btnSaveAll = document.getElementById('btnSaveBotQuanLySettings');
                if (btnSaveAll) {
                    btnSaveAll.addEventListener('click', function() {
                        btnSaveAll.disabled = true;
                        btnSaveAll.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Đang lưu...';
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
                            method: "POST",
                            dataType: "JSON",
                            data: {
                                action: 'update_telegram_assistant_settings',
                                telegram_assistant_LicenseKey: document.getElementById('telegram_assistant_LicenseKey').value,
                                telegram_assistant_status: document.getElementById('telegram_assistant_status').value,
                                telegram_assistant_token: document.getElementById('telegram_assistant_token').value,
                                telegram_assistant_list_username: document.getElementById('telegram_assistant_list_username').value
                            },
                            success: function(result) {
                                if (result.status == 'success') {
                                    showMessage(result.msg, 'success');
                                } else {
                                    showMessage(result.msg || 'Có lỗi xảy ra', 'error');
                                }
                            },
                            error: function() { showMessage('Đã xảy ra lỗi khi lưu', 'error'); },
                            complete: function() {
                                btnSaveAll.disabled = false;
                                btnSaveAll.innerHTML = '<i class="fas fa-save me-2"></i>Lưu cấu hình';
                            }
                        });
                    });
                }

                // Nút tạo lại Secret Token - phải confirm vì sau khi đổi cần set lại webhook mới dùng được
                var btnRegen = document.getElementById('btnRegenTelegramSecret');
                if (btnRegen) {
                    btnRegen.addEventListener('click', function() {
                        if (!confirm('Tạo lại Secret Token? Sau khi tạo lại, bạn BẮT BUỘC phải nhấn "Cập nhật Webhook" để Bot hoạt động trở lại.')) return;
                        btnRegen.disabled = true;
                        btnRegen.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
                            method: "POST",
                            dataType: "JSON",
                            data: { action: 'regenerate_telegram_assistant_secret' },
                            success: function(result) {
                                if (result.status == 'success') {
                                    showMessage(result.msg, 'success');
                                    setTimeout(function() { location.reload(); }, 800);
                                } else {
                                    showMessage(result.msg || 'Có lỗi xảy ra', 'error');
                                }
                            },
                            error: function() { showMessage('Đã xảy ra lỗi', 'error'); },
                            complete: function() {
                                btnRegen.disabled = false;
                                btnRegen.innerHTML = '<i class="fa fa-rotate"></i>';
                            }
                        });
                    });
                }

                // Nút đăng ký webhook lên Telegram - gọi API setWebhook + kèm secret_token
                var btnSetWh = document.getElementById('btnSetBotQuanLyWebhook');
                if (btnSetWh) {
                    btnSetWh.addEventListener('click', function() {
                        btnSetWh.disabled = true;
                        btnSetWh.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Đang xử lý...';
                        $.ajax({
                            url: "<?= base_url('ajaxs/admin/update.php'); ?>",
                            method: "POST",
                            dataType: "JSON",
                            data: { action: 'set_telegram_assistant_webhook' },
                            success: function(result) {
                                if (result.status == 'success') {
                                    showMessage(result.msg, 'success');
                                } else {
                                    showMessage(result.msg || 'Có lỗi xảy ra', 'error');
                                }
                            },
                            error: function() { showMessage('Lỗi kết nối máy chủ', 'error'); },
                            complete: function() {
                                btnSetWh.disabled = false;
                                btnSetWh.innerHTML = '<i class="fa fa-sync me-2"></i>Cập nhật Webhook';
                            }
                        });
                    });
                }
            });
        </script>

    </div><!-- End accordion -->
</div>