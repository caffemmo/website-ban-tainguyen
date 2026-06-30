<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Thêm sản phẩm mới').' | CTV Panel | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '';
require_once(__DIR__.'/../../models/is_ctv.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');

// Handle POST submission
if (isset($_POST['submit'])) {

    if(empty($_POST['name'])){
        die('<script type="text/javascript">if(!alert("'.__('Tên sản phẩm không được để trống!').'")){window.history.back().location.reload();}</script>');
    }
    $name = validate_string($_POST['name'], 255);
    if ($name === false) {
        die('<script type="text/javascript">if(!alert("'.__('Tên sản phẩm không hợp lệ!').'")){window.history.back().location.reload();}</script>');
    }
    
    $slug = validate_slug($_POST['slug'], 255);
    if ($slug === false) {
        die('<script type="text/javascript">if(!alert("'.__('Slug không hợp lệ!').'")){window.history.back().location.reload();}</script>');
    }
    
    $price = validate_float($_POST['price'], 0.01);
    if ($price === false) {
        die('<script type="text/javascript">if(!alert("'.__('Giá sản phẩm không hợp lệ!').'")){window.history.back().location.reload();}</script>');
    }
    
    $category_id = validate_int($_POST['category_id'], 1);
    if ($category_id === false) {
        die('<script type="text/javascript">if(!alert("'.__('Chuyên mục không hợp lệ!').'")){window.history.back().location.reload();}</script>');
    }

    if ($CMSNT->get_row_safe("SELECT * FROM `products` WHERE `name` = ?", [$name])) {
        die('<script type="text/javascript">if(!alert("'.__('Sản phẩm này đã tồn tại trong hệ thống.').'")){window.history.back().location.reload();}</script>');
    }
    
    $discount = validate_float($_POST['discount'], 0, 100);
    if ($discount === false) {
        die('<script type="text/javascript">if(!alert("'.__('Giảm giá không hợp lệ!').'")){window.history.back().location.reload();}</script>');
    }
    
    
    // Handle image uploads
    $images = '';
    if (isset($_FILES['images']['name']) && !empty($_FILES['images']['name'])) {
        $upload_dir = __DIR__ . '/../../assets/storage/images/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Define allowed file types with their magic bytes
        $allowed_types = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp']
        ];
        
        $max_file_size = 5 * 1024 * 1024; // 5MB
        $max_files = 5; // Maximum 5 files
        $uploaded_images = [];
        
        // Check total number of files
        $file_count = count($_FILES['images']['name']);
        if ($file_count > $max_files) {
            die('<script type="text/javascript">if(!alert("'.__('Tối đa chỉ được upload').' '.$max_files.' '.__('ảnh!').'")){window.history.back().location.reload();}</script>');
        }
        
        foreach ($_FILES['images']['name'] as $key => $filename) {
            if (empty($filename)) continue;
            
            $file_tmp = $_FILES['images']['tmp_name'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $file_error = $_FILES['images']['error'][$key];
            
            // Sanitize filename
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($filename));
            if (empty($filename)) continue;
            
            // Check for upload errors
            if ($file_error !== UPLOAD_ERR_OK) {
                continue;
            }
            
            // Check file size
            if ($file_size > $max_file_size) {
                die('<script type="text/javascript">if(!alert("'.__('File').' '.$filename.' '.__('quá lớn (tối đa 5MB)!').'")){window.history.back().location.reload();}</script>');
            }
            
            // Check if file is actually uploaded
            if (!is_uploaded_file($file_tmp)) {
                die('<script type="text/javascript">if(!alert("'.__('File upload không hợp lệ!').'")){window.history.back().location.reload();}</script>');
            }
            
            // Get real file type using magic bytes
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $real_mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            
            // Validate real MIME type
            if (!array_key_exists($real_mime_type, $allowed_types)) {
                die('<script type="text/javascript">if(!alert("'.__('File').' '.$filename.' '.__('không phải là hình ảnh hợp lệ! Chỉ chấp nhận định dạng JPG, PNG, GIF và WEBP').'")){window.history.back().location.reload();}</script>');
            }
            
            // Double-check if image file by trying to get image dimensions
            $image_size = @getimagesize($file_tmp);
            if ($image_size === false) {
                die('<script type="text/javascript">if(!alert("'.__('File').' '.$filename.' '.__('không phải là hình ảnh hợp lệ!').'")){window.history.back().location.reload();}</script>');
            }
            
            // Additional security: Check file content for PHP code
            $file_content = file_get_contents($file_tmp);
            if (strpos($file_content, '<?php') !== false || 
                strpos($file_content, '<?=') !== false ||
                strpos($file_content, '<script') !== false) {
                die('<script type="text/javascript">if(!alert("'.__('File').' '.$filename.' '.__('chứa mã độc hại!').'")){window.history.back().location.reload();}</script>');
            }
            
            // Get safe extension from allowed types
            $safe_extension = $allowed_types[$real_mime_type][0];
            
            // Generate secure unique filename
            $new_filename = 'ctv_' . $getUser['id'] . '_' . time() . '_' . $key . '_' . bin2hex(random_bytes(8)) . '.' . $safe_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Additional security: Create .htaccess to prevent execution
            $htaccess_path = $upload_dir . '.htaccess';
            if (!file_exists($htaccess_path)) {
                file_put_contents($htaccess_path, "Options -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n<Files ~ \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">\nOrder allow,deny\nDeny from all\n</Files>");
            }
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Set proper permissions
                chmod($upload_path, 0644);
                $uploaded_images[] = $new_filename; // Chỉ lưu tên file
            }
        }
        
        if (!empty($uploaded_images)) {
            $images = implode(PHP_EOL, $uploaded_images);
        }
    }
    
    // Generate unique product code
    $code = 'CTV_' . $getUser['username'] . '_' . time() . '_' . bin2hex(random_bytes(4));
    
    $short_desc = validate_string($_POST['short_desc'], 1000);
    $description = validate_string($_POST['description'], 10000);
    $note = validate_string($_POST['note'], 1000);
    $min = validate_int($_POST['min'], 1);
    $max = validate_int($_POST['max'], 1);

    // Kiểm tra ràng buộc hợp lệ giữa min/max
    if ($min !== false && $max !== false && $min > $max) {
        die('<script type="text/javascript">if(!alert("'.__('Số lượng tối thiểu không được lớn hơn số lượng tối đa!').'")){window.history.back().location.reload();}</script>');
    }

    // Đảm bảo slug là duy nhất
    if ($CMSNT->get_row_safe("SELECT `id` FROM `products` WHERE `slug` = ?", [$slug])) {
        die('<script type="text/javascript">if(!alert("'.__('Slug này đã tồn tại trong hệ thống. Vui lòng chọn slug khác!').'")){window.history.back().location.reload();}</script>');
    }
    
    $isInsert = $CMSNT->insert('products', [
        'code' => $code,
        'user_id' => $getUser['id'],
        'name' => $name,
        'slug' => $slug,
        'images' => $images,
        'short_desc' => $short_desc !== false ? $short_desc : NULL,
        'description' => $description !== false ? base64_encode($description) : NULL,
        'note' => $note !== false ? $note : NULL,
        'price' => $price,
        'min' => $min !== false ? $min : 1,
        'max' => $max !== false ? $max : 1000,
        'discount' => $discount,
        'category_id' => $category_id,
        'status' => 1, // Hidden by default until approved
        'pending' => 1, // Set pending = 1 for CTV products
        'sold' => 0,
        'create_gettime' => gettime(),
        'update_gettime' => gettime()
    ]);
    
    if ($isInsert) {
        $product_id = $isInsert; // Get the inserted ID
        $CMSNT->insert("logs", [
            'user_id' => $getUser['id'],
            'ip' => myip(),
            'device' => getUserAgent(),
            'createdate' => gettime(),
            'action' => 'CTV tạo sản phẩm mới: ' . $name
        ]);
        
        die('<script type="text/javascript">if(!alert("'.__('Tạo sản phẩm thành công!').'")){location.href = "'.base_url_ctv('products').'";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("'.__('Tạo sản phẩm thất bại!').'")){window.history.back().location.reload();}</script>');
    }
}
 

?>
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">
                <a href="<?=base_url_ctv('products');?>" class="btn btn-dark btn-sm me-2">
                    <i class="ri-arrow-left-line"></i>
                </a>
                <?=__('Thêm sản phẩm mới');?>
            </h1>
            <div class="text-muted">
                <small><?=__('Sản phẩm sẽ cần Admin duyệt trước khi hiển thị');?></small>
            </div>
        </div>


        <!-- Hướng dẫn -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="ri-information-line fs-18 me-2 mt-1"></i>
                <div>
                    <h6 class="mb-1"><?=__('Hướng dẫn tạo sản phẩm:');?></h6>
                    <ul class="mb-0 small">
                        <li><?=__('Điền đầy đủ thông tin sản phẩm, đặc biệt là tên và mô tả chi tiết');?></li>
                        <li><?=__('Upload hình ảnh chất lượng cao để thu hút khách hàng');?></li>
                        <li><?=__('Đặt giá hợp lý và cạnh tranh');?></li>
                        <li><?=__('Sản phẩm sau khi tạo sẽ có trạng thái "Chờ duyệt"');?></li>
                        <li><?=__('Admin sẽ kiểm tra và duyệt sản phẩm trong vòng 24h');?></li>
                        <li><?=__('Sau khi được duyệt, bạn có thể upload nick/tài khoản vào kho hàng');?></li>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="row">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="ri-information-line me-2"></i><?=__('Thông tin cơ bản');?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label"><?=__('Tên sản phẩm');?> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required 
                                           placeholder="<?=__('Ví dụ: Tài khoản Facebook cổ - Reg 2010');?>">
                                    <div class="form-text"><?=__('Tên sản phẩm nên rõ ràng, dễ hiểu và thu hút khách hàng');?></div>
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label"><?=__('Slug (Đường dẫn thân thiện)');?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><?=base_url('product/');?></span>
                                        <input type="text" class="form-control" name="slug" 
                                               placeholder="<?=__('tai-khoan-facebook-co-reg-2010');?>">
                                    </div>
                                    <div class="form-text"><?=__('Để trống để tự động tạo từ tên sản phẩm');?></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><?=__('Giá bán');?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="price" required min="0" 
                                               placeholder="<?=__('50000');?>">
                                        <span class="input-group-text">VNĐ</span>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><?=__('Giảm giá (%)');?></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="discount" value="0" 
                                               min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><?=__('Số lượng tối thiểu');?></label>
                                    <input type="number" class="form-control" name="min" value="1" min="1">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><?=__('Số lượng tối đa');?></label>
                                    <input type="number" class="form-control" name="max" value="1000" min="1">
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Mô tả sản phẩm -->
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="ri-file-text-line me-2"></i><?=__('Mô tả sản phẩm');?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><?=__('Mô tả ngắn');?></label>
                                <textarea class="form-control" name="short_desc" rows="3" 
                                          placeholder="<?=__('Mô tả ngắn gọn về sản phẩm (hiển thị trong danh sách sản phẩm)');?>"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?=__('Mô tả chi tiết');?></label>
                                <textarea class="form-control" name="description" rows="8" 
                                          placeholder="<?=__('Mô tả chi tiết về sản phẩm, hướng dẫn sử dụng, lưu ý...');?>"></textarea>
                                <div class="form-text"><?=__('Mô tả chi tiết giúp khách hàng hiểu rõ hơn về sản phẩm');?></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?=__('Lưu ý xuất hiện khi xem đơn hàng:');?></label>
                                <textarea class="form-control" name="note" rows="3" 
                                          placeholder="<?=__('Lưu ý xuất hiện khi xem đơn hàng');?>"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Hình ảnh sản phẩm -->
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="ri-image-line me-2"></i><?=__('Hình ảnh sản phẩm');?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><?=__('Upload hình ảnh');?></label>
                                <input type="file" class="form-control" name="images[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" 
                                       onchange="previewImages(this)">
                                <div class="form-text">
                                    <i class="ri-information-line me-1"></i>
                                    <?=__('Chọn nhiều hình ảnh (tối đa 5 ảnh, mỗi ảnh < 5MB). Hình đầu tiên sẽ là ảnh đại diện.');?>
                                </div>
                            </div>

                            <div id="imagePreview" class="row g-2" style="display: none;">
                                <!-- Preview images will be inserted here -->
                            </div>

                            <div class="alert alert-warning">
                                <i class="ri-alert-line me-2"></i>
                                <strong><?=__('Lưu ý về hình ảnh:');?></strong>
                                <ul class="mb-0 mt-2">
                                    <li><?=__('Sử dụng hình ảnh chất lượng cao, rõ nét');?></li>
                                    <li><?=__('Hình ảnh nên thể hiện đúng sản phẩm bán');?></li>
                                    <li><?=__('Không sử dụng hình ảnh có bản quyền');?></li>
                                    <li><?=__('Tránh hình ảnh chứa thông tin nhạy cảm');?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <!-- Chuyên mục -->
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="ri-folder-line me-2"></i><?=__('Phân loại');?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><?=__('Chuyên mục');?> <span class="text-danger">*</span></label>
                                <select class="form-control js-example-basic-single" name="category_id" required>
                                    <option value="">-- <?=__('Chọn chuyên mục');?> --</option>
                                    <?php foreach($CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` = 0 ORDER BY `name`", []) as $parent): ?>
                                        <optgroup label="<?=$parent['name'];?>">
                                            <?php foreach($CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` = ? ORDER BY `name`", [$parent['id']]) as $child): ?>
                                                <option value="<?=$child['id'];?>"><?=$child['name'];?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

           

                    <!-- Nút hành động -->
                    <div class="card custom-card">
                        <div class="card-body text-center">
                            <button type="submit" name="submit" class="btn btn-primary btn-lg w-100 mb-2">
                                <i class="ri-add-line me-2"></i><?=__('Tạo sản phẩm');?>
                            </button>
                            <a href="<?=base_url_ctv('products');?>" class="btn btn-secondary w-100">
                                <i class="ri-arrow-left-line me-2"></i><?=__('Quay lại danh sách');?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Auto generate slug from product name
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.querySelector('input[name="name"]');
    const slugInput = document.querySelector('input[name="slug"]');
    
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            const productName = this.value;
            
            // Only auto-generate if user hasn't manually edited the slug
            if (productName && (!slugInput.dataset.userEdited || slugInput.dataset.userEdited === 'false')) {
                const slug = removeVietnameseTones(productName)
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '') // Remove special characters except spaces and hyphens
                    .replace(/\s+/g, '-') // Replace multiple spaces with single hyphen
                    .replace(/-+/g, '-') // Replace multiple hyphens with single hyphen
                    .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
                
                slugInput.value = slug;
            }
        });
        
        // Track if user manually edited the slug
        slugInput.addEventListener('input', function() {
            this.dataset.userEdited = 'true';
        });
        
        // Reset tracking when slug is cleared
        slugInput.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.dataset.userEdited = 'false';
            }
        });
    }
});

function removeVietnameseTones(str) {
    // Sử dụng normalize để xử lý dấu tiếng Việt
    return str.normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Loại bỏ dấu
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D');
}

// Preview uploaded images
function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        // Kiểm tra số lượng file
        if (input.files.length > 5) {
            alert('<?=__('Chỉ được phép tải lên tối đa 5 ảnh!');?>');
            input.value = ''; // Xóa các file đã chọn
            return;
        }
        
        // Kiểm tra từng file
        let validFiles = true;
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        Array.from(input.files).forEach(file => {
            // Kiểm tra loại file
            if (!allowedTypes.includes(file.type)) {
                alert('<?=__('File');?> ' + file.name + ' <?=__('không phải là định dạng ảnh hợp lệ! Chỉ chấp nhận JPG, PNG, GIF và WEBP');?>');
                validFiles = false;
                return;
            }
            
            // Kiểm tra kích thước file
            if (file.size > maxSize) {
                alert('<?=__('File');?> ' + file.name + ' <?=__('có kích thước quá lớn! Tối đa 5MB');?>');
                validFiles = false;
                return;
            }
        });
        
        if (!validFiles) {
            input.value = ''; // Xóa các file đã chọn nếu có file không hợp lệ
            return;
        }
        
        // Hiển thị preview cho các file hợp lệ
        preview.style.display = 'block';
        
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-4 col-6';
                col.innerHTML = `
                    <div class="card">
                        <img src="${e.target.result}" class="card-img-top" style="height: 120px; object-fit: cover;">
                        <div class="card-body p-2">
                            <small class="text-muted"><?=__('Ảnh');?> ${index + 1}</small>
                            ${index === 0 ? '<small class="badge bg-primary ms-1"><?=__('Ảnh đại diện');?></small>' : ''}
                        </div>
                    </div>
                `;
                preview.appendChild(col);
            };
            reader.readAsDataURL(file);
        });
    } else {
        preview.style.display = 'none';
    }
}

// Form submission - No longer needed as we use POST

// Add spin animation for loading icon
const style = document.createElement('style');
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

<?php
require_once(__DIR__.'/footer.php');
?>
