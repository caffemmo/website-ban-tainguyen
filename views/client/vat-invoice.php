<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Thông tin xuất hóa đơn VAT').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">

<style>

#vatTabs .nav-item {
    margin: 0;
    flex: 1;
}

#vatTabs .nav-link {
    border: none;
    border-radius: 12px;
    padding: 14px 28px;
    font-weight: 500;
    color: #6c757d;
    background: transparent;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 160px;
    white-space: nowrap;
    z-index: 1;
}

#vatTabs .nav-link:hover:not(.active) {
    color: #495057;
    background: rgba(0, 0, 0, 0.02);
}

#vatTabs .nav-link.active {
    color: #0d6efd;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15), 0 1px 3px rgba(0,0,0,0.1);
    font-weight: 600;
    border: 1px solid rgba(13, 110, 253, 0.2);
    transform: translateY(-1px);
}

#vatTabs .nav-link i {
    font-size: 18px;
    margin-right: 8px;
    transition: all 0.3s ease;
}

#vatTabs .nav-link.active i {
    color: #0d6efd;
    transform: scale(1.1);
}

#vatTabs .nav-link:not(.active) i {
    color: #adb5bd;
}

@media (max-width: 576px) {
    #vatTabs {
        flex-direction: column;
        width: 100%;
        gap: 4px;
    }
    
    #vatTabs .nav-link {
        width: 100%;
        min-width: auto;
        padding: 12px 20px;
    }
    
    #vatTabs .nav-link.active {
        transform: none;
    }
}
</style>
';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_user.php');

// Lấy thông tin VAT hiện tại của user
$vat_info = null;
try {
    $vat_info_json = $CMSNT->get_row_safe("SELECT vat_info FROM `users` WHERE `id` = ?", [$getUser['id']]);
    if($vat_info_json && !empty($vat_info_json['vat_info'])) {
        $vat_info = json_decode($vat_info_json['vat_info'], true);
        if(!$vat_info || empty($vat_info['vat_type']) || empty($vat_info['vat_name'])) {
            $vat_info = null;
        }
    }
} catch (Exception $e) {
    $vat_info = null;
}

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row content-reverse">
            <div class="col-lg-3">
                <?php require_once(__DIR__.'/sidebar.php');?>
            </div>
            <div class="col-lg-9">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="account-card">
                            <div class="account-title">
                                <h4><i class="fa-solid fa-receipt me-2"></i><?=__('Thông tin xuất hóa đơn VAT');?></h4>
                            </div>
                            <div class="account-content">
                                <?php if($CMSNT->site('popup_vat') == 1 && !$vat_info): ?>
                                <!-- Thông báo cảnh báo bắt buộc nhập thông tin VAT -->
                                <div class="alert alert-warning border-warning" role="alert" style="border-left: 4px solid #ffc107; background-color: #fff3cd; padding: 1.25rem; margin-bottom: 1.5rem; border-radius: 0.5rem;">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3" style="font-size: 1.5rem; color: #ffc107;">
                                            <i class="fa-solid fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="alert-heading mb-2" style="color: #856404; font-weight: 600;">
                                                <i class="fa-solid fa-ban me-2"></i><?=__('Thông báo quan trọng');?>
                                            </h5>
                                            <p class="mb-2" style="color: #856404; font-size: 1rem; line-height: 1.6;">
                                                <strong><?=__('Bạn cần nhập đầy đủ thông tin xuất hóa đơn VAT để tiếp tục sử dụng website.');?></strong>
                                            </p>
                                            <p class="mb-0" style="color: #856404; font-size: 0.95rem; line-height: 1.6;">
                                                <?=__('Vui lòng điền đầy đủ các thông tin bên dưới (đánh dấu * là bắt buộc) và nhấn nút "Lưu thông tin" để hoàn tất. Sau khi lưu thành công, bạn mới có thể sử dụng các tính năng của website.');?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php elseif($CMSNT->site('popup_vat') == 1 && $vat_info): ?>
                                <!-- Thông báo thông tin đã có -->
                                <div class="alert alert-info border-info" role="alert" style="border-left: 4px solid #0dcaf0; background-color: #d1ecf1; padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.5rem;">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="font-size: 1.25rem; color: #0dcaf0;">
                                            <i class="fa-solid fa-circle-check"></i>
                                        </div>
                                        <div>
                                            <strong style="color: #055160;"><?=__('Thông tin VAT của bạn đã được lưu.');?></strong>
                                            <span style="color: #055160; margin-left: 0.5rem;"><?=__('Bạn có thể cập nhật thông tin bên dưới nếu cần thay đổi.');?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Tabs Navigation -->
                                <div class="d-flex justify-content-center mb-4">
                                    <ul class="nav nav-tabs" id="vatTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">
                                                <i class="fa-solid fa-user me-2"></i><?=__('Cá nhân');?>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button" role="tab" aria-controls="business" aria-selected="false">
                                                <i class="fa-solid fa-building me-2"></i><?=__('Doanh nghiệp');?>
                                            </button>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Tabs Content -->
                                <div class="tab-content" id="vatTabsContent">
                                    <!-- Tab Cá nhân -->
                                    <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                                        <form id="vatFormPersonal">
                                            <input type="hidden" name="vat_type" value="personal">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="form-label"><?=__('Họ và tên');?> <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="vat_name_personal" name="vat_name" 
                                                            value="<?=htmlspecialchars(isset($vat_info['vat_name']) && $vat_info['vat_type'] == 'personal' ? $vat_info['vat_name'] : '', ENT_QUOTES, 'UTF-8');?>" 
                                                            placeholder="<?=__('Nhập họ và tên');?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="form-label"><?=__('Địa chỉ');?> <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="vat_address_personal" name="vat_address" rows="3" 
                                                            placeholder="<?=__('Nhập địa chỉ');?>" required><?=htmlspecialchars(isset($vat_info['vat_address']) && $vat_info['vat_type'] == 'personal' ? $vat_info['vat_address'] : '', ENT_QUOTES, 'UTF-8');?></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label"><?=__('Mã số thuế cá nhân');?></label>
                                                        <input type="text" class="form-control" id="vat_tax_code_personal" name="vat_tax_code" 
                                                            value="<?=htmlspecialchars(isset($vat_info['vat_tax_code']) && $vat_info['vat_type'] == 'personal' ? $vat_info['vat_tax_code'] : '', ENT_QUOTES, 'UTF-8');?>" 
                                                            placeholder="<?=__('Nhập mã số thuế cá nhân');?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label"><?=__('CCCD (Căn cước công dân)');?></label>
                                                        <input type="text" class="form-control" id="vat_cccd_personal" name="vat_cccd" 
                                                            value="<?=htmlspecialchars(isset($vat_info['vat_cccd']) && $vat_info['vat_type'] == 'personal' ? $vat_info['vat_cccd'] : '', ENT_QUOTES, 'UTF-8');?>" 
                                                            placeholder="<?=__('Nhập số CCCD');?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Tab Doanh nghiệp -->
                                    <div class="tab-pane fade" id="business" role="tabpanel" aria-labelledby="business-tab">
                                        <form id="vatFormBusiness">
                                            <input type="hidden" name="vat_type" value="business">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label"><?=__('Tên công ty');?> <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="vat_name_business" name="vat_name" 
                                                            value="<?=htmlspecialchars(isset($vat_info['vat_name']) && $vat_info['vat_type'] == 'business' ? $vat_info['vat_name'] : '', ENT_QUOTES, 'UTF-8');?>" 
                                                            placeholder="<?=__('Nhập tên công ty');?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label class="form-label"><?=__('Mã số thuế');?> <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="vat_tax_code_business" name="vat_tax_code" 
                                                            value="<?=htmlspecialchars(isset($vat_info['vat_tax_code']) && $vat_info['vat_type'] == 'business' ? $vat_info['vat_tax_code'] : '', ENT_QUOTES, 'UTF-8');?>" 
                                                            placeholder="<?=__('Nhập mã số thuế');?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label class="form-label"><?=__('Địa chỉ công ty');?> <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="vat_address_business" name="vat_address" rows="3" 
                                                            placeholder="<?=__('Nhập địa chỉ công ty');?>" required><?=htmlspecialchars(isset($vat_info['vat_address']) && $vat_info['vat_type'] == 'business' ? $vat_info['vat_address'] : '', ENT_QUOTES, 'UTF-8');?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="mt-4 text-center">
                                    <button type="button" class="form-btn" id="saveVatBtn">
                                        <i class="fa-solid fa-save me-2"></i><?=__('Lưu thông tin');?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
$(document).ready(function() {
    // Set active tab dựa trên thông tin VAT hiện có
    <?php if($vat_info && isset($vat_info['vat_type'])): ?>
    var vatType = '<?=$vat_info['vat_type'];?>';
    if(vatType === 'business') {
        $('#business-tab').tab('show');
    } else {
        $('#personal-tab').tab('show');
    }
    <?php endif; ?>
    
    // Xử lý khi click nút Lưu
    $("#saveVatBtn").on("click", function() {
        var activeTab = $('#vatTabs .nav-link.active');
        var formId = activeTab.attr('data-bs-target') === '#personal' ? 'vatFormPersonal' : 'vatFormBusiness';
        var form = $('#' + formId);
        
        // Validate form
        if (form[0].checkValidity() === false) {
            form[0].reportValidity();
            return;
        }
        
        // Lấy dữ liệu từ form
        var formData = new FormData(form[0]);
        formData.append('action', 'SaveVatInfo');
        formData.append('token', '<?=isset($getUser) ? htmlspecialchars($getUser['token'], ENT_QUOTES, 'UTF-8') : '';?>');
        
        // Disable button và hiển thị loading
        $('#saveVatBtn').html('<i class="fa fa-spinner fa-spin me-2"></i><?=__('Đang lưu...');?>')
            .prop('disabled', true);
        
        // Gửi AJAX request
        $.ajax({
            url: "<?=base_url('ajaxs/client/auth.php');?>",
            method: "POST",
            dataType: "JSON",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '<?=__('Thành công!');?>',
                        text: response.msg || '<?=__('Lưu thông tin VAT thành công');?>',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        <?php if($CMSNT->site('popup_vat') == 1 && !$vat_info): ?>
                        // Nếu là lần đầu nhập thông tin và popup_vat bật, redirect về trang chủ
                        window.location.href = '<?=base_url('client/home');?>';
                        <?php else: ?>
                        // Nếu đã có thông tin, chỉ reload trang
                        location.reload();
                        <?php endif; ?>
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '<?=__('Thất bại!');?>',
                        text: response.msg || '<?=__('Lưu thông tin VAT thất bại');?>'
                    });
                    $('#saveVatBtn').html('<i class="fa-solid fa-save me-2"></i><?=__('Lưu thông tin');?>')
                        .prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '<?=__('Lỗi!');?>',
                    text: '<?=__('Không thể kết nối đến server');?>'
                });
                $('#saveVatBtn').html('<i class="fa-solid fa-save me-2"></i><?=__('Lưu thông tin');?>')
                    .prop('disabled', false);
            }
        });
    });
});
</script>

<?php
require_once(__DIR__.'/footer.php');
?>
