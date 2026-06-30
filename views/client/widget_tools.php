<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
?>

<?php if($CMSNT->site('status_menu_tools') == 1):?>
<div class="col-md-12">
    <div class="all-product-main row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-3">
            <div class="box-product-main"
                style="background-image: url(<?=base_url('mod/img/bg-1.webp');?>);box-shadow: 0 2px 10px rgb(36 194 138 / 19%);">
                <a href="<?=base_url('tool/check-live-facebook');?>" cursorshover="true">
                    <div class="d-flex align-items-center">
                        <div class="box-img-product">
                            <img src="<?=base_url('mod/img/icon-facebook.png');?>" width="45" height="45">
                        </div>
                        <div class="box-text-style">
                            <div style="white-space: nowrap;color: #fff;font-weight: 700;font-size: 18px;">
                                <?=__('Check live FB');?></div>
                            <div style="white-space: nowrap;font-size: 15px;color: #fff;opacity: .7;">
                                <?=__('Miễn phí');?></div>

                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-3">
            <div class="box-product-main"
                style="background-image: url(<?=base_url('mod/img/bg-2.webp');?>);box-shadow: 0 2px 10px rgb(0 147 255 / 27%);">
                <a href="<?=base_url('tool/get-2fa');?>" cursorshover="true">
                    <div class="d-flex align-items-center">
                        <div class="box-img-product">
                            <img src="<?=base_url('mod/img/icon-google-auth.webp');?>" width="45"
                                height="45">
                        </div>
                        <div class="box-text-style">

                            <div style="white-space: nowrap;color: #fff;font-weight: 700;font-size: 18px;">
                                <?=__('Lấy mã 2FA');?></div>

                            <div style="white-space: nowrap;font-size: 15px;color: #fff;opacity: .7;">
                                <?=__('Miễn phí');?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-3">
            <div class="box-product-main"
                style="background-image: url(<?=base_url('mod/img/bg-3.webp');?>);box-shadow: 0 2px 10px rgb(0 147 255 / 27%);">
                <a href="<?=base_url('tool/icon-facebook');?>" cursorshover="true">
                    <div class="d-flex align-items-center">
                        <div class="box-img-product">
                            <img src="<?=base_url('mod/img/icon-facebook.png');?>" width="45" height="45">
                        </div>
                        <div class="box-text-style">
                            <div style="white-space: nowrap;color: #fff;font-weight: 700;font-size: 18px;">
                                <?=__('Icon Facebook');?></div>
                            <div style="white-space: nowrap;font-size: 15px;color: #fff;opacity: .7;">
                                <?=__('Miễn phí');?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-3">
            <div class="box-product-main"
                style="background-image: url(<?=base_url('mod/img/bg-4.webp');?>);box-shadow: 0 2px 10px rgb(0 147 255 / 27%);">
                <a href="<?=base_url('tool/random-face');?>" cursorshover="true">
                    <div class="d-flex align-items-center">
                        <div class="box-img-product">
                            <img src="<?=base_url('mod/img/icon-random-face.webp');?>" width="45"
                                height="45">
                        </div>
                        <div class="box-text-style">
                            <div style="white-space: nowrap;color: #fff;font-weight: 700;font-size: 18px;">
                                <?=__('Random Face');?></div>
                            <div style="white-space: nowrap;font-size: 15px;color: #fff;opacity: .7;">
                                <?=__('Miễn phí');?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif?>