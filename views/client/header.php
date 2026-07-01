<?php if (!defined('IN_SITE')) {
  die('The Request Not Found');
}
checkMaintenance();
?>
<!doctype html>
<html class="h-100">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
  <link rel="canonical" href="<?= url(); ?>" />
  <title><?= isset($body['title']) ? $body['title'] : $CMSNT->site('title'); ?></title>
  <meta name="description" content="<?= isset($body['desc']) ? $body['desc'] : $CMSNT->site('description'); ?>" />
  <meta name="keywords" content="<?= isset($body['keyword']) ? $body['keyword'] : $CMSNT->site('keywords'); ?>">
  <?php // Kiểm tra nếu trang yêu cầu không lập chỉ mục tìm kiếm (noindex)
  if (isset($body['noindex']) && $body['noindex'] === true): ?>
  <meta name="robots" content="noindex, nofollow" />
  <?php endif; ?>
  <meta name="copyright" content="<?= $CMSNT->site('author'); ?>" />
  <meta name="author" content="<?= $CMSNT->site('author'); ?>" />
  <meta property="og:url" content="<?= base_url(''); ?>">
  <meta property="og:site_name" content="<?= base_url(); ?>" />
  <meta property="og:title" content="<?= $body['title']; ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:image"
    content="<?= isset($body['image']) ? $body['image'] : BASE_URL($CMSNT->site('image')); ?>" />
  <meta property="og:image:secure"
    content="<?= isset($body['image']) ? $body['image'] : BASE_URL($CMSNT->site('image')); ?>" />
  <meta name="twitter:title" content="<?= $body['title']; ?>" />
  <meta name="twitter:image"
    content="<?= isset($body['image']) ? $body['image'] : BASE_URL($CMSNT->site('image')); ?>" />
  <meta name="twitter:image:alt" content="<?= $body['title']; ?>" />
  <link rel="icon" type="image/png" href="<?= BASE_URL($CMSNT->site('favicon')); ?>" />
  <style>
    :root {
      --primary: <?= $CMSNT->site('theme_color');
                  ?>;
    }
  </style>
  <style>
    :root {
      --primary1: <?= $CMSNT->site('theme_color1');
                  ?>;
    }
  </style>

  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>fonts/flaticon/flaticon.css">
  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>fonts/icofont/icofont.min.css">
  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>fonts/fontawesome/fontawesome.min.css">
  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>vendor/venobox/venobox.min.css">
  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>vendor/slickslider/slick.min.css">
  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>vendor/niceselect/nice-select.min.css">
  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>vendor/bootstrap/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>css/user-auth.css">
  <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>css/index.css">

  <link rel="stylesheet" href="<?= BASE_URL('public/fontawesome/'); ?>css/all.min.css">

  <!-- sweetalert2-->
  <link class="main-stylesheet" href="<?= BASE_URL('public/'); ?>sweetalert2/default.css" rel="stylesheet"
    type="text/css">
  <script src="<?= BASE_URL('public/'); ?>sweetalert2/sweetalert2.js"></script>
  <!-- Cute Alert -->
  <link class="main-stylesheet" href="<?= BASE_URL('public/'); ?>cute-alert/style.css" rel="stylesheet" type="text/css">
  <script src="<?= BASE_URL('public/'); ?>cute-alert/cute-alert.js"></script>
  <!-- Simple Notify CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-notify@1.0.4/dist/simple-notify.css" />
  <!-- Simple Notify JS -->
  <script src="https://cdn.jsdelivr.net/npm/simple-notify@1.0.4/dist/simple-notify.min.js"></script>

  <!-- jQuery -->
  <script src="<?= base_url('public/js/jquery-3.6.0.js'); ?>"></script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">

  <?php if ($CMSNT->site('google_analytics_status') == 1): ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $CMSNT->site('google_analytics_id'); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];

      function gtag() {
        dataLayer.push(arguments);
      }
      gtag('js', new Date());

      gtag('config', '<?= $CMSNT->site('google_analytics_id'); ?>');
    </script>
  <?php endif ?>
  <?php if ($CMSNT->site('reCAPTCHA_status') == 1): ?>
    <!-- reCaptcha -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif ?>
  <?= $body['header']; ?>
  <link rel="stylesheet" href="<?= BASE_URL('mod/css/main.css?v=28'); ?>">
  <script src="<?= base_url('mod/js/main.js?v=2'); ?>"></script>
  <?= $CMSNT->site('javascript_header'); ?>

</head>

<script>
  // Khai báo biến global
  var baseUrl = '<?= base_url(); ?>';
  var BASE_URL = '<?= BASE_URL(); ?>';

  function showMessage(message, type) {
    const commonOptions = {
      effect: 'fade',
      speed: 300,
      customClass: null,
      customIcon: null,
      showIcon: true,
      showCloseButton: true,
      autoclose: true,
      autotimeout: 3000,
      gap: 20,
      distance: 20,
      type: 'outline',
      position: 'right top'
    };

    const options = {
      success: {
        status: 'success',
        title: '<?= __("Thành công!"); ?>',
        text: message,
      },
      error: {
        status: 'error',
        title: '<?= __("Thất bại!"); ?>',
        text: message,
      }
    };
    new Notify(Object.assign({}, commonOptions, options[type]));
  }
</script>

<style>
  body {
    <?= $CMSNT->site('font_family');
    ?>
  }

  html {
    scroll-behavior: smooth;
  }

  .feature-content {
    padding-left: 0px;
    border-left: none;
  }

  .product-disable::before {
    position: absolute;
    content: "<?= __('Hết hàng'); ?>";
    top: 89%;
    left: 50%;
    z-index: 2;
    width: 100%;
    font-size: 15px;
    font-weight: 400;
    padding: 0px;
    text-align: center;
    text-transform: uppercase;
    text-shadow: var(--primary-tshadow);
    -webkit-transform: translate(-50%, -50%);
    transform: translate(-50%, -50%);
    color: var(--white);
    background: rgba(224, 152, 22, 0.9);
  }
</style>
