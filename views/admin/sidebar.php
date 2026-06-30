<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
?>

<body>

    <!-- Start Switcher -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="switcher-canvas" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title text-default" id="offcanvasRightLabel">Switcher</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="border-bottom border-block-end-dashed">
                <div class="nav nav-tabs nav-justified" id="switcher-main-tab" role="tablist">
                    <button class="nav-link active" id="switcher-home-tab" data-bs-toggle="tab"
                        data-bs-target="#switcher-home" type="button" role="tab" aria-controls="switcher-home"
                        aria-selected="true">Theme Styles</button>
                    <button class="nav-link" id="switcher-profile-tab" data-bs-toggle="tab"
                        data-bs-target="#switcher-profile" type="button" role="tab" aria-controls="switcher-profile"
                        aria-selected="false">Theme Colors</button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active border-0" id="switcher-home" role="tabpanel"
                    aria-labelledby="switcher-home-tab" tabindex="0">
                    <div class="">
                        <p class="switcher-style-head">Theme Color Mode:</p>
                        <div class="row switcher-style gx-0">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-light-theme">
                                        Light
                                    </label>
                                    <input class="form-check-input" type="radio" name="theme-style"
                                        id="switcher-light-theme" checked>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-dark-theme">
                                        Dark
                                    </label>
                                    <input class="form-check-input" type="radio" name="theme-style"
                                        id="switcher-dark-theme">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <p class="switcher-style-head">Directions:</p>
                        <div class="row switcher-style gx-0">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-ltr">
                                        LTR
                                    </label>
                                    <input class="form-check-input" type="radio" name="direction" id="switcher-ltr"
                                        checked>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-rtl">
                                        RTL
                                    </label>
                                    <input class="form-check-input" type="radio" name="direction" id="switcher-rtl">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <p class="switcher-style-head">Navigation Styles:</p>
                        <div class="row switcher-style gx-0">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-vertical">
                                        Vertical
                                    </label>
                                    <input class="form-check-input" type="radio" name="navigation-style"
                                        id="switcher-vertical" checked>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-horizontal">
                                        Horizontal
                                    </label>
                                    <input class="form-check-input" type="radio" name="navigation-style"
                                        id="switcher-horizontal">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="navigation-menu-styles">
                        <p class="switcher-style-head">Vertical & Horizontal Menu Styles:</p>
                        <div class="row switcher-style gx-0 pb-2 gy-2">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-menu-click">
                                        Menu Click
                                    </label>
                                    <input class="form-check-input" type="radio" name="navigation-menu-styles"
                                        id="switcher-menu-click">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-menu-hover">
                                        Menu Hover
                                    </label>
                                    <input class="form-check-input" type="radio" name="navigation-menu-styles"
                                        id="switcher-menu-hover">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-icon-click">
                                        Icon Click
                                    </label>
                                    <input class="form-check-input" type="radio" name="navigation-menu-styles"
                                        id="switcher-icon-click">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-icon-hover">
                                        Icon Hover
                                    </label>
                                    <input class="form-check-input" type="radio" name="navigation-menu-styles"
                                        id="switcher-icon-hover">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="sidemenu-layout-styles">
                        <p class="switcher-style-head">Sidemenu Layout Styles:</p>
                        <div class="row switcher-style gx-0 pb-2 gy-2">
                            <div class="col-sm-6">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-default-menu">
                                        Default Menu
                                    </label>
                                    <input class="form-check-input" type="radio" name="sidemenu-layout-styles"
                                        id="switcher-default-menu" checked>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-closed-menu">
                                        Closed Menu
                                    </label>
                                    <input class="form-check-input" type="radio" name="sidemenu-layout-styles"
                                        id="switcher-closed-menu">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-icontext-menu">
                                        Icon Text
                                    </label>
                                    <input class="form-check-input" type="radio" name="sidemenu-layout-styles"
                                        id="switcher-icontext-menu">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-icon-overlay">
                                        Icon Overlay
                                    </label>
                                    <input class="form-check-input" type="radio" name="sidemenu-layout-styles"
                                        id="switcher-icon-overlay">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-detached">
                                        Detached
                                    </label>
                                    <input class="form-check-input" type="radio" name="sidemenu-layout-styles"
                                        id="switcher-detached">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-double-menu">
                                        Double Menu
                                    </label>
                                    <input class="form-check-input" type="radio" name="sidemenu-layout-styles"
                                        id="switcher-double-menu">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <p class="switcher-style-head">Page Styles:</p>
                        <div class="row switcher-style gx-0">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-regular">
                                        Regular
                                    </label>
                                    <input class="form-check-input" type="radio" name="page-styles"
                                        id="switcher-regular" checked>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-classic">
                                        Classic
                                    </label>
                                    <input class="form-check-input" type="radio" name="page-styles"
                                        id="switcher-classic">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-modern">
                                        Modern
                                    </label>
                                    <input class="form-check-input" type="radio" name="page-styles"
                                        id="switcher-modern">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <p class="switcher-style-head">Layout Width Styles:</p>
                        <div class="row switcher-style gx-0">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-full-width">
                                        Full Width
                                    </label>
                                    <input class="form-check-input" type="radio" name="layout-width"
                                        id="switcher-full-width" checked>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-boxed">
                                        Boxed
                                    </label>
                                    <input class="form-check-input" type="radio" name="layout-width"
                                        id="switcher-boxed">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <p class="switcher-style-head">Menu Positions:</p>
                        <div class="row switcher-style gx-0">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-menu-fixed">
                                        Fixed
                                    </label>
                                    <input class="form-check-input" type="radio" name="menu-positions"
                                        id="switcher-menu-fixed" checked>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-menu-scroll">
                                        Scrollable
                                    </label>
                                    <input class="form-check-input" type="radio" name="menu-positions"
                                        id="switcher-menu-scroll">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <p class="switcher-style-head">Header Positions:</p>
                        <div class="row switcher-style gx-0">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-header-fixed">
                                        Fixed
                                    </label>
                                    <input class="form-check-input" type="radio" name="header-positions"
                                        id="switcher-header-fixed" checked>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-header-scroll">
                                        Scrollable
                                    </label>
                                    <input class="form-check-input" type="radio" name="header-positions"
                                        id="switcher-header-scroll">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <p class="switcher-style-head">Loader:</p>
                        <div class="row switcher-style gx-0">
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-loader-enable">
                                        Enable
                                    </label>
                                    <input class="form-check-input" type="radio" name="page-loader"
                                        id="switcher-loader-enable">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-check switch-select">
                                    <label class="form-check-label" for="switcher-loader-disable">
                                        Disable
                                    </label>
                                    <input class="form-check-input" type="radio" name="page-loader"
                                        id="switcher-loader-disable" checked>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade border-0" id="switcher-profile" role="tabpanel"
                    aria-labelledby="switcher-profile-tab" tabindex="0">
                    <div>
                        <div class="theme-colors">
                            <p class="switcher-style-head">Menu Colors:</p>
                            <div class="d-flex switcher-style pb-2">
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-white" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Light Menu" type="radio" name="menu-colors"
                                        id="switcher-menu-light">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-dark" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Dark Menu" type="radio" name="menu-colors"
                                        id="switcher-menu-dark" checked>
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Color Menu" type="radio" name="menu-colors"
                                        id="switcher-menu-primary">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-gradient" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Gradient Menu" type="radio" name="menu-colors"
                                        id="switcher-menu-gradient">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-transparent"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Transparent Menu"
                                        type="radio" name="menu-colors" id="switcher-menu-transparent">
                                </div>
                            </div>
                            <div class="px-4 pb-3 text-muted fs-11">Note:If you want to change color Menu dynamically
                                change from below Theme Primary color picker</div>
                        </div>
                        <div class="theme-colors">
                            <p class="switcher-style-head">Header Colors:</p>
                            <div class="d-flex switcher-style pb-2">
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-white" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Light Header" type="radio" name="header-colors"
                                        id="switcher-header-light" checked>
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-dark" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Dark Header" type="radio" name="header-colors"
                                        id="switcher-header-dark">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Color Header" type="radio" name="header-colors"
                                        id="switcher-header-primary">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-gradient" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Gradient Header" type="radio"
                                        name="header-colors" id="switcher-header-gradient">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-transparent"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Transparent Header"
                                        type="radio" name="header-colors" id="switcher-header-transparent">
                                </div>
                            </div>
                            <div class="px-4 pb-3 text-muted fs-11">Note:If you want to change color Header dynamically
                                change from below Theme Primary color picker</div>
                        </div>
                        <div class="theme-colors">
                            <p class="switcher-style-head">Theme Primary:</p>
                            <div class="d-flex flex-wrap align-items-center switcher-style">
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary-1" type="radio"
                                        name="theme-primary" id="switcher-primary">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary-2" type="radio"
                                        name="theme-primary" id="switcher-primary1">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary-3" type="radio"
                                        name="theme-primary" id="switcher-primary2">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary-4" type="radio"
                                        name="theme-primary" id="switcher-primary3">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-primary-5" type="radio"
                                        name="theme-primary" id="switcher-primary4">
                                </div>
                                <div class="form-check switch-select ps-0 mt-1 color-primary-light">
                                    <div class="theme-container-primary"></div>
                                    <div class="pickr-container-primary"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-colors">
                            <p class="switcher-style-head">Theme Background:</p>
                            <div class="d-flex flex-wrap align-items-center switcher-style">
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-bg-1" type="radio"
                                        name="theme-background" id="switcher-background">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-bg-2" type="radio"
                                        name="theme-background" id="switcher-background1">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-bg-3" type="radio"
                                        name="theme-background" id="switcher-background2">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-bg-4" type="radio"
                                        name="theme-background" id="switcher-background3">
                                </div>
                                <div class="form-check switch-select me-3">
                                    <input class="form-check-input color-input color-bg-5" type="radio"
                                        name="theme-background" id="switcher-background4">
                                </div>
                                <div
                                    class="form-check switch-select ps-0 mt-1 tooltip-static-demo color-bg-transparent">
                                    <div class="theme-container-background"></div>
                                    <div class="pickr-container-background"></div>
                                </div>
                            </div>
                        </div>
                        <div class="menu-image mb-3">
                            <p class="switcher-style-head">Menu With Background Image:</p>
                            <div class="d-flex flex-wrap align-items-center switcher-style">
                                <div class="form-check switch-select m-2">
                                    <input class="form-check-input bgimage-input bg-img1" type="radio"
                                        name="theme-background" id="switcher-bg-img">
                                </div>
                                <div class="form-check switch-select m-2">
                                    <input class="form-check-input bgimage-input bg-img2" type="radio"
                                        name="theme-background" id="switcher-bg-img1">
                                </div>
                                <div class="form-check switch-select m-2">
                                    <input class="form-check-input bgimage-input bg-img3" type="radio"
                                        name="theme-background" id="switcher-bg-img2">
                                </div>
                                <div class="form-check switch-select m-2">
                                    <input class="form-check-input bgimage-input bg-img4" type="radio"
                                        name="theme-background" id="switcher-bg-img3">
                                </div>
                                <div class="form-check switch-select m-2">
                                    <input class="form-check-input bgimage-input bg-img5" type="radio"
                                        name="theme-background" id="switcher-bg-img4">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-grid canvas-footer">
                    <a href="javascript:void(0);" id="reset-all" class="btn btn-danger m-1">Reset</a>
                </div>
            </div>
        </div>
    </div>
    <!-- End Switcher -->


    <!-- Loader -->
    <div id="loader">
        <img src="<?= base_url('public/theme/'); ?>assets/images/media/loader.svg" alt="">
    </div>
    <!-- Loader -->

    <div class="page">
        <!-- app-header -->
        <header class="app-header">

            <!-- Start::main-header-container -->
            <div class="main-header-container container-fluid">

                <!-- Start::header-content-left -->
                <div class="header-content-left">

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <div class="horizontal-logo">
                            <a href="<?= base_url_admin(); ?>" class="header-logo">
                            </a>
                        </div>
                    </div>
                    <!-- End::header-element -->

                    <!-- Start::header-element -->
                    <div class="header-element">
                        <!-- Start::header-link -->
                        <a aria-label="Hide Sidebar"
                            class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle"
                            data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
                        <!-- End::header-link -->
                    </div>
                    <!-- End::header-element -->
                </div>
                <!-- End::header-content-left -->

                <!-- Start::header-content-right -->
                <div class="header-content-right">
                    <div class="header-element header-search">
                        <!-- Start::header-link -->
                        <a href="<?= base_url(); ?>" class="header-link">
                            <i class="bx bx-user-circle header-link-icon"></i>
                        </a>
                        <!-- End::header-link -->
                    </div>


                    <!-- Start::header-element -->
                    <div class="header-element header-theme-mode">
                        <!-- Start::header-link|layout-setting -->
                        <a href="javascript:void(0);" class="header-link layout-setting">
                            <span class="light-layout">
                                <!-- Start::header-link-icon -->
                                <i class="bx bx-moon header-link-icon"></i>
                                <!-- End::header-link-icon -->
                            </span>
                            <span class="dark-layout">
                                <!-- Start::header-link-icon -->
                                <i class="bx bx-sun header-link-icon"></i>
                                <!-- End::header-link-icon -->
                            </span>
                        </a>
                        <!-- End::header-link|layout-setting -->
                    </div>
                    <!-- End::header-element -->






                    <!-- Start::header-element -->
                    <div class="header-element header-fullscreen">
                        <!-- Start::header-link -->
                        <a onclick="openFullscreen();" href="#" class="header-link">
                            <i class="bx bx-fullscreen full-screen-open header-link-icon"></i>
                            <i class="bx bx-exit-fullscreen full-screen-close header-link-icon d-none"></i>
                        </a>
                        <!-- End::header-link -->
                    </div>
                    <!-- End::header-element -->




                    <!-- Start::header-element -->
                    <div class="header-element">
                        <!-- Start::header-link|switcher-icon -->
                        <a href="#" class="header-link switcher-icon" data-bs-toggle="offcanvas"
                            data-bs-target="#switcher-canvas">
                            <i class="bx bx-cog header-link-icon"></i>
                        </a>
                        <!-- End::header-link|switcher-icon -->
                    </div>
                    <!-- End::header-element -->

                </div>
                <!-- End::header-content-right -->

            </div>
            <!-- End::main-header-container -->

        </header>
        <!-- /app-header -->
        <!-- Start::app-sidebar -->
        <aside class="app-sidebar sticky" id="sidebar">

            <!-- Start::main-sidebar-header -->
            <div class="main-sidebar-header">
                <a href="<?= base_url_admin(); ?>" class="header-logo">
                </a>
            </div>
            <!-- End::main-sidebar-header -->

            <!-- Start::main-sidebar -->
            <div class="main-sidebar" id="sidebar-scroll">

                <!-- Start::nav -->
                <nav class="main-menu-container nav nav-pills flex-column sub-open">
                    <div class="slide-left" id="slide-left">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24"
                            viewBox="0 0 24 24">
                            <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
                        </svg>
                    </div>
                    <ul class="main-menu">
                        <li class="slide__category"><span class="category-name">Main</span></li>
                        <li class="slide">
                            <a href="<?= base_url_admin('home'); ?>"
                                class="side-menu__item <?= active_sidebar(['home', '']); ?>">
                                <i class="bx bxs-dashboard side-menu__icon" style="color: #3b82f6;"></i>
                                <span class="side-menu__label">Dashboard</span>
                            </a>
                        </li>
                        <li class="slide has-sub <?= show_sidebar(['logs', 'transactions', 'log-auto-bank', 'telegram-logs', 'telegram-queue', 'admin-logs', 'failed-attempts-logs', 'email-queue']); ?>">
                            <a href="javascript:void(0);"
                                class="side-menu__item <?= show_sidebar(['logs', 'transactions']); ?>">
                                <i class='bx bx-history side-menu__icon' style='color: #8b5cf6;'></i>
                                <span class="side-menu__label">Lịch sử</span>
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1">
                                <?php if (checkPermission($getUser['admin'], 'view_logs') == true): ?>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('logs'); ?>"
                                            class="side-menu__item <?= active_sidebar(['logs']); ?>">Nhật ký hoạt
                                            động</a>
                                    </li>
                                <?php endif ?>
                                <?php if (checkPermission($getUser['admin'], 'view_transactions') == true): ?>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('transactions'); ?>"
                                            class="side-menu__item <?= active_sidebar(['transactions']); ?>">Biến động
                                            số dư</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('log-auto-bank'); ?>"
                                            class="side-menu__item <?= active_sidebar(['log-auto-bank']); ?>">Lịch sử ngân
                                            hàng</a>
                                    </li>
                                <?php endif ?>
                                <?php if (checkPermission($getUser['admin'], 'view_telegram_logs') == true): ?>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('telegram-logs'); ?>"
                                            class="side-menu__item <?= active_sidebar(['telegram-logs']); ?>">Telegram Logs</a>
                                    </li>
                                <?php endif ?>
                                <?php if (checkPermission($getUser['admin'], 'view_logs') == true): ?>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('email-queue'); ?>"
                                            class="side-menu__item <?= active_sidebar(['email-queue']); ?>">Email Queue</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('telegram-queue'); ?>"
                                            class="side-menu__item <?= active_sidebar(['telegram-queue']); ?>">Telegram Queue</a>
                                    </li>
                                <?php endif ?>
                                <?php if ($getUser['admin'] == 99999): ?>
                                    <!-- <li class="slide">
                                    <a href="<?= base_url_admin('admin-logs'); ?>"
                                        class="side-menu__item <?= active_sidebar(['admin-logs']); ?>">Admin Logs</a>
                                </li> -->
                                <?php endif ?>
                                <!-- <?php if (checkPermission($getUser['admin'], 'view_block_ip') == true): ?>
                                <li class="slide">
                                    <a href="<?= base_url_admin('failed-attempts-logs'); ?>"
                                        class="side-menu__item <?= active_sidebar(['failed-attempts-logs']); ?>">Failed Attempts Logs</a>
                                </li>
                                <?php endif ?> -->
                            </ul>
                        </li>
                        <?php if (checkPermission($getUser['admin'], 'view_automations') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('automations'); ?>"
                                    class="side-menu__item <?= active_sidebar(['automations', 'automation-edit']); ?>">
                                    <i class="bx bxs-calendar side-menu__icon" style="color: #14b8a6;"></i>
                                    <span class="side-menu__label">Tự động hóa</span>
                                </a>
                            </li>
                        <?php endif ?>
                        <li class="slide__category"><span class="category-name">Bảo mật</span></li>
                        <?php if (checkPermission($getUser['admin'], 'view_block_ip') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('block-ip'); ?>"
                                    class="side-menu__item <?= active_sidebar(['block-ip']); ?>">
                                    <i class="bx bx-block side-menu__icon" style="color: #ef4444;"></i>
                                    <span class="side-menu__label">Block IP</span>
                                </a>
                            </li>
                        <?php endif ?>
                        <li class="slide__category"><span class="category-name">Dịch vụ</span></li>
                        <?php if (checkPermission($getUser['admin'], 'view_product') == true): ?>
                            <li
                                class="slide has-sub <?= show_sidebar(['category-add', 'product-warehouse', 'product-sold', 'product-api-manager', 'product-api-edit', 'product-api-add', 'product-api', 'product-orders', 'categories', 'category-edit', 'products', 'product-add', 'product-edit', 'product-stock']); ?>">
                                <a href="javascript:void(0);"
                                    class="side-menu__item <?= show_sidebar(['category-add', 'product-warehouse', 'product-sold', 'product-api-manager', 'product-api-edit', 'product-api-add', 'product-api', 'product-orders', 'categories', 'category-edit', 'products', 'product-add', 'product-edit', 'product-stock']); ?>">
                                    <i class='bx bx-cart side-menu__icon' style='color: #f97316;'></i>
                                    <span class="side-menu__label">Sản phẩm</span>
                                    <i class="fe fe-chevron-right side-menu__angle"></i>
                                </a>
                                <ul class="slide-menu child1">
                                    <li class="slide">
                                        <a href="<?= base_url_admin('categories'); ?>"
                                            class="side-menu__item <?= active_sidebar(['category-add', 'categories', 'category-edit']); ?>">Chuyên
                                            mục</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('products'); ?>"
                                            class="side-menu__item <?= active_sidebar(['products', 'product-add', 'product-edit', 'product-stock']); ?>">Tất
                                            cả sản phẩm</a>
                                    </li>
                                    <?php if (checkPermission($getUser['admin'], 'manager_suppliers') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('product-api'); ?>"
                                                class="side-menu__item <?= active_sidebar(['product-api-manager', 'product-api-edit', 'product-api', 'product-api-add']); ?>">Kết
                                                nối API</a>
                                        </li>
                                    <?php endif ?>
                                    <?php if (checkPermission($getUser['admin'], 'view_orders_product') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('product-orders'); ?>"
                                                class="side-menu__item <?= active_sidebar(['product-orders']); ?>">Đơn hàng</a>
                                        </li>
                                    <?php endif ?>
                                    <?php if (checkPermission($getUser['admin'], 'view_sold_product') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('product-sold'); ?>"
                                                class="side-menu__item <?= active_sidebar(['product-sold']); ?>">Tài khoản đã bán</a>
                                        </li>
                                    <?php endif ?>
                                    <?php if (checkPermission($getUser['admin'], 'edit_stock_product') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('product-warehouse'); ?>"
                                                class="side-menu__item <?= active_sidebar(['product-warehouse']); ?>">Tài khoản trong kho</a>
                                        </li>
                                    <?php endif ?>
                                </ul>
                            </li>
                        <?php endif ?>


                        <li class="slide__category"><span class="category-name">Quản lý</span></li>
                        <?php if (checkPermission($getUser['admin'], 'view_ctv') == true): ?>
                            <li
                                class="slide has-sub <?= show_sidebar(['ctv-withdraw', 'ctv-statistics', 'ctv-config', 'ctv-pending-products']); ?>">
                                <a href="javascript:void(0);"
                                    class="side-menu__item <?= show_sidebar(['ctv-withdraw', 'ctv-statistics', 'ctv-config', 'ctv-pending-products']); ?>">
                                    <i class='bx bxs-group side-menu__icon' style='color: #ec4899;'></i>
                                    <span class="side-menu__label">CTV Panel</span>
                                    <i class="fe fe-chevron-right side-menu__angle"></i>
                                </a>
                                <ul class="slide-menu child1">
                                    <?php if (checkPermission($getUser['admin'], 'view_statistics_ctv') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('ctv-statistics'); ?>"
                                                class="side-menu__item <?= active_sidebar(['ctv-statistics']); ?>">Thống kê</a>
                                        </li>
                                    <?php endif ?>
                                    <?php if (checkPermission($getUser['admin'], 'view_withdraw_ctv') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('ctv-withdraw'); ?>"
                                                class="side-menu__item <?= active_sidebar(['ctv-withdraw']); ?>">Đơn rút tiền</a>
                                        </li>
                                    <?php endif ?>
                                    <?php if (checkPermission($getUser['admin'], 'view_product') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('ctv-pending-products'); ?>"
                                                class="side-menu__item <?= active_sidebar(['ctv-pending-products']); ?>">
                                                Sản phẩm chờ duyệt
                                            </a>
                                        </li>
                                    <?php endif ?>
                                    <?php if (checkPermission($getUser['admin'], 'edit_config_ctv') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('ctv-config'); ?>"
                                                class="side-menu__item <?= active_sidebar(['ctv-config']); ?>">Cấu hình</a>
                                        </li>
                                    <?php endif ?>
                                </ul>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'view_user') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('users'); ?>"
                                    class="side-menu__item <?= active_sidebar(['users', 'user-edit']); ?>">
                                    <i class="bx bxs-user side-menu__icon" style="color: #06b6d4;"></i>
                                    <span class="side-menu__label">Thành viên</span>
                                </a>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'view_role') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('roles'); ?>"
                                    class="side-menu__item <?= active_sidebar(['roles', 'role-edit']); ?>">
                                    <i class="bx bxs-check-shield side-menu__icon" style="color: #eab308;"></i>
                                    <span class="side-menu__label">Admin Role</span>
                                </a>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'view_recharge') == true): ?>
                            <li
                                class="slide has-sub <?= show_sidebar(['recharge-thesieure', 'recharge-flutterwave', 'recharge-momo', 'recharge-card', 'recharge-bank', 'recharge-crypto', 'recharge-bank-edit', 'recharge-paypal', 'recharge-perfectmoney', 'recharge-toyyibpay', 'recharge-squadco', 'recharge-bank-config', 'recharge-manual', 'recharge-manual-edit', 'recharge-xipay', 'recharge-lempay', 'recharge-tripay', 'recharge-korapay', 'recharge-pocketfi', 'recharge-tmweasyapi', 'recharge-bakong', 'recharge-openpix', 'recharge-dsociopay', 'recharge-zinipay']); ?>">
                                <a href="javascript:void(0);"
                                    class="side-menu__item <?= active_sidebar(['recharge-thesieure', 'recharge-flutterwave', 'recharge-momo', 'recharge-card', 'recharge-bank', 'recharge-crypto', 'recharge-bank-edit', 'recharge-paypal', 'recharge-perfectmoney', 'recharge-toyyibpay', 'recharge-squadco', 'recharge-bank-config', 'recharge-manual', 'recharge-manual-edit', 'recharge-xipay', 'recharge-lempay', 'recharge-tripay', 'recharge-korapay', 'recharge-pocketfi', 'recharge-tmweasyapi', 'recharge-bakong', 'recharge-openpix', 'recharge-dsociopay', 'recharge-zinipay']); ?>">
                                    <i class='bx bxs-wallet-alt side-menu__icon' style='color: #22c55e;'></i>
                                    <span class="side-menu__label">Nạp tiền</span>
                                    <i class="fe fe-chevron-right side-menu__angle"></i>
                                </a>
                                <ul class="slide-menu child1">
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-bank'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-bank', 'recharge-bank-edit', 'recharge-bank-config']); ?>">Ngân
                                            hàng</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-card'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-card']); ?>">Nạp thẻ cào</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-crypto'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-crypto']); ?>">Crypto USDT</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-momo'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-momo']); ?>">Ví MOMO</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-thesieure'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-thesieure']); ?>">Ví THESIEURE</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-paypal'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-paypal']); ?>">Paypal</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-perfectmoney'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-perfectmoney']); ?>">Perfect
                                            Money</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-toyyibpay'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-toyyibpay']); ?>">Toyyibpay
                                            Malaysia</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-squadco'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-squadco']); ?>">Squadco
                                            Nigeria</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-flutterwave'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-flutterwave']); ?>">Flutterwave</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-xipay'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-xipay']); ?>">XiPay (AliPay, WechatPay)</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-lempay'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-lempay']); ?>">LemPay (AliPay, WechatPay, USDT)</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-tripay'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-tripay']); ?>">TriPay Indonesia</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-zinipay'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-zinipay']); ?>">ZiniPay (bKash, Nagad)</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-korapay'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-korapay']); ?>">Korapay Africa</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-pocketfi'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-pocketfi']); ?>">PocketFi Nigeria</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-paymentpoint'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-paymentpoint']); ?>">PaymentPoint Nigeria</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-dsociopay'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-dsociopay']); ?>">DSocioPay</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-tmweasyapi'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-tmweasyapi']); ?>">Tmweasyapi Thailand</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-openpix'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-openpix']); ?>">OpenPix Brazil</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-bakong'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-bakong']); ?>">Bakong Wallet Cambodia</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('recharge-manual'); ?>"
                                            class="side-menu__item <?= active_sidebar(['recharge-manual', 'recharge-manual-edit']); ?>">Manual Payment</a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'view_affiliate') == true): ?>
                            <li
                                class="slide has-sub <?= show_sidebar(['affiliate-config', 'affiliate-withdraw', 'affiliate-history', 'affiliate-links']); ?>">
                                <a href="javascript:void(0);"
                                    class="side-menu__item <?= show_sidebar(['affiliate-config', 'affiliate-withdraw', 'affiliate-history', 'affiliate-links']); ?>">
                                    <i class='bx bx-group side-menu__icon' style='color: #a855f7;'></i>
                                    <span class="side-menu__label">Affiliate Program</span>
                                    <i class="fe fe-chevron-right side-menu__angle"></i>
                                </a>
                                <ul class="slide-menu child1">
                                    <li class="slide">
                                        <a href="<?= base_url_admin('affiliate-links'); ?>"
                                            class="side-menu__item <?= active_sidebar(['affiliate-links']); ?>">Danh sách
                                            liên kết</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('affiliate-history'); ?>"
                                            class="side-menu__item <?= active_sidebar(['affiliate-history']); ?>">Nhật
                                            ký hoa hồng</a>
                                    </li>
                                    <?php if (checkPermission($getUser['admin'], 'view_withdraw_affiliate') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('affiliate-withdraw'); ?>"
                                                class="side-menu__item <?= active_sidebar(['affiliate-withdraw']); ?>">Rút
                                                tiền
                                                <?php
                                                $total_widthdraw_pending = $CMSNT->get_row(" SELECT COUNT(id) FROM `aff_withdraw` WHERE `status` = 'pending' ")['COUNT(id)'];
                                                if ($total_widthdraw_pending > 0): ?>
                                                    <span
                                                        class="badge bg-warning-transparent ms-2"><?= $total_widthdraw_pending; ?></span>
                                                <?php endif ?>
                                            </a>
                                        </li>
                                    <?php endif ?>
                                    <?php if (checkPermission($getUser['admin'], 'edit_affiliate') == true): ?>
                                        <li class="slide">
                                            <a href="<?= base_url_admin('affiliate-config'); ?>"
                                                class="side-menu__item <?= active_sidebar(['affiliate-config']); ?>">Cấu
                                                hình</a>
                                        </li>
                                    <?php endif ?>
                                </ul>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'view_email_campaigns') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('email-campaigns'); ?>"
                                    class="side-menu__item <?= active_sidebar(['email-campaigns', 'email-campaign-add', 'email-campaign-edit', 'email-sending-view']); ?>">
                                    <i class="bx bx-mail-send side-menu__icon" style="color: #3b82f6;"></i>
                                    <span class="side-menu__label">Email Campaigns</span>
                                </a>
                            </li>
                        <?php endif ?>

                        <?php if (checkPermission($getUser['admin'], 'view_coupon') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('coupons'); ?>"
                                    class="side-menu__item <?= active_sidebar(['coupons']); ?>">
                                    <i class="bx bxs-discount side-menu__icon" style="color: #f97316;"></i>
                                    <span class="side-menu__label">Mã giảm giá</span>
                                </a>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'view_promotion') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('promotions'); ?>"
                                    class="side-menu__item <?= active_sidebar(['promotions']); ?>">
                                    <i class="fa-solid fa-percent side-menu__icon" style="color: #ec4899;"></i>
                                    <span class="side-menu__label">Khuyến mãi nạp tiền</span>
                                </a>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'view_blog') == true): ?>
                            <li
                                class="slide has-sub <?= show_sidebar(['blog-add', 'blogs', 'blog-edit', 'blog-category', 'blog-category-edit']); ?>">
                                <a href="javascript:void(0);"
                                    class="side-menu__item <?= show_sidebar(['blog-add', 'blogs', 'blog-edit', 'blog-category', 'blog-category-edit']); ?>">
                                    <i class='bx bxl-blogger side-menu__icon' style='color: #14b8a6;'></i>
                                    <span class="side-menu__label">Bài viết</span>
                                    <i class="fe fe-chevron-right side-menu__angle"></i>
                                </a>
                                <ul class="slide-menu child1">
                                    <li class="slide">
                                        <a href="<?= base_url_admin('blog-add'); ?>"
                                            class="side-menu__item <?= active_sidebar(['blog-add']); ?>">Viết bài mới</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('blogs'); ?>"
                                            class="side-menu__item <?= active_sidebar(['blogs', 'blog-edit']); ?>">Tất cả bài
                                            viết</a>
                                    </li>
                                    <li class="slide">
                                        <a href="<?= base_url_admin('blog-category'); ?>"
                                            class="side-menu__item <?= active_sidebar(['blog-category', 'blog-category-edit']); ?>">Chuyên
                                            mục</a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif ?>
                        <li class="slide__category"><span class="category-name">Cài đặt hệ thống</span></li>
                        <?php if (checkPermission($getUser['admin'], 'view_menu') == true): ?>
                            <!-- <li class="slide">
                            <a href="<?= base_url_admin('menu-list'); ?>"
                                class="side-menu__item <?= active_sidebar(['menu-list', 'menu-edit']); ?>">
                                <i class="bx bx-sitemap side-menu__icon"></i>
                                <span class="side-menu__label">Menu</span>
                            </a>
                        </li> -->
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'view_lang') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('language-list'); ?>"
                                    class="side-menu__item <?= active_sidebar(['language-list', 'language-add', 'language-edit', 'translate-list']); ?>">
                                    <i class="las la-language side-menu__icon" style="color: #6366f1;"></i>
                                    <span class="side-menu__label">Ngôn ngữ</span>
                                </a>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'view_currency') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('currency-list'); ?>"
                                    class="side-menu__item <?= active_sidebar(['currency-list', 'currency-add', 'currency-edit']); ?>">
                                    <i class="bx bx-dollar side-menu__icon" style="color: #10b981;"></i>
                                    <span class="side-menu__label">Tiền tệ</span>
                                </a>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'edit_theme') == true): ?>
                            <li class="slide">
                                <a href="<?= base_url_admin('theme'); ?>"
                                    class="side-menu__item <?= active_sidebar(['theme']); ?>">
                                    <i class="bx bxs-image side-menu__icon" style="color: #a855f7;"></i>
                                    <span class="side-menu__label">Giao diện</span>
                                </a>
                            </li>
                        <?php endif ?>
                        <?php if (checkPermission($getUser['admin'], 'edit_setting') == true): ?>
                            <li class="slide mb-5">
                                <a href="<?= base_url_admin('settings'); ?>"
                                    class="side-menu__item <?= active_sidebar(['settings']); ?>">
                                    <i class="bx bx-cog side-menu__icon" style="color: #64748b;"></i>
                                    <span class="side-menu__label">Cài đặt</span>
                                </a>
                            </li>
                        <?php endif ?>
                    </ul>
                    <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191"
                            width="24" height="24" viewBox="0 0 24 24">
                            <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
                        </svg></div>
                </nav>
                <!-- End::nav -->

            </div>
            <!-- End::main-sidebar -->

        </aside>
        <!-- End::app-sidebar -->

        <script>
            function changeLanguage(id) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/client/update.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'changeLanguage',
                        id: id
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, result.status);
                            location.reload();
                        } else {
                            showMessage(result.msg, result.status);
                        }
                    },
                    error: function() {
                        alert(html(result));
                        history.back();
                    }
                });
            }
        </script>
        <script>
            function changeCurrency(id) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/client/update.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'changeCurrency',
                        id: id
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, result.status);
                            location.reload();
                        } else {
                            showMessage(result.msg, result.status);
                        }
                    },
                    error: function() {
                        alert(html(result));
                        history.back();
                    }
                });
            }
        </script>