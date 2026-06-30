<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge Manual'),
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
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_recharge') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}


if (isset($_POST['SaveSettings'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_recharge') != true){
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình Manual Payment')
    ]);
    foreach ($_POST as $key => $value) {
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình nạp tiền thẻ cào'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);    
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("'.__('Save successfully!').'")){window.history.back().location.reload();}</script>');
} 



if (isset($_POST['AddPage'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }

    $url_icon = null;
    if (check_img('icon') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $uploads_dir = 'assets/storage/images/icon_gateway'.$rand.'.png';
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $url_icon = $uploads_dir;
        }
    }
    $isInsert = $CMSNT->insert("payment_manual", [
        'icon'              => $url_icon,
        'title'             => check_string($_POST['title']),
        'description'       => check_string($_POST['description']),
        'slug'              => check_string($_POST['slug']),
        'content'           => isset($_POST['content']) ? base64_encode($_POST['content']) : NULL,
        'display'           => check_string($_POST['display']),
        'create_gettime'    => gettime(),
        'update_gettime'    => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Tạo trang thanh toán thủ công')." (".check_string($_POST['title']).")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Tạo trang thanh toán thủ công')." (".check_string($_POST['title']).").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công !")){location.href = "";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Thêm thất bại !")){window.history.back().location.reload();}</script>');
    }
}
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Phương thức nạp tiền Thủ Công</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#">Nạp tiền</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manual Payment</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH TRANG NẠP TIỀN THỦ CÔNG
                        </div>
                        <div class="d-flex">
                            <button data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> Thêm trang mới</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="datatable-basic" class="table text-nowrap table-striped table-hover table-bordered"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?=__('Title');?></th>
                                    <th><?=__('Icon');?></th>
                                    <th><?=__('Trạng thái');?></th>
                                    <th><?=__('Thời gian thêm');?></th>
                                    <th><?=__('Cập nhật');?></th>
                                    <th><?=__('Thao tác');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 0; foreach ($CMSNT->get_list("SELECT * FROM `payment_manual`  ") as $payment_manual) {?>
                                <tr>
                                    <td><?=$i++;?></td>
                                    <td><?=$payment_manual['title'];?></td>
                                    <td><img width="40px" src="<?=base_url($payment_manual['icon']);?>"></td>
                                    <td><?=display_status_product($payment_manual['display']);?></td>
                                    <td><?=$payment_manual['create_gettime'];?></td>
                                    <td><?=$payment_manual['update_gettime'];?></td>
                                    <td><a aria-label=""
                                            href="<?=base_url_admin('recharge-manual-edit&id='.$payment_manual['id']);?>"
                                            style="color:white;" class="btn btn-info btn-sm btn-icon-left m-b-10"
                                            type="button">
                                            <i class="fas fa-edit mr-1"></i><span class=""> Edit</span>
                                        </a>
                                        <button style="color:white;" onclick="RemoveRow('<?=$payment_manual['id'];?>')"
                                            class="btn btn-danger btn-sm btn-icon-left m-b-10" type="button">
                                            <i class="fas fa-trash mr-1"></i><span class=""> Delete</span>
                                        </button>
                                    </td>
                                </tr>
                                <?php }?>
                            </tbody>
                        </table>
                        <br>
                        <p>Hướng dẫn sử dụng chức năng này tại: <a class="text-primary"
                                href="https://help.cmsnt.co/huong-dan/shopclone7-cach-tao-trang-nap-tien-thu-cong-manual-payment/"
                                target="_blank">https://help.cmsnt.co/huong-dan/shopclone7-cach-tao-trang-nap-tien-thu-cong-manual-payment/</a>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
    data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2">Thêm trang mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Title:
                            <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input name="title" type="text" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Slug:
                            <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <div class="input-group">
                                <span class="input-group-text"><?=base_url('recharge-manual/');?></span>
                                <input type="text" class="form-control" name="slug" required>
                            </div>
                        </div>
                    </div>
                    <script>
                    function removeVietnameseTones(str) {
                        return str.normalize('NFD') // Tách tổ hợp ký tự và dấu
                            .replace(/[\u0300-\u036f]/g, '') // Loại bỏ dấu
                            .replace(/đ/g, 'd') // Chuyển đổi chữ "đ" thành "d"
                            .replace(/Đ/g, 'D'); // Chuyển đổi chữ "Đ" thành "D"
                    }

                    document.querySelector('input[name="title"]').addEventListener('input', function() {
                        var productName = this.value;

                        // Chuyển tên sản phẩm thành slug
                        var slug = removeVietnameseTones(productName.toLowerCase())
                            .replace(/ /g, '-') // Thay khoảng trắng bằng dấu gạch ngang
                            .replace(/[^\w-]+/g, ''); // Loại bỏ các ký tự không hợp lệ

                        // Đặt giá trị slug vào trường input slug
                        document.querySelector('input[name="slug"]').value = slug;
                    });
                    </script>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Description:</label>
                        <div class="col-sm-8">
                            <textarea class="form-control" name="description"></textarea>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Icon:
                            <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="file" class="custom-file-input" name="icon" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label"
                            for="example-hf-email"><?=__('Nội dung chi tiết:');?></label>
                        <div class="col-sm-12">
                            <textarea class="content" id="content" name="content"></textarea>
                            <br>
                            <ul>
                                <li><strong>{username}</strong> => Username của khách hàng.</li>
                                <li><strong>{id}</strong> => ID của khách hàng.</li>
                                <li><strong>{hotline}</strong> => Hotline đã nhập trong cài đặt.</li>
                                <li><strong>{email} </strong> => Email đã nhập trong cài đặt.</li>
                                <li><strong>{fanpage}</strong> => Fanpage đã nhập trong cài đặt.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Trạng thái:');?> <span
                                class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <select class="form-control" name="display" required>
                                <option value="1">ON</option>
                                <option value="0">OFF</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="AddPage" class="btn btn-primary btn-sm"><i
                            class="fa fa-fw fa-plus me-1"></i>
                        <?=__('Submit');?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once(__DIR__.'/footer.php');
?>


<script>
CKEDITOR.replace("content");
</script>

<script type="text/javascript">
function RemoveRow(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Xác nhận xóa item');?>",
        message: "<?=__('Bạn có chắc chắn muốn xóa ID');?> " + id + " ?",
        confirmText: "Okey",
        cancelText: "Close"
    }).then((e) => {
        if (e) {
            $.ajax({
                url: "<?=BASE_URL("ajaxs/admin/remove.php");?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'remove_payment_manual',
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
                    location.reload();
                }
            });
        }
    })
}
</script>
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