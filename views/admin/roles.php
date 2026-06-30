<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Roles',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
';
$body['footer'] = '
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
';
require_once(__DIR__ . '/../../models/is_admin.php');

require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'view_role') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['addRole'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_role') != true) {
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    if (empty($_POST['name'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng nhập tên vai trò")){window.history.back().location.reload();}</script>');
    }
    $name = check_string($_POST['name']);
    if (empty($_POST['role'])) {
        die('<script type="text/javascript">if(!alert("Vui lòng chọn quyền cho role")){window.history.back().location.reload();}</script>');
    }
    $role = json_encode($_POST['role']);
    $isInsert = $CMSNT->insert("admin_role", [
        'name'              => $name,
        'role'              => $role,
        'create_gettime'    => gettime(),
        'update_gettime'    => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Role ($name)"
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add Role ($name)", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công!")){location.href = "' . base_url_admin('roles') . '";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Thêm thất bại!")){window.history.back().location.reload();}</script>');
    }
}

?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-shield-halved"></i> Admin Role</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active" aria-current="page">Admin Role</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH ROLE
                        </div>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                            class="btn btn-sm btn-primary shadow-primary"><i class="fa-solid fa-plus"></i> Tạo một role mới</button>
                    </div>
                    <div class="card-body">
                        <table id="datatable-basic" class="table text-nowrap table-striped table-hover table-bordered"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-center"><?= __('Thao tác'); ?></th>
                                    <th><?= __('Vai trò'); ?></th>
                                    <th><?= __('Tài khoản thuộc Role này'); ?></th>
                                    <th><?= __('Quyền hạn'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($CMSNT->get_list("SELECT * FROM `admin_role` ORDER BY id DESC ") as $row) { ?>
                                    <tr>
                                        <td class="text-center">
                                            <a type="button" href="<?= base_url_admin('role-edit&id=' . $row['id']); ?>"
                                                class="btn btn-sm btn-info" data-bs-toggle="tooltip"
                                                title="<?= __('Edit'); ?>">
                                                <i class="fa fa-pencil-alt"></i>
                                            </a>
                                            <a type="button" onclick="remove('<?= $row['id']; ?>')"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="<?= __('Delete'); ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <?= $row['name']; ?>
                                        </td>
                                        <td>
                                            <?php foreach ($CMSNT->get_list("SELECT * FROM `users` WHERE `admin` = '" . $row['id'] . "' ") as $user): ?>
                                                <a href="<?= base_url_admin('user-edit&id=' . $user['id']); ?>" target="_blank">
                                                    <span class="badge bg-outline-info"><i class="fa-solid fa-user"></i> <?= $user['username']; ?></span>
                                                </a>
                                            <?php endforeach ?>
                                        </td>
                                        <td>
                                            <?php
                                            $roles = json_decode($row['role']);
                                            $role_count = count($roles);
                                            $display_limit = 3;
                                            $counter = 0;
                                            foreach ($roles as $rl):
                                                $counter++;
                                                if ($counter <= $display_limit):
                                            ?>
                                                    <span class="badge bg-outline-primary"><?= $rl; ?></span>
                                                <?php
                                                endif;
                                            endforeach;
                                            if ($role_count > $display_limit):
                                                ?>
                                                <button type="button" class="badge bg-secondary-transparent border-0"
                                                    data-bs-toggle="popover"
                                                    data-bs-placement="top"
                                                    data-bs-html="true"
                                                    data-bs-title="<?= __('Tất cả quyền hạn'); ?>"
                                                    data-bs-content="<?php
                                                                        $all_permissions = '';
                                                                        foreach ($roles as $permission) {
                                                                            $all_permissions .= '<span class=\'badge bg-primary-transparent me-1 mb-1\'>' . $permission . '</span>';
                                                                        }
                                                                        echo htmlspecialchars($all_permissions);
                                                                        ?>">
                                                    +<?= ($role_count - $display_limit); ?> <?= __('quyền khác'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
require_once(__DIR__ . '/footer.php');
?>


<div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered modal-lg dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2"><i class='bx bx-plus'></i> Tạo một role mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Tên vai trò (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="name" placeholder="VD: Super Admin" required>
                        </div>
                    </div>
                    <div class="form-check form-check-md d-flex align-items-center mb-2">
                        <input class="form-check-input" type="checkbox" value="" id="selectAll"
                            onclick="toggleAllCheckboxes()">
                        <label class="form-check-label" for="selectAll">
                            Chọn tất cả các quyền
                        </label>
                    </div>
                    <div class="row mb-4">
                        <?php foreach ($admin_roles as $category => $roles): ?>
                            <hr>
                            <div class="col-4">
                                <div class="form-check form-check-md d-flex align-items-center">
                                    <input class="form-check-input" type="checkbox" value=""
                                        id="<?= strtolower(str_replace(' ', '_', $category)) ?>"
                                        onclick="toggleCategory('<?= strtolower(str_replace(' ', '_', $category)) ?>')">
                                    <label class="form-check-label"
                                        for="<?= strtolower(str_replace(' ', '_', $category)) ?>">
                                        <?= $category ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-8">
                                <?php foreach ($roles as $key => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="<?= $key ?>" name="role[]"
                                            id="<?= $key ?>"
                                            data-category="<?= strtolower(str_replace(' ', '_', $category)) ?>">
                                        <label class="form-check-label" for="<?= $key ?>">
                                            <?= $label ?> <span class="badge bg-primary-transparent"><?= $key; ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <script>
                        function toggleAllCheckboxes() {
                            var checkboxes = document.querySelectorAll('[name="role[]"]');
                            var selectAllCheckbox = document.getElementById('selectAll');

                            checkboxes.forEach(function(checkbox) {
                                checkbox.checked = selectAllCheckbox.checked;
                            });
                        }

                        function toggleCategory(categoryId) {
                            var checkboxes = document.querySelectorAll('[data-category="' + categoryId + '"]');
                            var categoryCheckbox = document.getElementById(categoryId);
                            var selectAllCheckbox = document.getElementById('selectAll');

                            checkboxes.forEach(function(checkbox) {
                                checkbox.checked = categoryCheckbox.checked;
                            });

                            // Kiểm tra xem tất cả ô checkbox trong danh mục đã được chọn hay không
                            selectAllCheckbox.checked = checkboxes.length === document.querySelectorAll('[data-category="' +
                                categoryId + '"]:checked').length;
                        }
                    </script>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light " data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="addRole" class="btn btn-primary shadow-primary btn-wave"><i
                            class="fa fa-fw fa-plus me-1"></i>Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>




<script>
    $('#datatable-basic').DataTable({
        language: {
            searchPlaceholder: 'Search...',
            sSearch: '',
        },
        "pageLength": 10,
        scrollX: true
    });
</script>

<script>
    function postRemove(id) {
        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
            type: 'POST',
            dataType: "JSON",
            data: {
                action: 'removeRole',
                id: id
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, result.status);
                } else {
                    showMessage(result.msg, result.status);
                }
            }
        });
    }

    function remove(id) {
        cuteAlert({
            type: "question",
            title: "Xác nhận xóa Role",
            message: "Bạn có chắc chắn muốn xóa Role này không ?",
            confirmText: "Đồng ý",
            cancelText: "Không"
        }).then((e) => {
            if (e) {
                postRemove(id);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        })
    }
</script>