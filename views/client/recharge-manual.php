<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

 
if (isset($_GET['slug'])) {
    $slug = validate_slug($_GET['slug'], 100);
    if ($slug === false || !$payment_manual1 = $CMSNT->get_row_safe("SELECT * FROM `payment_manual` WHERE `slug` = ? AND `display` = 1", [$slug])) {
        redirect(base_url());
    }
} else {
    redirect(base_url());
}

$body = [
    'title' => __($payment_manual1['title']).' | '.$CMSNT->site('title'),
    'desc'   => $payment_manual1['description'],
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">
';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_user.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');

?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="account-card p-3">
                    <?php
                    $content = base64_decode($payment_manual1['content']);
                    $content = str_replace('{fanpage}', $CMSNT->site('fanpage'), $content);
                    $content = str_replace('{email}', $CMSNT->site('email'), $content);
                    $content = str_replace('{hotline}', $CMSNT->site('hotline'), $content);
                    $content = str_replace('{id}', $getUser['id'], $content);
                    $content = str_replace('{username}', $getUser['username'], $content);
                    ?>
                    <?=$content;?>
                </div>
            </div>
        </div>
    </div>
</section>


<?php
require_once(__DIR__.'/footer.php');
?>

<script type="text/javascript">
new ClipboardJS(".copy");

function copy() {
    showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
}
</script>
 
 