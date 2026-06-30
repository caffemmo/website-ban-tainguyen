<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Danh sách bài viết').' | '.$CMSNT->site('title'),
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
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_blog') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
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
$title = '';
$shortByDate  = '';

 
if(!empty($_GET['title'])){
    $title = check_string($_GET['title']);
    $where .= ' AND `title` LIKE "%'.$title.'%" ';
}
if(!empty($_GET['category'])){
    $category = check_string($_GET['category']);
    $where .= ' AND `category_id` = '.$category.' ';
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

$listDatatable = $CMSNT->get_list(" SELECT * FROM `posts` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `posts` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("blogs&limit=$limit&shortByDate=$shortByDate&title=$title&category=$category&create_gettime=$create_gettime&"), $from, $totalDatatable, $limit);


?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Blogs</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active" aria-current="page">Blogs</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH BÀI VIẾT
                        </div>
                        <div class="d-flex">
                            <a type="button" href="<?=base_url_admin('blog-add');?>"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> Viết bài mới</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="blogs">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$title;?>" name="title"
                                        placeholder="<?=__('Title');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <select class="form-control form-control-sm mb-1" name="category">
                                        <option value="">-- Chuyên mục --</option>
                                        <?php foreach($CMSNT->get_list(" SELECT * FROM `post_category` ") as $listcategory):?>
                                        <option <?=$listcategory['id'] == $category ? 'selected' : '';?>
                                            value="<?=$listcategory['id'];?>"><?=$listcategory['name'];?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Search');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger" href="<?=base_url_admin('blogs');?>"><i
                                            class="fa fa-trash"></i>
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
                                        <th><?=__('Tiêu đề bài viết');?></th>
                                        <th><?=__('Ảnh');?></th>
                                        <th><?=__('Chuyên mục');?></th>
                                        <th class="text-center"><?=__('Trạng thái');?></th>
                                        <th class="text-center">Lượt xem</th>
                                        <th><?=__('Thao tác');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><?=$row['title'];?></td>
                                        <td><?php if(!empty($row['image'])):?><img src="<?=base_url($row['image']);?>"
                                                width="100px"><?php endif?></td>
                                        <td><a class="text-primary" href="<?=base_url_admin('blog-category-edit&id='.$row['category_id']);?>"
                                                target="_blank"><i class="fa fa-pencil-alt"></i>
                                                <?=getRowRealtime('post_category', $row['category_id'], 'name');?></a>
                                        </td>
                                        <td class="text-center"><?=display_status_product($row['status']);?></td>
                                        <td class="text-center"><?=$row['view'];?> lượt xem</td>
                                        <td>
                                            <a type="button" target="_blank" href="<?=base_url('blog/'.$row['slug']);?>"
                                                class="btn btn-sm btn-light" data-bs-toggle="tooltip"
                                                title="<?=__('Xem');?>">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a type="button" href="<?=base_url_admin('blog-edit&id='.$row['id']);?>"
                                                class="btn btn-sm btn-light" data-bs-toggle="tooltip"
                                                title="<?=__('Chỉnh sửa');?>">
                                                <i class="fa fa-pencil-alt"></i>
                                            </a>
                                            <a type="button" onclick="RemoveRow('<?=$row['id'];?>')"
                                                class="btn btn-sm btn-light" data-bs-toggle="tooltip"
                                                title="<?=__('Xoá');?>">
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


<?php
require_once(__DIR__.'/footer.php');
?>

<script>
CKEDITOR.replace("content");


function RemoveRow(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Xác nhận xoá item');?>",
        message: "<?=__('Bạn có chắc chắn muốn xóa item này không ?');?>",
        confirmText: "<?=__('Đồng Ý');?>",
        cancelText: "<?=__('Huỷ');?>"
    }).then((e) => {
        if (e) {
            $.ajax({
                url: "<?=BASE_URL("ajaxs/admin/remove.php");?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    id: id,
                    action: 'removePost'
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