<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Chỉnh sửa Task',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '
 
';
require_once(__DIR__ . '/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `automations` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url('admin/automations'));
    }
} else {
    redirect(base_url('admin/automations'));
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'edit_automations') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveTask'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('This function cannot be used because this is a demo site') . '")){window.history.back().location.reload();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_automations') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    if (empty($_POST['type'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng chọn loại công việc")){window.history.back().location.reload();}</script>');
    }
    $type = check_string($_POST['type']);
    if (empty($_POST['product_id'])) {
        $product_id = NULL;
    } else {
        // product_id is sent as a JSON string from the form to avoid PHP max_input_vars limit
        $decoded = json_decode($_POST['product_id'], true);
        if (is_array($decoded) && !empty($decoded)) {
            $product_id = json_encode($decoded);
        } else {
            $product_id = NULL;
        }
    }

    if (empty($_POST['schedule'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng nhập thời gian")){window.history.back().location.reload();}</script>');
    }
    $schedule = check_string($_POST['schedule']);

    $isUpdate = $CMSNT->update("automations", [
        'name'              => !empty($_POST['name']) ? check_string($_POST['name']) : NULL,
        'type'              => $type,
        'product_id'        => $product_id,
        'schedule'          => $schedule,
        'other'             => !empty($_POST['other']) ? check_string($_POST['other']) : NULL,
        'update_gettime'    => gettime()
    ], " `id` = '$id' ");
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit Task Automation (" . $row['name'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit Task Automation (" . $row['name'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Lưu thành công !")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Lưu thất bại !")){window.history.back().location.reload();}</script>');
    }
}
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-tags"></i> Chỉnh sửa công việc '<b
                    style="color:red;"><?= $row['name']; ?></b>'</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= base_url_admin('automations'); ?>">Automations</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Task</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            CHỈNH SỬA CÔNG VIỆC
                        </div>
                    </div>
                    <div class="card-body" onchange="loadform()">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Tên công việc</label>
                                <div class="col-sm-8">
                                    <div class="input-group mb-3">
                                        <textarea class="form-control" name="name" placeholder="Nhập tên mô tả task nếu có"><?= $row['name']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Loại công việc (<span
                                        class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <div class="input-group mb-3">
                                        <select class="form-control" name="type" id="type">
                                            <option> -- Chọn loại công việc --</option>
                                            <option value="delete_order" <?= $row['type'] == 'delete_order' ? 'selected' : ''; ?>>Xóa tài khoản đã bán</option>
                                            <option value="delete_order_not_uid" <?= $row['type'] == 'delete_order_not_uid' ? 'selected' : ''; ?>>Xóa tài khoản đã bán, không xóa UID</option>
                                            <option value="delete_order_revenue" <?= $row['type'] == 'delete_order_revenue' ? 'selected' : ''; ?>>Xóa đơn hàng & tài khoản đã bán</option>
                                            <option value="change_warehouse" <?= $row['type'] == 'change_warehouse' ? 'selected' : ''; ?>>Thay đổi kho hàng</option>
                                            <option value="delete_history_topup" <?= $row['type'] == 'delete_history_topup' ? 'selected' : ''; ?>>Xóa lịch sử nạp tiền</option>
                                            <option value="delete_history_dongtien" <?= $row['type'] == 'delete_history_dongtien' ? 'selected' : ''; ?>>Xóa biến động số dư</option>
                                            <option value="delete_user_no_topup" <?= $row['type'] == 'delete_user_no_topup' ? 'selected' : ''; ?>>Xóa User không nạp tiền</option>
                                            <option value="cleanup_orphan_images" <?= $row['type'] == 'cleanup_orphan_images' ? 'selected' : ''; ?>>Dọn dẹp ảnh rác</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4" id="product_id_input" style="display: none;">
                                <label class="col-sm-4 col-form-label">Áp dụng cho sản phẩm:</label>
                                <div class="col-sm-8">
                                    <?php $selectedProducts = json_decode($row['product_id'] ?? '[]', true) ?? []; ?>
                                    <input type="text" id="productSearch" class="form-control form-control-sm mb-2" placeholder="🔍 Tìm kiếm sản phẩm hoặc chuyên mục..." oninput="searchProducts(this.value)" autocomplete="off">
                                    <div id="productCheckboxList" style="border: 1px solid #e0e0e0; border-radius: 6px; max-height: 300px; overflow-y: auto; background: #fff;">
                                        <div class="product-item" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0;" onclick="toggleAllProducts(this)">
                                            <span style="cursor: pointer; margin: 0; font-weight: 500; color: #555;">
                                                <input type="checkbox" id="checkAllProducts" style="margin-right: 8px;" <?= empty($selectedProducts) ? 'checked' : ''; ?>> ~ Tất cả sản phẩm ~
                                            </span>
                                        </div>
                                        <?php foreach ($CMSNT->get_list(" SELECT * FROM `categories` ") as $category): ?>
                                            <?php $catProducts = $CMSNT->get_list(" SELECT * FROM `products` WHERE `category_id` = '" . $category['id'] . "' "); ?>
                                            <?php if (!empty($catProducts)): ?>
                                                <?php
                                                // Check if all products in this category are selected
                                                $allCatSelected = !empty($selectedProducts);
                                                $anyCatSelected = false;
                                                foreach ($catProducts as $p) {
                                                    $isInSelected = in_array($p['id'], $selectedProducts, true) || in_array((string)$p['id'], $selectedProducts, true);
                                                    if (!$isInSelected) {
                                                        $allCatSelected = false;
                                                    } else {
                                                        $anyCatSelected = true;
                                                    }
                                                }
                                                ?>
                                                <div class="category-group" data-cat-id="<?= $category['id']; ?>">
                                                    <div class="category-header" data-cat-name="<?= htmlspecialchars($category['name']); ?>" style="padding: 6px 12px; background: #f8f9fa; font-weight: 600; font-size: 12px; color: #555; letter-spacing: 0.3px; border-bottom: 1px solid #f0f0f0; cursor: pointer; display: flex; align-items: center; justify-content: space-between;" onclick="handleCategoryClick(event, '<?= $category['id']; ?>', this)">
                                                        <span style="cursor: pointer; margin: 0; display: flex; align-items: center;">
                                                            <input type="checkbox" class="category-checkbox" data-cat-id="<?= $category['id']; ?>" style="margin-right: 6px;" <?= $allCatSelected ? 'checked' : ''; ?> onclick="event.stopPropagation(); toggleCategoryCheck(event, '<?= $category['id']; ?>', this.closest('.category-header'))"> <?= $category['name']; ?>
                                                            <span style="margin-left: 6px; font-weight: normal; color: #999; font-size: 11px;">(<?= count($catProducts); ?>)</span>
                                                        </span>
                                                        <span class="cat-chevron" style="font-size: 14px; color: #999; transition: transform 0.2s;"><?= $anyCatSelected ? '▾' : '▸'; ?></span>
                                                    </div>
                                                    <div class="category-products" data-cat-id="<?= $category['id']; ?>" style="<?= $anyCatSelected ? '' : 'display: none;'; ?>">
                                                        <?php foreach ($catProducts as $product): ?>
                                                            <?php $isSelected = in_array($product['id'], $selectedProducts, true) || in_array((string)$product['id'], $selectedProducts, true); ?>
                                                            <div class="product-item" data-product-name="<?= htmlspecialchars($product['name']); ?>" data-cat-id="<?= $category['id']; ?>" style="padding: 7px 12px 7px 28px; cursor: pointer; border-bottom: 1px solid #f8f8f8; transition: background 0.15s; <?= $isSelected ? 'background:#ede9fe;' : ''; ?>" onmouseover="this.style.background='#f5f3ff'" onmouseout="if(!this.querySelector('input').checked) this.style.background='#fff'; else this.style.background='#ede9fe';" onclick="toggleProduct(event, this)">
                                                                <span style="cursor: pointer; margin: 0; display: block;">
                                                                    <input type="checkbox" class="product-checkbox" data-category="<?= $category['id']; ?>" value="<?= $product['id']; ?>" style="margin-right: 8px;" <?= $isSelected ? 'checked' : ''; ?> onchange="updateProductSelection()"> <?= $product['name']; ?>
                                                                </span>
                                                            </div>
                                                        <?php endforeach ?>
                                                    </div>
                                                </div>
                                            <?php endif ?>
                                        <?php endforeach ?>
                                    </div>
                                    <!-- Single hidden input for form submission (avoids PHP max_input_vars limit) -->
                                    <input type="hidden" name="product_id" id="productHiddenInput" value="<?= htmlspecialchars(json_encode($selectedProducts)); ?>">
                                    <small class="text-muted mt-1 d-block">Click vào chuyên mục để mở rộng danh sách. Tích checkbox chuyên mục để chọn tất cả sản phẩm.</small>
                                </div>
                                <script>
                                    function toggleAllProducts(el) {
                                        var cb = document.getElementById('checkAllProducts');
                                        if (event.target !== cb) cb.checked = !cb.checked;
                                        if (cb.checked) {
                                            document.querySelectorAll('.product-checkbox').forEach(function(c) {
                                                c.checked = false;
                                                c.closest('.product-item').style.background = '#fff';
                                            });
                                            document.querySelectorAll('.category-checkbox').forEach(function(c) {
                                                c.checked = false;
                                            });
                                        }
                                        updateProductSelection();
                                    }

                                    function toggleCategoryDropdown(catId, headerEl) {
                                        var container = document.querySelector('.category-products[data-cat-id="' + catId + '"]');
                                        var chevron = headerEl.querySelector('.cat-chevron');
                                        if (container.style.display === 'none') {
                                            container.style.display = '';
                                            chevron.textContent = '▾';
                                        } else {
                                            container.style.display = 'none';
                                            chevron.textContent = '▸';
                                        }
                                    }

                                    function handleCategoryClick(e, catId, headerEl) {
                                        if (e.target.classList.contains('category-checkbox')) return;
                                        toggleCategoryDropdown(catId, headerEl);
                                    }

                                    function toggleCategoryCheck(e, catId, headerEl) {
                                        var catCb = headerEl.querySelector('.category-checkbox');
                                        var isChecked = catCb.checked;
                                        var container = document.querySelector('.category-products[data-cat-id="' + catId + '"]');
                                        var chevron = headerEl.querySelector('.cat-chevron');
                                        if (isChecked && container.style.display === 'none') {
                                            container.style.display = '';
                                            chevron.textContent = '▾';
                                        }
                                        document.querySelectorAll('.product-checkbox[data-category="' + catId + '"]').forEach(function(c) {
                                            c.checked = isChecked;
                                            c.closest('.product-item').style.background = isChecked ? '#ede9fe' : '#fff';
                                        });
                                        document.getElementById('checkAllProducts').checked = false;
                                        var anyChecked = document.querySelectorAll('.product-checkbox:checked').length > 0;
                                        if (!anyChecked) document.getElementById('checkAllProducts').checked = true;
                                        updateProductSelection();
                                        e.stopPropagation();
                                    }

                                    function toggleProduct(e, el) {
                                        var cb = el.querySelector('.product-checkbox');
                                        if (e.target !== cb) cb.checked = !cb.checked;
                                        document.getElementById('checkAllProducts').checked = false;
                                        el.style.background = cb.checked ? '#ede9fe' : '#fff';
                                        var catId = cb.getAttribute('data-category');
                                        var allInCat = document.querySelectorAll('.product-checkbox[data-category="' + catId + '"]');
                                        var checkedInCat = document.querySelectorAll('.product-checkbox[data-category="' + catId + '"]:checked');
                                        var catCb = document.querySelector('.category-checkbox[data-cat-id="' + catId + '"]');
                                        if (catCb) catCb.checked = (allInCat.length === checkedInCat.length);
                                        var anyChecked = document.querySelectorAll('.product-checkbox:checked').length > 0;
                                        if (!anyChecked) document.getElementById('checkAllProducts').checked = true;
                                        updateProductSelection();
                                    }

                                    function updateProductSelection() {
                                        var ids = [];
                                        document.querySelectorAll('.product-checkbox:checked').forEach(function(c) {
                                            ids.push(c.value);
                                        });
                                        document.getElementById('productHiddenInput').value = JSON.stringify(ids);
                                    }

                                    function searchProducts(query) {
                                        var q = query.toLowerCase().trim();
                                        var catGroups = document.querySelectorAll('#productCheckboxList .category-group');
                                        if (!q) {
                                            catGroups.forEach(function(g) {
                                                g.style.display = '';
                                                var header = g.querySelector('.category-header');
                                                var container = g.querySelector('.category-products');
                                                // When clearing search, collapse categories that have no checked products
                                                var hasChecked = container.querySelectorAll('.product-checkbox:checked').length > 0;
                                                container.style.display = hasChecked ? '' : 'none';
                                                header.querySelector('.cat-chevron').textContent = hasChecked ? '▾' : '▸';
                                                container.querySelectorAll('.product-item').forEach(function(p) {
                                                    p.style.display = '';
                                                });
                                            });
                                            return;
                                        }
                                        catGroups.forEach(function(g) {
                                            var header = g.querySelector('.category-header');
                                            var container = g.querySelector('.category-products');
                                            var catName = (header.getAttribute('data-cat-name') || '').toLowerCase();
                                            var products = container.querySelectorAll('.product-item');
                                            var hasMatch = false;
                                            if (catName.indexOf(q) !== -1) {
                                                g.style.display = '';
                                                container.style.display = '';
                                                header.querySelector('.cat-chevron').textContent = '▾';
                                                products.forEach(function(p) {
                                                    p.style.display = '';
                                                });
                                            } else {
                                                products.forEach(function(p) {
                                                    var name = (p.getAttribute('data-product-name') || '').toLowerCase();
                                                    if (name.indexOf(q) !== -1) {
                                                        p.style.display = '';
                                                        hasMatch = true;
                                                    } else {
                                                        p.style.display = 'none';
                                                    }
                                                });
                                                if (hasMatch) {
                                                    g.style.display = '';
                                                    container.style.display = '';
                                                    header.querySelector('.cat-chevron').textContent = '▾';
                                                } else {
                                                    g.style.display = 'none';
                                                }
                                            }
                                        });
                                    }
                                </script>
                            </div>
                            <div class="row mb-4" id="schedule_row">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Thời gian (<span
                                        class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <div class="input-group mb-3">
                                        <input class="form-control" name="schedule" value="<?= $row['schedule']; ?>" id="schedule" onkeyup="loadform()"
                                            value="604800" placeholder="Nhập giây, ví dụ 1 ngày = 86400" required>
                                        <span class="input-group-text">
                                            Giây
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4" id="warehouse_input" style="display: none;">
                                <label class="col-sm-4 col-form-label" for="example-hf-email">Kho hàng nhận (<span
                                        class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <div class="input-group mb-3">
                                        <input class="form-control" name="other" id="other" value="<?= $row['other']; ?>" onkeyup="loadform()"
                                            placeholder="Mã kho hàng">
                                    </div>
                                </div>
                            </div>

                            <p id="mota">Vui lòng chọn loại công việc</p>

                            <script>
                                function formatTime(seconds) {
                                    var days = Math.floor(seconds / (60 * 60 * 24));
                                    var hours = Math.floor((seconds % (60 * 60 * 24)) / (60 * 60));
                                    var minutes = Math.floor((seconds % (60 * 60)) / 60);
                                    var remainingSeconds = seconds % 60;

                                    var result = '';
                                    if (days > 0) {
                                        result += days + ' ngày ';
                                    }
                                    if (hours > 0) {
                                        result += hours + ' giờ ';
                                    }
                                    if (minutes > 0) {
                                        result += minutes + ' phút ';
                                    }
                                    if (remainingSeconds > 0) {
                                        result += remainingSeconds + ' giây';
                                    }

                                    return result.trim();
                                }

                                function loadform() {
                                    var type = $('#type').val();
                                    var schedule = $('#schedule').val();
                                    var formattedTime = formatTime(schedule);

                                    $('#warehouse_input').hide();
                                    $('#product_id_input').hide();

                                    if (type == 'change_warehouse') {
                                        $('#warehouse_input').show();
                                        $('#product_id_input').show();
                                        $('#mota').html(
                                            'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện chuyển những tài khoản trong sản phẩm bạn chọn vào kho hàng <b style="color:blue;">' +
                                            $('#other').val() + '</b> nếu quá <b style="color:red;">' + formattedTime +
                                            '</b> chưa được bán.');
                                    } else if (type == 'delete_order') {
                                        $('#product_id_input').show();
                                        $('#mota').html(
                                            'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa tài khoản đã bán sau <b style="color:red;">' +
                                            formattedTime + '</b>, chỉ áp dụng các sản phẩm bạn chọn ở trên.');
                                    } else if (type == 'delete_order_not_uid') {
                                        $('#product_id_input').show();
                                        $('#mota').html(
                                            'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa tài khoản đã bán, không xóa UID sau <b style="color:red;">' +
                                            formattedTime + '</b>, chỉ áp dụng các sản phẩm bạn chọn ở trên.');
                                    } else if (type == 'delete_order_revenue') {
                                        $('#product_id_input').show();
                                        $('#mota').html(
                                            'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa đơn hàng và tài khoản đã bán sau <b style="color:red;">' +
                                            formattedTime + '</b>, chỉ áp dụng các sản phẩm bạn chọn ở trên.');
                                    } else if (type == 'delete_history_topup') {
                                        $('#product_id_input').hide();
                                        $('#mota').html(
                                            'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa lịch sử nạp tiền sau <b style="color:red;">' +
                                            formattedTime + '</b>.');
                                    } else if (type == 'delete_history_dongtien') {
                                        $('#product_id_input').hide();
                                        $('#mota').html(
                                            'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa biến động số dư sau <b style="color:red;">' +
                                            formattedTime + '</b>.');
                                    } else if (type == 'delete_user_no_topup') {
                                        $('#product_id_input').hide();
                                        $('#mota').html(
                                            'Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa User chưa nạp tiền (total_money = 0 và money = 0) sau <b style="color:red;">' +
                                            formattedTime + '</b> kể từ ngày đăng ký.');
                                    } else if (type == 'cleanup_orphan_images') {
                                        $('#product_id_input').hide();
                                        $('#mota').html(
                                            'Nếu bạn tạo Task này => Hệ thống sẽ tự động quét và xóa ảnh không còn được sử dụng trong thư mục storage.');
                                    } else {
                                        $('#mota').html('Vui lòng chọn loại công việc');
                                    }

                                    // Ẩn/hiện ô thời gian
                                    if (type == 'cleanup_orphan_images') {
                                        $('#schedule_row').hide();
                                        $('#schedule').removeAttr('required');
                                    } else {
                                        $('#schedule_row').show();
                                        $('#schedule').attr('required', true);
                                    }
                                }
                                // Sự kiện DOMContentLoaded
                                document.addEventListener("DOMContentLoaded", function(event) {
                                    // Gọi hàm loadform khi trang đã tải xong
                                    loadform();
                                });
                            </script>
                            <a type="button" class="btn btn-danger shadow-danger btn-wave"
                                href="<?= base_url_admin('automations'); ?>"><i class="fa fa-fw fa-undo me-1"></i>
                                <?= __('Back'); ?></a>
                            <button type="submit" name="SaveTask" class="btn btn-primary shadow-primary btn-wave"><i
                                    class="fa fa-fw fa-save me-1"></i> <?= __('Save'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Lấy logs của task này
        $deviceKey = 'cron_task_' . $id;
        $taskLogs = $CMSNT->get_list_safe("SELECT * FROM `logs` WHERE `device` = ? ORDER BY `id` DESC LIMIT 50", [$deviceKey]);
        ?>
        <div class="row mt-4">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Nhật ký hoạt động
                            <span class="badge bg-primary-transparent ms-2"><?= count($taskLogs); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($taskLogs)): ?>
                            <div class="table-responsive">
                                <table class="table text-nowrap table-striped table-hover table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Hành động</th>
                                            <th>Thời gian</th>
                                            <th>Địa chỉ IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($taskLogs as $log): ?>
                                            <tr>
                                                <td><?= $log['id']; ?></td>
                                                <td><span class="text-wrap" style="max-width: 400px; display: inline-block;"><?= htmlspecialchars($log['action']); ?></span></td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?= $log['createdate']; ?>
                                                    </span>
                                                </td>
                                                <td><span class="badge bg-danger-transparent"><?= $log['ip']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                                Chưa có nhật ký hoạt động nào cho task này
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





<?php
require_once(__DIR__ . '/footer.php');
?>