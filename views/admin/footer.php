<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>

<?= $CMSNT->site('script_footer_admin'); ?>


<!-- Footer Start -->
<footer class="footer mt-auto py-3 bg-white text-center">
    <div class="container">
        <div class="gtranslate_wrapper"></div>
        <script>
            window.gtranslateSettings = {
                "default_language": "vi",
                "languages": ["vi", "en", "th", "ms", "zh-CN", "tl", "de", "km", "ru", "my", "lo", "tr", "uk", "ko",
                    "zh-TW", "it", "fr", "ar"
                ],
                "wrapper_selector": ".gtranslate_wrapper"
            }
        </script>
        <script src="https://cdn.gtranslate.net/widgets/latest/flags.js" defer></script>
    </div>
</footer>
<!-- Footer End -->

</div>

<!-- Scroll To Top -->
<div class="scrollToTop">
    <span class="arrow"><i class="ri-arrow-up-s-fill fs-20"></i></span>
</div>
<div id="responsive-overlay"></div>
<!-- Scroll To Top -->

<?php if ($CMSNT->site('status_update') == 1): ?>
    <script>
        $(document).ready(function() {
            $.ajax({
                url: '<?= base_url('update.php'); ?>',
                type: 'GET',
                dataType: 'json',
                timeout: 6000, // Tăng timeout lên 6s
                success: function(response) {
                    // Xử lý response JSON
                    if (response.status === 'success') {
                        // Cập nhật thành công
                        var message = response.message;
                        showMessage(message, 'success');

                    }
                },
                error: function(xhr, status, error) {
                    // Log lỗi chi tiết ra console để debug (không hiển thị popup)
                    console.log('Update Check:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });

                    // Không hiển thị popup lỗi vì có thể là:
                    // - Localhost (không được phép update)
                    // - Chức năng update đang tắt
                    // - Đã là phiên bản mới nhất
                    // - Lỗi network thông thường
                    // - Response không phải JSON
                }
            });

            // Chạy install.php để cập nhật database nếu cần
            $.ajax({
                url: "<?= BASE_URL('install.php'); ?>",
                type: "GET",
                success: function(result) {
                    console.log('Database check completed');
                },
                error: function() {
                    console.log('Database check skipped');
                }
            });
        });
    </script>
<?php endif ?>


<!-- Popper JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/@popperjs/core/umd/popper.min.js"></script>

<!-- Bootstrap JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Defaultmenu JS -->
<script src="<?= base_url('public/theme/'); ?>assets/js/defaultmenu.min.js"></script>

<!-- Node Waves JS-->
<script src="<?= base_url('public/theme/'); ?>assets/libs/node-waves/waves.min.js"></script>

<!-- Sticky JS -->
<script src="<?= base_url('public/theme/'); ?>assets/js/sticky.js"></script>

<!-- Simplebar JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/simplebar/simplebar.min.js"></script>
<script src="<?= base_url('public/theme/'); ?>assets/js/simplebar.js"></script>

<!-- Color Picker JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>

<!-- Custom-Switcher JS -->
<script src="<?= base_url('public/theme/'); ?>assets/js/custom-switcher.min.js"></script>
<!-- Internal Swiper JS -->
<script src="<?= base_url('public/theme/'); ?>assets/js/swiper.js"></script>

<!-- Custom JS -->
<script src="<?= base_url('public/theme/'); ?>assets/js/custom.js"></script>


<!-- Prism JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/prismjs/prism.js"></script>
<script src="<?= base_url('public/theme/'); ?>assets/js/prism-custom.js"></script>

<!-- Modal JS -->
<script src="<?= base_url('public/theme/'); ?>assets/js/modal.js"></script>

<!-- Date & Time Picker JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/flatpickr/flatpickr.min.js"></script>
<script src="<?= base_url('public/theme/'); ?>assets/js/date&time_pickers.js"></script>

<!-- Chartjs Chart JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/chart.js/chart.min.js"></script>

<!-- Gallery JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/glightbox/js/glightbox.min.js"></script>

<!-- Choices JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/choices.js/public/assets/scripts/choices.min.js"></script>

<!-- Swiper JS -->
<script src="<?= base_url('public/theme/'); ?>assets/libs/swiper/swiper-bundle.min.js"></script>

<?= $body['footer']; ?>

<script>
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>




</body>

</html>