<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Danh sách menu',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '


';
$body['footer'] = '

';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_menu') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['AddMenu'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used as this is a demo site.').'")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_menu') != true){
        die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
    }
    if ($CMSNT->get_row("SELECT * FROM `menu` WHERE `name` = '".check_string($_POST['name'])."' ")) {
        die('<script type="text/javascript">if(!alert("Tên menu này đã tồn tại trong hệ thống.")){window.history.back().location.reload();}</script>');
    }
    $isCreate = $CMSNT->insert("menu", [
        'name'              => check_string($_POST['name']),
        'slug'              => create_slug(check_string($_POST['name'])),
        'href'              => !empty($_POST['href']) ? check_string($_POST['href']) : '',
        'icon'              => $_POST['icon'],
        'position'          => !empty($_POST['position']) ? check_string($_POST['position']) : 3,
        'target'            => !empty($_POST['target']) ? check_string($_POST['target']) : '',
        'content'           => !empty($_POST['content']) ? $_POST['content'] : '',
        'status'            => check_string($_POST['status'])
    ]);
    if ($isCreate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Menu (".check_string($_POST['name']).")"
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add Menu (".check_string($_POST['name']).")", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Thêm thành công !")){location.href = "'.BASE_URL('admin/menu-list').'";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Thêm menu thất bại, vui lòng thử lại!")){window.history.back().location.reload();}</script>');
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
$create_gettime = '';
$name = '';
$shortByDate  = '';

if(!empty($_GET['name'])){
    $name = check_string($_GET['name']);
    $where .= ' AND `name` LIKE "%'.$name.'%" ';
}
if(!empty($_GET['create_gettime'])){
    $create_gettime = check_string($_GET['create_gettime']);
    $createdate = $create_gettime;
    $create_gettime_1 = str_replace('-', '/', $create_gettime);
    $create_gettime_1 = explode(' to ', $create_gettime_1);
    if($create_gettime_1[0] != $create_gettime_1[1]){
        $create_gettime_1 = [$create_gettime_1[0].' 00:00:00', $create_gettime_1[1].' 23:59:59'];
        $where .= " AND `create_gettime` >= '".$create_gettime_1[0]."' AND `create_gettime` <= '".$create_gettime_1[1]."' ";
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
$listDatatable = $CMSNT->get_list(" SELECT * FROM `menu` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `menu` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("menu-list&limit=$limit&shortByDate=$shortByDate&name=$name&create_gettime=$create_gettime&"), $from, $totalDatatable, $limit);


?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-sitemap"></i> Quản lý Menu</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active" aria-current="page">Menu</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH MENU
                        </div>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                            class="btn btn-sm btn-primary shadow-primary"><i
                                class="ri-add-line fw-semibold align-middle"></i> Tạo một menu mới</button>
                    </div>
                    <div class="card-body">
                        <form action="" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="menu-list">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=$name;?>" name="name"
                                        placeholder="Tên menu">
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
                                        href="<?=base_url_admin('menu-list');?>"><i class="fa fa-trash"></i>
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
                                        <th>NAME</th>
                                        <th>HREF</th>
                                        <th>ICON</th>
                                        <th>TARGET</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td><?=$row['name']; ?></td>
                                        <td><a href="<?=$row['href']; ?>" target="_blank"><?=$row['href']; ?></a></td>
                                        <td><textarea class="form-control" rows="1"
                                                readonly><?=$row['icon']; ?></textarea></td>
                                        <td><?=$row['target']; ?></td>
                                        <td>
                                            <a type="button" href="<?=base_url_admin('menu-edit&id='.$row['id']);?>"
                                                class="btn btn-sm btn-light" data-bs-toggle="tooltip"
                                                title="<?=__('Edit');?>">
                                                <i class="fa fa-pencil-alt"></i>
                                            </a>
                                            <a type="button" onclick="remove('<?=$row['id'];?>')"
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
    <div class="modal-dialog modal-dialog-centered modal-xl dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2"><i class="fa-solid fa-plus"></i> Tạo một menu mới
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Tên menu (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" name="name" placeholder="Nhập tên menu cần tạo"
                                required>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Menu cha:');?>
                            <span class="text-danger">*</span></label>
                        <div class="col-sm-8">
                            <select class="form-control mb-2" name="parent_id" required>
                                <option value="0">Menu cha</option>
                                <?php foreach($CMSNT->get_list("SELECT * FROM `menu` WHERE `parent_id` = 0 ") as $option):?>
                                <option value="<?=$option['id'];?>" <?=$id == $option['id'] ? 'selected' : '';?>>
                                    <?=$option['name'];?></option>
                                <?php foreach($CMSNT->get_list("SELECT * FROM `menu` WHERE `parent_id` = '".$option['id']."' ") as $option1):?>
                                <option disabled value="<?=$option1['id'];?>">__<?=$option1['name'];?></option>
                                <?php endforeach?>
                                <?php endforeach?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Liên kết</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control"
                                placeholder="Nhập địa chỉ liên kết cần tới khi click vào menu này" name="href">
                            <small>Chỉ áp dụng khi nội dung hiển thị trống</small>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-12 col-form-label" for="example-hf-email">Nội dung hiển thị (nếu
                            có)</label>
                        <div class="col-sm-12">
                            <textarea id="content" name="content"
                                placeholder="Để trống nếu muốn sử dụng liên kết"></textarea>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Vị trí hiển thị</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="position" required>
                                <option value="1">Trong menu SỐ DƯ</option>
                                <option value="2">Trong menu NẠP TIỀN</option>
                                <option value="3">Trong menu KHÁC</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Icon menu (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" placeholder='Ví dụ: <i class="fas fa-home"></i>'
                                name="icon" required>
                            <small>Tìm thêm icon tại <a target="_blank"
                                    href="https://fontawesome.com/v5.15/icons?d=gallery&p=2">đây</a></small>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email">Trạng thái</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="status" required>
                                <option value="1">Hiển thị</option>
                                <option value="0">Ẩn</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-sm-4"></div>
                        <div class="col-sm-8">
                            <div class="form-check form-check-md d-flex align-items-center mb-2">
                                <input class="form-check-input" type="checkbox" name="target" value="_blank"
                                    id="customCheckbox2" checked>
                                <label class="form-check-label" for="customCheckbox2">
                                    Mở tab mới khi
                                    click
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light " data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="AddMenu" class="btn btn-primary shadow-primary btn-wave"><i
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
function postRemove(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeMenu',
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
        title: "Xác nhận xóa Menu",
        message: "Bạn có chắc chắn muốn xóa menu này không ?",
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


<script>
CKEDITOR.replace("content");
</script>