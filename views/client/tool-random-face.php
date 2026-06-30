<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('Random Face').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '

';
$body['footer'] = '

';

if (isSecureCookie('user_login') == true) {
    require_once(__DIR__ . '/../../models/is_user.php');
}

if($CMSNT->site('status_menu_tools') != 1){
    redirect(base_url('client/home'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="posterd home mb-3" style="background-image: url(<?=base_url('mod/img/bg-intro.webp');?>)">
                    <div class="welcomto">
                        <div class="box-intro">
                            <img src="<?=base_url('mod/img/icon-random-face.webp');?>" alt="Accnice" width="70"
                                height="70">
                        </div>
                        <div class="">
                            <div
                                style="font-size: 15px; text-shadow: rgba(0, 0, 0, 0.25) 0px 3px 5px;font-family: Robot,Roboto,sans-serif;">
                                <?=__('Bạn đang xem');?></div>
                            <h1
                                style="color: #fff; font-size: 25px; font-weight:500; margin-top: 10px; text-shadow: rgba(0, 0, 0, 0.25) 0px 3px 5px;font-family: Robot,Roboto,sans-serif;">
                                <?=__('Tool Random Face');?></h1>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once(__DIR__.'/widget_tools.php');?>
            <div class="mb-5"></div>

            <div class="col-12 text-center">
                <button type="button" class="btn btn-primary fw-semibold px-3 py-2 mb-3" onclick="tai_lai_trang()"
                    cursorshover="true"><i class="fa-solid fa-rotate-right"></i> CHANGE FACE </button>
            </div>
            <div class="col-12 text-center">

                <iframe id="iframe" src="https://thispersondoesnotexist.com" width="100%" style="height:1000px"
                    frameborder="0" scrolling="auto">
                </iframe>

            </div>
        </div>
    </div>
</section>



<?php
require_once(__DIR__.'/footer.php');
?>
<script>
// Tải lại trang
function tai_lai_trang() {
    location.reload();
}
</script>