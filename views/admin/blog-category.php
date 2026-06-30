<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Chuyên mục bài viết').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '

<!-- Page JS Plugins CSS -->
<link rel="stylesheet" href="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-buttons-bs5/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-responsive-bs5/css/responsive.bootstrap5.min.css">
';
$body['footer'] = '


<!-- Page JS Plugins -->
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-buttons/dataTables.buttons.min.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-buttons-jszip/jszip.min.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-buttons-pdfmake/pdfmake.min.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-buttons-pdfmake/vfs_fonts.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-buttons/buttons.print.min.js"></script>
<script src="'.BASE_URL('public/theme/').'assets/js/plugins/datatables-buttons/buttons.html5.min.js"></script>
<!-- Page JS Code -->
<script src="'.BASE_URL('public/theme/').'assets/js/pages/be_tables_datatables.min.js"></script>

';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');

if(checkPermission($getUser['admin'], 'view_blog') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}

if (isset($_POST['submit'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
    }
    if ($CMSNT->get_row("SELECT * FROM `post_category` WHERE `name` = '".check_string($_POST['name'])."' ")) {
        die('<script type="text/javascript">if(!alert("Chuyên mục này đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
    }
    $url_icon = null;
    if (check_img('icon') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $uploads_dir = 'assets/storage/images/icon'.$rand.'.png';
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $url_icon = $uploads_dir;
        }
    }
    $isInsert = $CMSNT->insert("post_category", [
        'icon'          => $url_icon,
        'name'          => check_string($_POST['name']),
        'slug'          => create_slug(check_string($_POST['name'])),
        'content'       => isset($_POST['content']) ? base64_encode($_POST['content']) : NULL,
        'status'        => check_string($_POST['status']),
        'create_gettime'   => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Tạo chuyên mục bài viết')." (".check_string($_POST['name']).")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Tạo chuyên mục bài viết')." (".check_string($_POST['name']).")", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công !")){location.href = "";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Thêm thất bại !")){window.history.back().location.reload();}</script>');
    }
}


if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}
else{
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$category = '';
$create_gettime = '';
$name = '';
$shortByDate  = '';

 
if(!empty($_GET['name'])){
    $name = check_string($_GET['name']);
    $where .= ' AND `name` LIKE "%'.$name.'%" ';
}
if(!empty($_GET['create_gettime'])){
    $create_date = check_string($_GET['create_gettime']);
    $create_gettime = $create_date;
    $create_date_1 = str_replace('-', '/', $create_date);
    $create_date_1 = explode(' to ', $create_date_1);

    if($create_date_1[0] != $create_date_1[1]){
        $create_date_1 = [$create_date_1[0].' 00:00:00', $create_date_1[1].' 23:59:59'];
        $where .= " AND `create_gettime` >= '".$create_date_1[0]."' AND `create_gettime` <= '".$create_date_1[1]."' ";
    }
}
if(isset($_GET['shortByDate'])){
    $shortByDate = check_string($_GET['shortByDate']);
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if($shortByDate == 1){
        $where .= " AND `create_gettime` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `post_category` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `post_category` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("blog-category&limit=$limit&shortByDate=$shortByDate&name=$name&create_gettime=$create_gettime&"), $from, $totalDatatable, $limit);



?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-name fw-semibold fs-18 mb-0">Chuyên mục bài viết</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active" aria-current="page">Chuyên mục bài viết</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH CHUYÊN MỤC BÀI VIẾT
                        </div>
                        <div class="d-flex">
                            <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> Thêm chuyên mục</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="blog-category">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$name;?>" name="name"
                                        placeholder="Tên chuyên mục">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?=base_url_admin('blog-category');?>"><i class="fa fa-trash"></i>
                                        <?=__('Clear filter');?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                        <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                        <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                        <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                        <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                        <option <?=$limit == 500 ? 'selected' : '';?> value="500">500</option>
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?=__('Short by Date:');?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?>
                                        </option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?>
                                        </option>
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3">
                                            <?=__('Tháng này');?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th><?=__('Tên chuyên mục');?></th>
                                        <th><?=__('Ảnh');?></th>
                                        <th><?=__('Đường dẫn');?></th>
                                        <th><?=__('Trạng thái');?></th>
                                        <th><?=__('Thao tác');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><?=$row['name'];?></td>
                                        <td><?php if(!empty($row['icon'])):?><img src="<?=base_url($row['icon']);?>"
                                                width="40px"><?php endif?></td>
                                        <td><?=$row['slug'];?></td>
                                        <td><?=display_status_product($row['status']);?></td>
                                        <td>
                                            <a type="button"
                                                href="<?=base_url_admin('blog-category-edit&id='.$row['id']);?>"
                                                class="btn btn-sm btn-light" data-bs-toggle="tooltip"
                                                title="<?=__('Edit');?>">
                                                <i class="fa fa-pencil-alt"></i>
                                            </a>
                                            <a type="button" onclick="RemoveRow('<?=$row['id'];?>')"
                                                class="btn btn-sm btn-light" data-bs-toggle="tooltip"
                                                title="<?=__('Delete');?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=$limit;?> of <?=format_cash($totalDatatable);?>
                                    Results</p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered modal-xl dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2">Thêm chuyên mục bài viết mới</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Tên chuyên mục:');?>
                            <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="name"
                                placeholder="<?=__('Nhập tên chuyên mục');?>" required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Icon:');?></label>
                        <div class="col-sm-8">
                            <input type="file" class="custom-file-input" name="icon">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label"
                            for="example-hf-email"><?=__('Mô tả chi tiết:');?></label>
                        <div class="col-sm-12">
                            <textarea class="content" id="content" name="content"></textarea>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Status:');?> <span
                                class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <select class="form-control" name="status" required>
                                <option value="1">ON</option>
                                <option value="0">OFF</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="submit" class="btn btn-primary"><i class="fa fa-fw fa-plus me-1"></i>
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


function RemoveRow(id) {
    cuteAlert({
        type: "question",
        name: "Xác Nhận Xóa Chuyên Mục",
        message: "Bạn có chắc chắn muốn xóa chuyên mục ID " + id + " không ?",
        confirmText: "Đồng Ý",
        cancelText: "Hủy"
    }).then((e) => {
        if (e) {
            $.ajax({
                url: "<?=BASE_URL("ajaxs/admin/remove.php");?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    id: id,
                    action: 'removeCategoryBlog'
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