<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>


<?php if ($CMSNT->site('widget_phone1_status') == 1): ?>
    <div class="hotline-phone-ring-wrap">
        <div class="hotline-phone-ring">
            <div class="hotline-phone-ring-circle"></div>
            <div class="hotline-phone-ring-circle-fill"></div>
            <div class="hotline-phone-ring-img-circle">
                <a href="tel:<?= $CMSNT->site('widget_phone1_sdt'); ?>" class="pps-btn-img">
                    <img src="<?= base_url('mod/img/'); ?>icon-call-nh.png" alt="Gọi điện thoại" width="50">
                </a>
            </div>
        </div>
        <div class="hotline-bar">
            <a href="tel:<?= $CMSNT->site('widget_phone1_sdt'); ?>">
                <span class="text-hotline"><?= $CMSNT->site('widget_phone1_sdt'); ?></span>
            </a>
        </div>
    </div>

    <style>
        .hotline-phone-ring-wrap {
            position: fixed;
            bottom: 20px;
            left: 0;
            z-index: 999999;
        }

        .hotline-phone-ring {
            position: relative;
            visibility: visible;
            background-color: transparent;
            width: 110px;
            height: 110px;
            cursor: pointer;
            z-index: 11;
            -webkit-backface-visibility: hidden;
            -webkit-transform: translateZ(0);
            transition: visibility .5s;
            left: 0;
            bottom: 0;
            display: block;
        }

        .hotline-phone-ring-circle {
            width: 85px;
            height: 85px;
            top: 10px;
            left: 10px;
            position: absolute;
            background-color: transparent;
            border-radius: 100%;
            border: 2px solid #e60808;
            -webkit-animation: phonering-alo-circle-anim 1.2s infinite ease-in-out;
            animation: phonering-alo-circle-anim 1.2s infinite ease-in-out;
            transition: all .5s;
            -webkit-transform-origin: 50% 50%;
            -ms-transform-origin: 50% 50%;
            transform-origin: 50% 50%;
            opacity: 0.5;
        }

        .hotline-phone-ring-circle-fill {
            width: 55px;
            height: 55px;
            top: 25px;
            left: 25px;
            position: absolute;
            background-color: rgba(230, 8, 8, 0.7);
            border-radius: 100%;
            border: 2px solid transparent;
            -webkit-animation: phonering-alo-circle-fill-anim 2.3s infinite ease-in-out;
            animation: phonering-alo-circle-fill-anim 2.3s infinite ease-in-out;
            transition: all .5s;
            -webkit-transform-origin: 50% 50%;
            -ms-transform-origin: 50% 50%;
            transform-origin: 50% 50%;
        }

        .hotline-phone-ring-img-circle {
            background-color: #e60808;
            width: 33px;
            height: 33px;
            top: 37px;
            left: 37px;
            position: absolute;
            background-size: 20px;
            border-radius: 100%;
            border: 2px solid transparent;
            -webkit-animation: phonering-alo-circle-img-anim 1s infinite ease-in-out;
            animation: phonering-alo-circle-img-anim 1s infinite ease-in-out;
            -webkit-transform-origin: 50% 50%;
            -ms-transform-origin: 50% 50%;
            transform-origin: 50% 50%;
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hotline-phone-ring-img-circle .pps-btn-img {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
        }

        .hotline-phone-ring-img-circle .pps-btn-img img {
            width: 20px;
            height: 20px;
        }

        .hotline-bar {
            position: absolute;
            background: rgba(230, 8, 8, 0.75);
            height: 40px;
            width: 180px;
            line-height: 40px;
            border-radius: 3px;
            padding: 0 10px;
            background-size: 100%;
            cursor: pointer;
            transition: all 0.8s;
            -webkit-transition: all 0.8s;
            z-index: 9;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25), 0 10px 10px rgba(0, 0, 0, 0.1);
            border-radius: 50px !important;
            /* width: 175px !important; */
            left: 33px;
            bottom: 37px;
        }

        .hotline-bar>a {
            color: #fff;
            text-decoration: none;
            font-size: 15px;
            font-weight: bold;
            text-indent: 50px;
            display: block;
            letter-spacing: 1px;
            line-height: 40px;
            font-family: Arial;
        }

        .hotline-bar>a:hover,
        .hotline-bar>a:active {
            color: #fff;
        }

        @-webkit-keyframes phonering-alo-circle-anim {
            0% {
                -webkit-transform: rotate(0) scale(0.5) skew(1deg);
                -webkit-opacity: 0.1;
            }

            30% {
                -webkit-transform: rotate(0) scale(0.7) skew(1deg);
                -webkit-opacity: 0.5;
            }

            100% {
                -webkit-transform: rotate(0) scale(1) skew(1deg);
                -webkit-opacity: 0.1;
            }
        }

        @-webkit-keyframes phonering-alo-circle-fill-anim {
            0% {
                -webkit-transform: rotate(0) scale(0.7) skew(1deg);
                opacity: 0.6;
            }

            50% {
                -webkit-transform: rotate(0) scale(1) skew(1deg);
                opacity: 0.6;
            }

            100% {
                -webkit-transform: rotate(0) scale(0.7) skew(1deg);
                opacity: 0.6;
            }
        }

        @-webkit-keyframes phonering-alo-circle-img-anim {
            0% {
                -webkit-transform: rotate(0) scale(1) skew(1deg);
            }

            10% {
                -webkit-transform: rotate(-25deg) scale(1) skew(1deg);
            }

            20% {
                -webkit-transform: rotate(25deg) scale(1) skew(1deg);
            }

            30% {
                -webkit-transform: rotate(-25deg) scale(1) skew(1deg);
            }

            40% {
                -webkit-transform: rotate(25deg) scale(1) skew(1deg);
            }

            50% {
                -webkit-transform: rotate(0) scale(1) skew(1deg);
            }

            100% {
                -webkit-transform: rotate(0) scale(1) skew(1deg);
            }
        }

        @media (max-width: 768px) {
            .hotline-bar {
                display: none;
            }
        }
    </style>
<?php endif ?>


<?php if ($CMSNT->site('widget_zalo1_status') == 1): ?>
    <a href="https://chat.zalo.me/?phone=<?= $CMSNT->site('widget_zalo1_sdt'); ?>" id="linkzalo" target="_blank"
        rel="noopener noreferrer">
        <div id="fcta-zalo-tracking" class="fcta-zalo-mess">
            <span id="fcta-zalo-tracking"><?= __('Chat hỗ trợ'); ?></span>
        </div>
        <div class="fcta-zalo-vi-tri-nut">
            <div id="fcta-zalo-tracking" class="fcta-zalo-nen-nut">
                <div id="fcta-zalo-tracking" class="fcta-zalo-ben-trong-nut"> <svg xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 460.1 436.6">
                        <path fill="currentColor" class="st0"
                            d="M82.6 380.9c-1.8-.8-3.1-1.7-1-3.5 1.3-1 2.7-1.9 4.1-2.8 13.1-8.5 25.4-17.8 33.5-31.5 6.8-11.4 5.7-18.1-2.8-26.5C69 269.2 48.2 212.5 58.6 145.5 64.5 107.7 81.8 75 107 46.6c15.2-17.2 33.3-31.1 53.1-42.7 1.2-.7 2.9-.9 3.1-2.7-.4-1-1.1-.7-1.7-.7-33.7 0-67.4-.7-101 .2C28.3 1.7.5 26.6.6 62.3c.2 104.3 0 208.6 0 313 0 32.4 24.7 59.5 57 60.7 27.3 1.1 54.6.2 82 .1 2 .1 4 .2 6 .2H290c36 0 72 .2 108 0 33.4 0 60.5-27 60.5-60.3v-.6-58.5c0-1.4.5-2.9-.4-4.4-1.8.1-2.5 1.6-3.5 2.6-19.4 19.5-42.3 35.2-67.4 46.3-61.5 27.1-124.1 29-187.6 7.2-5.5-2-11.5-2.2-17.2-.8-8.4 2.1-16.7 4.6-25 7.1-24.4 7.6-49.3 11-74.8 6zm72.5-168.5c1.7-2.2 2.6-3.5 3.6-4.8 13.1-16.6 26.2-33.2 39.3-49.9 3.8-4.8 7.6-9.7 10-15.5 2.8-6.6-.2-12.8-7-15.2-3-.9-6.2-1.3-9.4-1.1-17.8-.1-35.7-.1-53.5 0-2.5 0-5 .3-7.4.9-5.6 1.4-9 7.1-7.6 12.8 1 3.8 4 6.8 7.8 7.7 2.4.6 4.9.9 7.4.8 10.8.1 21.7 0 32.5.1 1.2 0 2.7-.8 3.6 1-.9 1.2-1.8 2.4-2.7 3.5-15.5 19.6-30.9 39.3-46.4 58.9-3.8 4.9-5.8 10.3-3 16.3s8.5 7.1 14.3 7.5c4.6.3 9.3.1 14 .1 16.2 0 32.3.1 48.5-.1 8.6-.1 13.2-5.3 12.3-13.3-.7-6.3-5-9.6-13-9.7-14.1-.1-28.2 0-43.3 0zm116-52.6c-12.5-10.9-26.3-11.6-39.8-3.6-16.4 9.6-22.4 25.3-20.4 43.5 1.9 17 9.3 30.9 27.1 36.6 11.1 3.6 21.4 2.3 30.5-5.1 2.4-1.9 3.1-1.5 4.8.6 3.3 4.2 9 5.8 14 3.9 5-1.5 8.3-6.1 8.3-11.3.1-20 .2-40 0-60-.1-8-7.6-13.1-15.4-11.5-4.3.9-6.7 3.8-9.1 6.9zm69.3 37.1c-.4 25 20.3 43.9 46.3 41.3 23.9-2.4 39.4-20.3 38.6-45.6-.8-25-19.4-42.1-44.9-41.3-23.9.7-40.8 19.9-40 45.6zm-8.8-19.9c0-15.7.1-31.3 0-47 0-8-5.1-13-12.7-12.9-7.4.1-12.3 5.1-12.4 12.8-.1 4.7 0 9.3 0 14v79.5c0 6.2 3.8 11.6 8.8 12.9 6.9 1.9 14-2.2 15.8-9.1.3-1.2.5-2.4.4-3.7.2-15.5.1-31 .1-46.5z">
                        </path>
                    </svg></div>
                <div id="fcta-zalo-tracking" class="fcta-zalo-text"><?= __('Chat ngay'); ?></div>
            </div>
        </div>
    </a>

    <style>
        @keyframes zoom {
            0% {
                transform: scale(.5);
                opacity: 0
            }

            50% {
                opacity: 1
            }

            to {
                opacity: 0;
                transform: scale(1)
            }
        }

        @keyframes lucidgenzalo {

            0%,
            to {
                transform: rotate(-25deg);
            }

            50% {
                transform: rotate(25deg);
            }
        }

        .jscroll-to-top {
            bottom: 100px
        }

        .fcta-zalo-ben-trong-nut svg path {
            fill: #fff
        }

        .fcta-zalo-vi-tri-nut {
            position: fixed;
            bottom: 71px;
            right: 20px;
            z-index: 999
        }

        .fcta-zalo-nen-nut,
        div.fcta-zalo-mess {
            box-shadow: 0 1px 6px rgba(0, 0, 0, .06), 0 2px 32px rgba(0, 0, 0, .16)
        }

        .fcta-zalo-nen-nut {
            width: 50px;
            height: 50px;
            text-align: center;
            color: #fff;
            background: #0068ff;
            border-radius: 50%;
            position: relative
        }

        .fcta-zalo-nen-nut::after,
        .fcta-zalo-nen-nut::before {
            content: "";
            position: absolute;
            border: 1px solid #0068ff;
            background: #0068ff80;
            z-index: -1;
            left: -20px;
            right: -20px;
            top: -20px;
            bottom: -20px;
            border-radius: 50%;
            animation: zoom 1.9s linear infinite
        }

        .fcta-zalo-nen-nut::after {
            animation-delay: .4s
        }

        .fcta-zalo-ben-trong-nut,
        .fcta-zalo-ben-trong-nut i {
            transition: all 1s
        }

        .fcta-zalo-ben-trong-nut {
            position: absolute;
            text-align: center;
            width: 60%;
            height: 60%;
            left: 10px;
            bottom: 31px;
            line-height: 70px;
            font-size: 25px;
            opacity: 1
        }

        .fcta-zalo-ben-trong-nut i {
            animation: lucidgenzalo 1s linear infinite
        }

        .fcta-zalo-nen-nut:hover .fcta-zalo-ben-trong-nut,
        .fcta-zalo-text {
            opacity: 0
        }

        .fcta-zalo-nen-nut:hover i {
            transform: scale(.5);
            transition: all .5s ease-in
        }

        .fcta-zalo-text a {
            text-decoration: none;
            color: #fff
        }

        .fcta-zalo-text {
            position: absolute;
            top: 6px;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 700;
            transform: scaleX(-1);
            transition: all .5s;
            line-height: 1.5
        }

        .fcta-zalo-nen-nut:hover .fcta-zalo-text {
            transform: scaleX(1);
            opacity: 1
        }

        div.fcta-zalo-mess {
            position: fixed;
            bottom: 75px;
            right: 58px;
            z-index: 99;
            background: #fff;
            padding: 7px 25px 7px 15px;
            color: #0068ff;
            border-radius: 50px 0 0 50px;
            font-weight: 700;
            font-size: 15px
        }

        .fcta-zalo-mess span {
            color: #0068ff !important
        }

        span#fcta-zalo-tracking {
            font-family: Roboto;
            line-height: 1.5
        }

        .fcta-zalo-text {
            font-family: Roboto
        }
    </style>

    <script>
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            document.getElementById("linkzalo").href = "https://zalo.me/<?= $CMSNT->site('widget_zalo1_sdt'); ?>";
        }
    </script>
<?php endif ?>

<?php if ($CMSNT->site('widget_fbzalo2_status') == 1): ?>
    <style>
        .giuseart-pc-contact-bar {
            left: 9px;
            bottom: 59px;
            position: fixed;
            z-index: 998;
            margin-bottom: 0
        }

        .giuseart-pc-contact-bar li {
            width: 44px;
            height: 46px;
            overflow: hidden;
            margin-bottom: 0;
            list-style: none;
        }

        .giuseart-pc-contact-bar li.facebook {
            margin-bottom: 8px;
            background: url(<?= base_url('mod/img/icon-mess.webp'); ?>);
            background-repeat: no-repeat;
        }

        .giuseart-pc-contact-bar li.zalo {
            background: url(<?= base_url('mod/img/icon-chat-zalo.webp'); ?>);
            background-repeat: no-repeat;
        }

        .giuseart-pc-contact-bar li a {
            display: block;
            width: 44px;
            height: 44px;
        }
    </style>
    <ul class="giuseart-pc-contact-bar">
        <li class="facebook">
            <a href="<?= $CMSNT->site('widget_fbzalo2_fb'); ?>" target="_blank" rel="nofollow"></a>
        </li>
        <li class="zalo">
            <a href="<?= $CMSNT->site('widget_fbzalo2_zalo'); ?>" target="_blank" rel="nofollow"></a>
        </li>
    </ul>
<?php endif ?>


<?php if ($CMSNT->site('leaderboard_status') == 1): ?>
    <!-- Widget Bảng xếp hạng - Nút trigger phía trên nút Back to Top -->
    <a class="leaderboard-btn" href="javascript:void(0);" onclick="openLeaderboardModal()" title="<?= __('Bảng xếp hạng'); ?>">
        <i class="fa-solid fa-trophy"></i>
    </a>

    <!-- Modal Bảng xếp hạng -->
    <div class="modal fade" id="leaderboardModal" tabindex="-1" aria-labelledby="leaderboardModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content lb-modal">
                <div class="modal-header lb-header">
                    <div class="d-flex align-items-center">
                        <div class="lb-icon-wrap me-3"><i class="fa-solid fa-trophy"></i></div>
                        <div>
                            <h5 class="modal-title mb-0" id="leaderboardModalLabel"><?= __('Bảng Xếp Hạng'); ?></h5>
                            <small class="lb-sub"><?= __('Top chi tiêu nhiều nhất'); ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Tabs thời gian -->
                <?php
                $lb_periods = explode(',', $CMSNT->site('leaderboard_periods') ?: 'daily,weekly,monthly,all_time');
                $period_labels = [
                    'daily'    => __('Hôm nay'),
                    'weekly'   => __('Tuần này'),
                    'monthly'  => __('Tháng này'),
                    'all_time' => __('Tất cả'),
                ];
                ?>
                <div class="lb-tabs-wrap">
                    <?php $first = true;
                    foreach ($lb_periods as $p): if (!isset($period_labels[$p])) continue; ?>
                        <button class="lb-tab <?= $first ? 'active' : ''; ?>" data-period="<?= $p; ?>" onclick="loadLeaderboard('<?= $p; ?>',this)">
                            <?= $period_labels[$p]; ?>
                        </button>
                    <?php $first = false;
                    endforeach; ?>
                </div>
                <div class="modal-body lb-body" id="leaderboard-content">
                    <div class="lb-loading"><i class="fa fa-spinner fa-spin"></i> <?= __('Đang tải...'); ?></div>
                </div>
            </div>
        </div>
    </div>




    <script>
        function openLeaderboardModal() {
            $('#leaderboardModal').modal('show');
            var t = document.querySelector('.lb-tab.active');
            if (t) loadLeaderboard(t.dataset.period, t);
        }

        function loadLeaderboard(period, tabEl) {
            document.querySelectorAll('.lb-tab').forEach(function(t) {
                t.classList.remove('active')
            });
            if (tabEl) tabEl.classList.add('active');
            var c = document.getElementById('leaderboard-content');
            c.innerHTML = '<div class="lb-loading"><i class="fa fa-spinner fa-spin"></i> <?= __("Đang tải..."); ?></div>';
            $.ajax({
                url: '<?= base_url("ajaxs/client/leaderboard.php"); ?>',
                type: 'GET',
                dataType: 'json',
                data: {
                    period: period
                },
                success: function(r) {
                    if (r.status === 'success' && r.data.length > 0) {
                        var h = '';
                        r.data.forEach(function(d) {
                            var tc = d.rank <= 3 ? ' lb-top-' + d.rank : '';
                            if (d.is_me) tc += ' lb-is-me';
                            var rc = d.rank <= 3 ? '<i class="fa-solid ' + (d.rank === 1 ? 'fa-crown' : d.rank === 2 ? 'fa-medal' : 'fa-award') + '"></i>' : d.rank;
                            h += '<div class="lb-row' + tc + '">';
                            h += '<div class="lb-badge">' + rc + '</div>';
                            h += '<div style="flex:1;min-width:0"><div class="lb-name">' + escapeHtml(d.username) + '</div>';
                            h += '<div class="lb-detail">' + d.total_orders + ' <?= __("đơn hàng"); ?></div></div>';
                            h += '<div class="lb-amount">' + d.total_spent + '</div></div>';
                        });
                        c.innerHTML = h;
                    } else {
                        c.innerHTML = '<div class="lb-empty"><i class="fa-solid fa-chart-simple"></i><p><?= __("Chưa có dữ liệu xếp hạng."); ?></p></div>';
                    }
                },
                error: function() {
                    c.innerHTML = '<div class="lb-empty"><i class="fa-solid fa-exclamation-triangle"></i><p><?= __("Không thể tải dữ liệu."); ?></p></div>';
                }
            });
        }

        function escapeHtml(t) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(t));
            return d.innerHTML;
        }
    </script>
<?php endif; ?>

<footer class="footer-part">
    <div class="container">
        <div class="row">
            <div class="col-sm-6 col-xl-4">
                <div class="footer-widget">
                    <a class="footer-logo" href="<?= base_url(); ?>">
                        <img src="<?= BASE_URL($CMSNT->site('logo_light')); ?>" alt="logo"></a>
                    <p class="footer-desc">
                        <?= $CMSNT->site('description'); ?></p>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="footer-widget contact">
                    <h3 class="footer-title"><?= __('Liên hệ'); ?></h3>
                    <ul class="footer-contact">
                        <li><?= $CMSNT->site('icon_email'); ?>
                            <p><?= $CMSNT->site('email'); ?></p>
                        </li>
                        <li><?= $CMSNT->site('icon_hotline'); ?>
                            <p><?= $CMSNT->site('hotline'); ?></p>
                        </li>
                        <li><?= $CMSNT->site('icon_address'); ?>
                            <p><?= $CMSNT->site('address'); ?></p>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="footer-widget">
                    <h3 class="footer-title"><?= __('Liên kết'); ?></h3>
                    <div class="footer-links">
                        <ul>
                            <li><a href="<?= base_url('client/policy'); ?>"><?= __('Chính sách'); ?></a></li>
                            <li><a href="<?= base_url('client/faq'); ?>"><?= __('Câu hỏi thường gặp'); ?></a></li>
                            <li><a href="<?= base_url('client/contact'); ?>"><?= __('Liên hệ chúng tôi'); ?></a></li>
                            <li><a href="<?= base_url('client/document-api'); ?>"><?= __('Tài liệu API'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="footer-bottom">
                    <p class="footer-copytext">© All Copyrights Reserved by <a href="#"><?= $CMSNT->site('title'); ?></a>
                    </p>
                    <div class="footer-card">
                        <?= $CMSNT->site('footer_card'); ?>


                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<style>
    /* Decorative widgets should not keep repainting the desktop interface. */
    @media (min-width: 992px) {
        .hotline-phone-ring-circle,
        .hotline-phone-ring-circle-fill,
        .hotline-phone-ring-img-circle,
        .fcta-zalo-nen-nut::before,
        .fcta-zalo-nen-nut::after,
        .fcta-zalo-ben-trong-nut i {
            -webkit-animation: none !important;
            animation: none !important;
        }
    }
</style>
<script src="<?= BASE_URL('public/client/'); ?>vendor/bootstrap/popper.min.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>vendor/bootstrap/bootstrap.min.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>vendor/countdown/countdown.min.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>vendor/niceselect/nice-select.min.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>vendor/slickslider/slick.min.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>vendor/venobox/venobox.min.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>js/nice-select.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>js/countdown.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>js/accordion.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>js/venobox.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>js/slick.js"></script>
<script src="<?= BASE_URL('public/client/'); ?>js/main.js?v=4"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    flatpickr("#example-flatpickr-range");
</script>
<script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

<?= $body['footer']; ?>
<?= $CMSNT->site('javascript_footer'); ?>
</body>

</html>


<?php if (isCaptchaEnabled()): ?>
    <script>
        // Global Captcha Functions
        window.getCaptchaResponse = function() {
            <?php if (getCaptchaType() === 'Cloudflare'): ?>
                // Cloudflare Turnstile
                const turnstileWidget = document.querySelector('.cf-turnstile');
                if (turnstileWidget) {
                    return turnstile.getResponse(turnstileWidget);
                }
                return '';
            <?php else: ?>
                // Google reCAPTCHA
                if (typeof grecaptcha !== 'undefined') {
                    return grecaptcha.getResponse();
                }
                return '';
            <?php endif; ?>
        };

        window.resetCaptcha = function() {
            <?php if (getCaptchaType() === 'Cloudflare'): ?>
                // Reset Cloudflare Turnstile
                const turnstileWidget = document.querySelector('.cf-turnstile');
                if (turnstileWidget && typeof turnstile !== 'undefined') {
                    turnstile.reset(turnstileWidget);
                }
            <?php else: ?>
                // Reset Google reCAPTCHA
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.reset();
                }
            <?php endif; ?>
        };

        // Callback cho Turnstile (nếu cần)
        window.onTurnstileSuccess = function(token) {
            // Token đã được xử lý tự động
            console.log('Turnstile verification successful');
        };
    </script>
<?php endif; ?>
