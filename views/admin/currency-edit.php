<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Currency editing'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '
<!-- bs-custom-file-input -->
<script src="'.BASE_URL('public/AdminLTE3/').'plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- Page specific script -->
<script>
$(function () {
  bsCustomFileInput.init();
});
</script> 
';
require_once(__DIR__.'/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `currencies` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url('admin/currency-list'));
    }
} else {
    redirect(base_url('admin/currency-list'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_currency') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveCurrency'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    $isUpdate = $CMSNT->update("currencies", [
        'name'          => check_string($_POST['name']),
        'code'          => check_string($_POST['code']),
        'symbol_left'   => !empty($_POST['symbol_left']) ? check_string($_POST['symbol_left']) : NULL,
        'symbol_right'  => !empty($_POST['symbol_right']) ? check_string($_POST['symbol_right']) : NULL,
        'rate'          => !empty($_POST['rate']) ? check_string($_POST['rate']) : 0,
        'decimal_currency'          => !empty($_POST['decimal_currency']) ? check_string($_POST['decimal_currency']) : 0,
        'seperator'     => !empty($_POST['seperator']) ? check_string($_POST['seperator']) : 'dot',
        'display'       => check_string($_POST['display'])
    ], " `id` = '$id' ");
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Chỉnh sửa tiền tệ')." (".check_string($_POST['name']).")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Chỉnh sửa tiền tệ')." (".check_string($_POST['name']).").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công !")){location.href = "'.BASE_URL('admin/currency-list').'";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Thêm thất bại !")){window.history.back().location.reload();}</script>');
    }
}
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Edit Currency</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('currency-list');?>">Currencies</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?=$row['name'];?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CHỈNH SỬA TIỀN TỆ
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <div class="mb-4">
                                        <label class="form-label" for="name">Name:</label>
                                        <input name="name" type="text" value="<?=$row['name'];?>" class="form-control"
                                            id="name" placeholder="Name" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="code">Code:</label>
                                        <select class="form-control" data-trigger name="code" required>
                                            <option value="<?=$row['code'];?>"><?=$row['code'];?></option>
                                            <option value="AED">AED - United Arab Emirates Dirham</option>
                                            <option value="AFN">AFN - Afghanistan Afghani</option>
                                            <option value="ALL">ALL - Albania Lek</option>
                                            <option value="AMD">AMD - Armenia Dram</option>
                                            <option value="ANG">ANG - Netherlands Antilles Guilder</option>
                                            <option value="AOA">AOA - Angola Kwanza</option>
                                            <option value="ARS">ARS - Argentina Peso</option>
                                            <option value="AUD">AUD - Australia Dollar</option>
                                            <option value="AWG">AWG - Aruba Guilder</option>
                                            <option value="AZN">AZN - Azerbaijan New Manat</option>
                                            <option value="BBD">BBD - Barbados Dollar</option>
                                            <option value="BDT">BDT - Bangladesh Taka</option>
                                            <option value="BGN">BGN - Bulgaria Lev</option>
                                            <option value="BHD">BHD - Bahrain Dinar</option>
                                            <option value="BIF">BIF - Burundi Franc</option>
                                            <option value="BMD">BMD - Bermuda Dollar</option>
                                            <option value="BND">BND - Brunei Darussalam Dollar</option>
                                            <option value="BOB">BOB - Bolivia Bolíviano</option>
                                            <option value="BRL">BRL - Brazil Real</option>
                                            <option value="BSD">BSD - Bahamas Dollar</option>
                                            <option value="BTC">BTC - Bitcoin</option>
                                            <option value="BTN">BTN - Bhutan Ngultrum</option>
                                            <option value="BWP">BWP - Botswana Pula</option>
                                            <option value="BYN">BYN - Belarus Ruble</option>
                                            <option value="BZD">BZD - Belize Dollar</option>
                                            <option value="CAD">CAD - Canada Dollar</option>
                                            <option value="CDF">CDF - Congo/Kinshasa Franc</option>
                                            <option value="CHF">CHF - Switzerland Franc</option>
                                            <option value="CLP">CLP - Chile Peso</option>
                                            <option value="CNY">CNY - China Yuan Renminbi</option>
                                            <option value="COP">COP - Colombia Peso</option>
                                            <option value="CRC">CRC - Costa Rica Colon</option>
                                            <option value="CUC">CUC - Cuba Convertible Peso</option>
                                            <option value="CUP">CUP - Cuba Peso</option>
                                            <option value="CVE">CVE - Cape Verde Escudo</option>
                                            <option value="CZK">CZK - Czech Republic Koruna</option>
                                            <option value="DJF">DJF - Djibouti Franc</option>
                                            <option value="DKK">DKK - Denmark Krone</option>
                                            <option value="DOP">DOP - Dominican Republic Peso</option>
                                            <option value="DZD">DZD - Algeria Dinar</option>
                                            <option value="EGP">EGP - Egypt Pound</option>
                                            <option value="ERN">ERN - Eritrea Nakfa</option>
                                            <option value="ETB">ETB - Ethiopia Birr</option>
                                            <option value="ETH">ETH - Ethereum</option>
                                            <option value="EUR">EUR - Euro Member Countries</option>
                                            <option value="FJD">FJD - Fiji Dollar</option>
                                            <option value="GBP">GBP - United Kingdom Pound</option>
                                            <option value="GEL">GEL - Georgia Lari</option>
                                            <option value="GGP">GGP - Guernsey Pound</option>
                                            <option value="GHS">GHS - Ghana Cedi</option>
                                            <option value="GIP">GIP - Gibraltar Pound</option>
                                            <option value="GMD">GMD - Gambia Dalasi</option>
                                            <option value="GNF">GNF - Guinea Franc</option>
                                            <option value="GTQ">GTQ - Guatemala Quetzal</option>
                                            <option value="GYD">GYD - Guyana Dollar</option>
                                            <option value="HKD">HKD - Hong Kong Dollar</option>
                                            <option value="HNL">HNL - Honduras Lempira</option>
                                            <option value="HRK">HRK - Croatia Kuna</option>
                                            <option value="HTG">HTG - Haiti Gourde</option>
                                            <option value="HUF">HUF - Hungary Forint</option>
                                            <option value="IDR">IDR - Indonesia Rupiah</option>
                                            <option value="ILS">ILS - Israel Shekel</option>
                                            <option value="IMP">IMP - Isle of Man Pound</option>
                                            <option value="INR">INR - India Rupee</option>
                                            <option value="IQD">IQD - Iraq Dinar</option>
                                            <option value="IRR">IRR - Iran Rial</option>
                                            <option value="ISK">ISK - Iceland Krona</option>
                                            <option value="JEP">JEP - Jersey Pound</option>
                                            <option value="JMD">JMD - Jamaica Dollar</option>
                                            <option value="JOD">JOD - Jordan Dinar</option>
                                            <option value="JPY">JPY - Japan Yen</option>
                                            <option value="KES">KES - Kenya Shilling</option>
                                            <option value="KGS">KGS - Kyrgyzstan Som</option>
                                            <option value="KHR">KHR - Cambodia Riel</option>
                                            <option value="KMF">KMF - Comoros Franc</option>
                                            <option value="KPW">KPW - Korea (North) Won</option>
                                            <option value="KRW">KRW - Korea (South) Won</option>
                                            <option value="KWD">KWD - Kuwait Dinar</option>
                                            <option value="KYD">KYD - Cayman Islands Dollar</option>
                                            <option value="KZT">KZT - Kazakhstan Tenge</option>
                                            <option value="LAK">LAK - Laos Kip</option>
                                            <option value="LBP">LBP - Lebanon Pound</option>
                                            <option value="LKR">LKR - Sri Lanka Rupee</option>
                                            <option value="LRD">LRD - Liberia Dollar</option>
                                            <option value="LSL">LSL - Lesotho Loti</option>
                                            <option value="LTC">LTC - Litecoin</option>
                                            <option value="LYD">LYD - Libya Dinar</option>
                                            <option value="MAD">MAD - Morocco Dirham</option>
                                            <option value="MDL">MDL - Moldova Leu</option>
                                            <option value="MGA">MGA - Madagascar Ariary</option>
                                            <option value="MKD">MKD - Macedonia Denar</option>
                                            <option value="MMK">MMK - Myanmar (Burma) Kyat</option>
                                            <option value="MNT">MNT - Mongolia Tughrik</option>
                                            <option value="MOP">MOP - Macau Pataca</option>
                                            <option value="MRO">MRO - Mauritania Ouguiya</option>
                                            <option value="MUR">MUR - Mauritius Rupee</option>
                                            <option value="MWK">MWK - Malawi Kwacha</option>
                                            <option value="MXN">MXN - Mexico Peso</option>
                                            <option value="MYR">MYR - Malaysia Ringgit</option>
                                            <option value="MZN">MZN - Mozambique Metical</option>
                                            <option value="NAD">NAD - Namibia Dollar</option>
                                            <option value="NGN">NGN - Nigeria Naira</option>
                                            <option value="NIO">NIO - Nicaragua Cordoba</option>
                                            <option value="NOK">NOK - Norway Krone</option>
                                            <option value="NPR">NPR - Nepal Rupee</option>
                                            <option value="NZD">NZD - New Zealand Dollar</option>
                                            <option value="OMR">OMR - Oman Rial</option>
                                            <option value="PAB">PAB - Panama Balboa</option>
                                            <option value="PEN">PEN - Peru Sol</option>
                                            <option value="PGK">PGK - Papua New Guinea Kina</option>
                                            <option value="PHP">PHP - Philippines Peso</option>
                                            <option value="PKR">PKR - Pakistan Rupee</option>
                                            <option value="PLN">PLN - Poland Zloty</option>
                                            <option value="PYG">PYG - Paraguay Guarani</option>
                                            <option value="QAR">QAR - Qatar Riyal</option>
                                            <option value="RON">RON - Romania New Leu</option>
                                            <option value="RSD">RSD - Serbia Dinar</option>
                                            <option value="RUB">RUB - Russia Ruble</option>
                                            <option value="RWF">RWF - Rwanda Franc</option>
                                            <option value="SAR">SAR - Saudi Arabia Riyal</option>
                                            <option value="SCR">SCR - Seychelles Rupee</option>
                                            <option value="SDG">SDG - Sudan Pound</option>
                                            <option value="SEK">SEK - Sweden Krona</option>
                                            <option value="SGD">SGD - Singapore Dollar</option>
                                            <option value="SHP">SHP - Saint Helena Pound</option>
                                            <option value="SLL">SLL - Sierra Leone Leone</option>
                                            <option value="SOS">SOS - Somalia Shilling</option>
                                            <option value="SPL">SPL - Seborga Luigino</option>
                                            <option value="SRD">SRD - Suriname Dollar</option>
                                            <option value="SVC">SVC - El Salvador Colon</option>
                                            <option value="SYP">SYP - Syria Pound</option>
                                            <option value="SZL">SZL - Swaziland Lilangeni</option>
                                            <option value="THB">THB - Thailand Baht</option>
                                            <option value="TJS">TJS - Tajikistan Somoni</option>
                                            <option value="TMT">TMT - Turkmenistan Manat</option>
                                            <option value="TND">TND - Tunisia Dinar</option>
                                            <option value="TOP">TOP - Tonga Pa'anga</option>
                                            <option value="TRY">TRY - Turkey Lira</option>
                                            <option value="TVD">TVD - Tuvalu Dollar</option>
                                            <option value="TWD">TWD - Taiwan New Dollar</option>
                                            <option value="TZS">TZS - Tanzania Shilling</option>
                                            <option value="UAH">UAH - Ukraine Hryvnia</option>
                                            <option value="UGX">UGX - Uganda Shilling</option>
                                            <option value="USD">USD - United States Dollar</option>
                                            <option value="UYU">UYU - Uruguay Peso</option>
                                            <option value="UZS">UZS - Uzbekistan Som</option>
                                            <option value="VEF">VEF - Venezuela Bolivar</option>
                                            <option value="VND">VND - Viet Nam Dong</option>
                                            <option value="VUV">VUV - Vanuatu Vatu</option>
                                            <option value="WST">WST - Samoa Tala</option>
                                            <option value="YER">YER - Yemen Rial</option>
                                            <option value="ZAR">ZAR - South Africa Rand</option>
                                            <option value="ZMW">ZMW - Zambia Kwacha</option>
                                            <option value="ZWD">ZWD - Zimbabwe Dollar</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="symbol_left">Symbol Left:</label>
                                        <input name="symbol_left" type="text" value="<?=$row['symbol_left'];?>"
                                            class="form-control" id="url" placeholder="Enter Symbol Left" value="">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="symbol_right">Symbol Right:</label>
                                        <input name="symbol_right" type="text" value="<?=$row['symbol_right'];?>"
                                            class="form-control" id="symbol_right" placeholder="Enter Symbol Right"
                                            value="">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-4">
                                        <label class="form-label" for="value">Rate:</label>
                                        <div class="input-group">
                                            <input name="rate" type="text" class="form-control" id="rate"
                                                value="<?=$row['rate'];?>"
                                                placeholder="Bao nhiêu <?=$CMSNT->get_row(" SELECT `code` FROM `currencies` WHERE `display` = 1 AND `default_currency` = 1")['code'];?> cho 1 đơn vị tiền tệ này"
                                                value="" required>
                                            <span class="input-group-text">
                                                <?=$CMSNT->get_row(" SELECT `code` FROM `currencies` WHERE `display` = 1 AND `default_currency` = 1")['code'];?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label"
                                            for="value"><?=__('Decimals (VND is 0 USD is 2)');?>:</label>
                                        <input name="decimal_currency" type="number"
                                            value="<?=$row['decimal_currency'];?>" class="form-control"
                                            id="decimal_currency" placeholder="VND là 0, USD là 2" value="" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="seperator">Seperator:</label>
                                        <select class="form-control" name="seperator" required>
                                            <option <?=$row['seperator'] == 'comma' ? 'selected' : '';?> value="comma">
                                                Comma (,)
                                            </option>
                                            <option <?=$row['seperator'] == 'space' ? 'selected' : '';?> value="space">
                                                Space ( )
                                            </option>
                                            <option <?=$row['seperator'] == 'dot' ? 'selected' : '';?> value="dot">Dot
                                                (.)
                                            </option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="status"><?=__('Status');?>:</label>
                                        <select class="form-control" name="display" required>
                                            <option <?=$row['display'] == 1 ? 'selected' : '';?> value="1">
                                                <?=__('Show');?>
                                            </option>
                                            <option <?=$row['display'] == 0 ? 'selected' : '';?> value="0">
                                                <?=__('Hide');?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger shadow-danger btn-wave"
                                href="<?=base_url_admin('currency-list');?>"><i class="fa fa-fw fa-undo me-1"></i>
                                <?=__('Back');?></a>
                            <button type="submit" name="SaveCurrency" class="btn btn-primary shadow-primary btn-wave"><i
                                    class="fa fa-fw fa-save me-1"></i> <?=__('Save');?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





<?php
require_once(__DIR__.'/footer.php');
?>