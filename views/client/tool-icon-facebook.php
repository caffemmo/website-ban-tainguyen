<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('Icon Facebook').' | '.$CMSNT->site('title'),
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
                            <img src="<?=base_url('mod/img/icon-facebook.png');?>" alt="Accnice" width="70" height="70">
                        </div>
                        <div class="">
                            <div
                                style="font-size: 15px; text-shadow: rgba(0, 0, 0, 0.25) 0px 3px 5px;font-family: Robot,Roboto,sans-serif;">
                                <?=__('Bạn đang xem');?></div>
                            <h1
                                style="color: #fff; font-size: 25px; font-weight:500; margin-top: 10px; text-shadow: rgba(0, 0, 0, 0.25) 0px 3px 5px;font-family: Robot,Roboto,sans-serif;">
                                <?=__('Tool Icon Facebook');?></h1>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once(__DIR__.'/widget_tools.php');?>
            <div class="mb-5"></div>


            <div class="col-md-12">
                <div class="account-card pt-3">
                    <iframe src="https://www.smileysapp.com/emojiPicker/" class="border border-dark" width="100%"
                        style="height:calc(100vh - 150px)"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>



<?php
require_once(__DIR__.'/footer.php');
?>