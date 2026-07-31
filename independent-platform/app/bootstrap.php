<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/views');

require_once APP_PATH . '/Support/functions.php';
require_once APP_PATH . '/Support/Logger.php';
require_once APP_PATH . '/Database.php';
require_once APP_PATH . '/Support/Auth.php';
require_once APP_PATH . '/Support/Settings.php';
require_once APP_PATH . '/Support/Router.php';
require_once APP_PATH . '/Support/View.php';
require_once APP_PATH . '/Providers/ProviderInterface.php';
require_once APP_PATH . '/Providers/HttpJsonProvider.php';
require_once APP_PATH . '/Providers/YouProxyProvider.php';
require_once APP_PATH . '/Providers/SocialProvider.php';
require_once APP_PATH . '/Providers/BankProvider.php';
require_once APP_PATH . '/Services/AuditService.php';
require_once APP_PATH . '/Services/CatalogService.php';
require_once APP_PATH . '/Services/WalletService.php';
require_once APP_PATH . '/Services/PaymentService.php';
require_once APP_PATH . '/Services/OrderService.php';
require_once APP_PATH . '/Services/ProxyService.php';
require_once APP_PATH . '/Services/SocialService.php';

load_environment(BASE_PATH . '/.env');
configure_session();

function app_booted(): bool
{
    return true;
}
