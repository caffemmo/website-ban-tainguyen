<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
// Cấu hình thông tin header và bật chế độ noindex cho trang đăng ký để tránh công cụ tìm kiếm lập chỉ mục
$body = [
    'title' => __('Đăng ký tài khoản') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords'),
    'noindex' => true
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/wallet.css">
' . renderCaptchaScripts('register') . '
';
$body['footer'] = '

';
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');



?>
<style>
    #password-strength-status {
        margin-top: 10px;
        border-radius: 4px;
        font-weight: bold;
        text-align: center;
        font-size: 14px;
    }

    /* Màu nền và đường viền cho các mức độ mạnh của mật khẩu */
    #password-strength-status.weak {
        color: #ff0000;
        /* Màu chữ đỏ cho mật khẩu yếu */
        background-color: #f8d7da;
        /* Màu nền đỏ nhạt */
        border: 1px solid #f5c6cb;
        /* Đường viền đỏ nhạt */
    }

    #password-strength-status.medium {
        color: #ff9800;
        /* Màu chữ cam cho mật khẩu trung bình */
        background-color: #fff3cd;
        /* Màu nền cam nhạt */
        border: 1px solid #ffeeba;
        /* Đường viền cam nhạt */
    }

    #password-strength-status.strong {
        color: #4caf50;
        /* Màu chữ xanh lá cho mật khẩu mạnh */
        background-color: #d4edda;
        /* Màu nền xanh lá nhạt */
        border: 1px solid #c3e6cb;
        /* Đường viền xanh lá nhạt */
    }
</style>
<style>
    /* Checkbox điều khoản đồng bộ style */
    .agree-terms-row {
        display: flex;
        align-items: center;
        gap: 10px
    }

    #agree-terms {
        width: 18px;
        height: 18px;
        accent-color: #007bff
    }

    .agree-terms-label {
        margin: 0;
        cursor: pointer;
        user-select: none
    }

    .terms-link {
        display: inline-block;
        margin-top: 6px;
        color: #007bff;
        text-decoration: none
    }

    .terms-link:hover {
        text-decoration: underline
    }
</style>
<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
                <div class="user-form-card">
                    <div class="user-form-title">
                        <h2><?= __('Đăng ký tài khoản'); ?></h2>
                        <p><?= __('Vui lòng nhập thông tin đăng ký'); ?></p>
                    </div>
                    <div class="user-form-group">
                        <form class="user-form">
                            <div class="form-group">
                                <input type="hidden" id="csrf_token" value="<?= generate_csrf_token(); ?>">
                                <input type="text" class="form-control" id="register-username" autocomplete="username"
                                    placeholder="<?= __('Tài khoản đăng nhập'); ?>" minlength="3" maxlength="50" required>
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control" id="register-email" autocomplete="email"
                                    placeholder="<?= __('Địa chỉ Email'); ?>" maxlength="100" required>
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" id="register-password" autocomplete="new-password"
                                    placeholder="<?= __('Mật khẩu'); ?>" minlength="6" maxlength="50" required>
                                <div id="password-strength-status"></div>
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" id="register-password-confirm"
                                    placeholder="<?= __('Nhập lại mật khẩu'); ?>" minlength="6" maxlength="50" required>
                            </div>
                            <?php if (isCaptchaEnabledForModule('register')): ?>
                                <center class="mb-3" id="captcha-container">
                                    <?= renderCaptchaWidget('captcha-container', 'register'); ?>
                                </center>
                            <?php endif; ?>
                            <?php if ((int)$CMSNT->site('isConfirmPolicyRegister') == 1): ?>
                                <div class="form-group">
                                    <div class="agree-terms-row">
                                        <input type="checkbox" id="agree-terms">
                                        <label class="agree-terms-label" for="agree-terms">
                                            <?= __('Tôi đồng ý với điều khoản/chính sách'); ?>
                                        </label>
                                    </div>
                                    <input type="hidden" id="agreed_policy" value="0">
                                </div>
                            <?php endif; ?>
                            <div class="form-button">
                                <button type="button" id="btnRegister"><?= __('Đăng Ký'); ?></button>
                                <p><?= __('Bạn đã có tài khoản?'); ?> <a href="<?= base_url('client/login'); ?>"><?= __('Đăng Nhập'); ?></a></p>
                            </div>

                            <?php
                            // Nút đăng ký bằng Google - chỉ hiển thị khi:
                            // 1. Tính năng được bật trong admin settings
                            // 2. Thư viện google/apiclient đã được cài qua Composer
                            if ($CMSNT->site('status_google_login') == 1 && class_exists('Google_Client')):
                                $client = new Google_Client();
                                $client->setClientId($CMSNT->site('google_login_client_id'));
                                $client->setClientSecret($CMSNT->site('google_login_client_secret'));
                                $client->setRedirectUri(base_url('api/callback_google_login.php'));
                                $client->addScope("email");
                                $client->addScope("profile");
                                if (session_status() !== PHP_SESSION_ACTIVE) {
                                    session_start();
                                }
                                // Tạo state ngẫu nhiên để chống CSRF
                                try {
                                    $googleOauthState = bin2hex(random_bytes(32));
                                } catch (Exception $e) {
                                    $googleOauthState = md5(uniqid((string)mt_rand(), true));
                                }
                                $_SESSION['google_oauth_state'] = $googleOauthState;
                                $_SESSION['google_oauth_state_expires'] = time() + 300; // 5 phút
                                $_SESSION['google_oauth_intent'] = 'register'; // Intent là đăng ký
                                $client->setState($googleOauthState);
                                $register_url = $client->createAuthUrl();
                            ?>
                                <div style="text-align:center;margin:15px 0 5px;position:relative;">
                                    <span style="background:#fff;padding:0 12px;color:#888;font-size:13px;position:relative;z-index:1;"><?= __('Hoặc đăng ký với'); ?></span>
                                    <hr style="margin-top:-10px;border-color:#e0e0e0;">
                                </div>
                                <div style="text-align:center;">
                                    <a href="<?= $register_url; ?>" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 24px;border:1px solid #dadce0;border-radius:6px;background:#fff;color:#3c4043;font-size:14px;font-weight:500;text-decoration:none;transition:all .2s;cursor:pointer;width:100%;"
                                        onmouseover="this.style.boxShadow='0 1px 3px rgba(0,0,0,.15)';this.style.borderColor='#c6c6c6';"
                                        onmouseout="this.style.boxShadow='none';this.style.borderColor='#dadce0';">
                                        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4" />
                                            <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.18L12.05 13.56c-.806.54-1.837.86-3.047.86-2.344 0-4.328-1.584-5.036-3.711H.96v2.332C2.44 15.983 5.485 18 9.003 18z" fill="#34A853" />
                                            <path d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.96H.957C.347 6.175 0 7.55 0 9.002c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05" />
                                            <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.428 0 9.002 0 5.485 0 2.44 2.017.96 4.958L3.967 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#EA4335" />
                                        </svg>
                                        <span><?= __('Đăng ký với Google'); ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="user-form-remind">
                    <p><?= __('Bạn chưa có tài khoản?'); ?> <a href="<?= base_url('client/register'); ?>"><?= __('Đăng Ký Ngay'); ?></a></p>
                </div>
            </div>
        </div>
    </div>
</section>


<?php if ((int)$CMSNT->site('isConfirmPolicyRegister') == 1): ?>
    <style>
        /* Modal điều khoản */
        .policy-modal {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            z-index: 1050;
            display: none
        }

        .policy-modal.show {
            display: block
        }

        .policy-modal .policy-modal-overlay {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5)
        }

        .policy-modal .policy-modal-dialog {
            position: relative;
            background: #fff;
            max-width: 800px;
            margin: 5% auto;
            border-radius: 6px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
            display: flex;
            flex-direction: column
        }

        .policy-modal .policy-modal-header {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .policy-modal .policy-modal-header h5 {
            margin: 0;
            font-size: 18px
        }

        .policy-modal .policy-modal-close {
            background: transparent;
            border: 0;
            font-size: 22px;
            line-height: 1;
            cursor: pointer
        }

        .policy-modal .policy-modal-body {
            padding: 0 16px 0 16px;
            max-height: 60vh;
            overflow: hidden
        }

        .policy-modal .policy-content-scroll {
            padding: 12px 0;
            max-height: 60vh;
            overflow-y: auto
        }

        .policy-modal .policy-modal-footer {
            padding: 12px 16px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            justify-content: flex-end
        }

        .policy-modal .btn {
            padding: 8px 14px;
            border-radius: 4px;
            border: 1px solid transparent;
            cursor: pointer
        }

        .policy-modal .btn-secondary {
            background: #e9ecef;
            color: #212529;
            border-color: #dee2e6
        }

        .policy-modal .btn-primary {
            background: #007bff;
            color: #fff
        }

        .policy-modal .btn-primary:disabled {
            background: #6c757d;
            border-color: #6c757d;
            cursor: not-allowed
        }
    </style>

    <div id="policy-modal" class="policy-modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="policy-modal-overlay"></div>
        <div class="policy-modal-dialog">
            <div class="policy-modal-header">
                <h5><?= __('Điều khoản và Chính sách'); ?></h5>
                <button type="button" class="policy-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="policy-modal-body">
                <div id="policy-content" class="policy-content-scroll">
                    <?= $CMSNT->site('policy_register'); ?>
                </div>
            </div>
            <div class="policy-modal-footer">
                <button type="button" class="btn btn-secondary" id="btn-policy-cancel"><?= __('Hủy'); ?></button>
                <button type="button" class="btn btn-primary" id="btn-policy-accept" disabled><?= __('Tôi đã đọc và đồng ý'); ?></button>
            </div>
        </div>
    </div>
<?php endif; ?>


<?php
require_once(__DIR__ . '/footer.php');
?>
<script>
    document.getElementById('register-password').addEventListener('input', function() {
        var password = this.value;
        var strengthStatus = document.getElementById('password-strength-status');

        // Kiểm tra độ mạnh mật khẩu
        var strength = getPasswordStrength(password);

        // Hiển thị độ mạnh của mật khẩu
        strengthStatus.textContent = strength.message;
        strengthStatus.style.color = strength.color;
    });

    function getPasswordStrength(password) {
        var strength = {
            message: "❗ <?= __('Mật khẩu rất yếu'); ?>",
            color: "red"
        };
        var regexes = [
            /[A-Z]/, // Chữ cái viết hoa
            /[a-z]/, // Chữ cái viết thường
            /[0-9]/, // Số
            /[\W_]/, // Ký tự đặc biệt
            /.{8,}/ // Độ dài ít nhất 8 ký tự
        ];
        var passedChecks = regexes.reduce((acc, regex) => acc + regex.test(password), 0);
        if (passedChecks === 5) {
            strength.message = "🔰 <?= __('Mật khẩu mạnh'); ?>";
            strength.color = "green";
        } else if (passedChecks >= 3) {
            strength.message = "⚠️ <?= __('Mật khẩu trung bình'); ?>";
            strength.color = "orange";
        }

        return strength;
    }
</script>
<?php if ((int)$CMSNT->site('isConfirmPolicyRegister') == 1): ?>
    <script>
        // Logic điều khoản đăng ký
        (function() {
            var requirePolicy = true;
            var agreedInput = document.getElementById('agreed_policy');
            var checkbox = document.getElementById('agree-terms');
            var openLink = null;
            var modal = document.getElementById('policy-modal');
            var modalOverlay = modal ? modal.querySelector('.policy-modal-overlay') : null;
            var btnClose = modal ? modal.querySelector('.policy-modal-close') : null;
            var btnCancel = document.getElementById('btn-policy-cancel');
            var btnAccept = document.getElementById('btn-policy-accept');
            var content = document.getElementById('policy-content');

            function refreshPolicyScroll() {
                if (!content) return;
                var h = Math.round(window.innerHeight * 0.60);
                content.style.maxHeight = h + 'px';
                // Ép reflow để khôi phục thanh trượt khi mở lại
                content.style.overflowY = 'hidden';
                void content.offsetHeight; // force reflow
                content.style.overflowY = 'auto';
                content.style.webkitOverflowScrolling = 'touch';
            }

            function openModal() {
                if (!modal) return;
                btnAccept.disabled = false;
                modal.classList.add('show');
                // Sau khi hiển thị modal, reset scroll và khởi tạo lại thanh trượt
                requestAnimationFrame(function() {
                    refreshPolicyScroll();
                    if (content) {
                        content.scrollTop = 0;
                    }
                });
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.remove('show');
            }

            function resetAgreement() {
                if (agreedInput) {
                    agreedInput.value = '0';
                }
                if (checkbox) {
                    checkbox.checked = false;
                }
            }
            if (checkbox) {
                checkbox.addEventListener('change', function(e) {
                    if (this.checked) {
                        // Buộc xem điều khoản trước khi có thể đánh dấu
                        e.preventDefault();
                        this.checked = false;
                        openModal();
                    } else {
                        resetAgreement();
                    }
                });
            }
            if (openLink) {
                openLink.addEventListener('click', function() {
                    openModal();
                });
            }
            if (btnCancel) {
                btnCancel.addEventListener('click', function() {
                    resetAgreement();
                    closeModal();
                });
            }
            if (btnClose) {
                btnClose.addEventListener('click', function() {
                    resetAgreement();
                    closeModal();
                });
            }
            if (modalOverlay) {
                modalOverlay.addEventListener('click', function() {
                    resetAgreement();
                    closeModal();
                });
            }
            // Không yêu cầu cuộn nội dung để bật nút đồng ý
            // Đảm bảo khi đổi kích thước, thanh trượt luôn hiển thị đúng
            window.addEventListener('resize', refreshPolicyScroll);
            if (btnAccept) {
                btnAccept.addEventListener('click', function() {
                    if (agreedInput) {
                        agreedInput.value = '1';
                    }
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                    closeModal();
                });
            }
        })();
    </script>
<?php endif; ?>
<script type="text/javascript">
    $("#btnRegister").on("click", function() {
        // Validate registration inputs
        var username = $("#register-username").val();
        var email = $("#register-email").val();
        var password = $("#register-password").val();
        var repassword = $("#register-password-confirm").val();

        if (!username || username.length < 3) {
            Swal.fire('<?= __('Error'); ?>', '<?= __('Tên đăng nhập phải có ít nhất 3 ký tự'); ?>', 'error');
            return;
        }

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            Swal.fire('<?= __('Error'); ?>', '<?= __('Email không hợp lệ'); ?>', 'error');
            return;
        }

        if (!password || password.length < 6) {
            Swal.fire('<?= __('Error'); ?>', '<?= __('Mật khẩu phải có ít nhất 6 ký tự'); ?>', 'error');
            return;
        }

        if (password !== repassword) {
            Swal.fire('<?= __('Error'); ?>', '<?= __('Mật khẩu xác nhận không khớp'); ?>', 'error');
            return;
        }

        $('#btnRegister').html('<i class="fa fa-spinner fa-spin"></i> <?= __('Đang xử lý...'); ?>').prop('disabled',
            true);
        <?php if ((int)$CMSNT->site('isConfirmPolicyRegister') == 1): ?>
            if ($('#agreed_policy').val() != '1') {
                Swal.fire('<?= __('Failure!'); ?>', '<?= __('Vui lòng đọc và đồng ý điều khoản/chính sách trước khi đăng ký.'); ?>', 'error');
                $('#btnRegister').html('<?= __('Đăng Ký'); ?>').prop('disabled', false);
                return;
            }
        <?php endif; ?>
        <?php if (isCaptchaEnabledForModule('register')): ?>
            var __captchaVal = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
            if (!__captchaVal) {
                Swal.fire('<?= __('Failure!'); ?>', '<?= __('Vui lòng xác nhận Captcha'); ?>', 'error');
                $('#btnRegister').html('<?= __('Đăng Ký'); ?>').prop('disabled', false);
                return;
            }
        <?php endif; ?>
        var ajaxData = {
            action: 'Register',
            csrf_token: $("#csrf_token").val(),
            username: username,
            email: email,
            password: password,
            repassword: repassword
        };
        <?php if (isCaptchaEnabledForModule('register')): ?>
            ajaxData.captcha_response = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
            ajaxData.recaptcha = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
            ajaxData['cf-turnstile-response'] = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : '';
        <?php endif; ?>

        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: ajaxData,
            success: function(respone) {
                if (respone.status == 'success') {
                    Swal.fire({
                        title: '<?= __('Successful!'); ?>',
                        text: respone.msg,
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {

                        }
                    });
                    <?php if ($CMSNT->site('google_analytics_status') == 1): ?>
                        // ✅ Gửi sự kiện về Google Analytics
                        gtag('event', 'sign_up', {
                            method: 'Website Form'
                        });
                    <?php endif ?>

                    <?php if ($CMSNT->site('google_ads_status') == 1): ?>
                        gtag('event', 'conversion', {
                            'send_to': '<?= $CMSNT->site('google_ads_id'); ?>'
                        });
                    <?php endif ?>


                    setTimeout("location.href = '<?= BASE_URL(''); ?>';", 1000);
                } else {
                    Swal.fire('<?= __('Failure!'); ?>', respone.msg, 'error');
                }
                $('#btnRegister').html('<?= __('Đăng Ký'); ?>').prop('disabled', false);
            },
            error: function() {
                showMessage('<?= __('Không thể xử lý'); ?>', 'error');
                $('#btnRegister').html('<?= __('Đăng Ký'); ?>').prop('disabled', false);
            }

        });
    });
</script>