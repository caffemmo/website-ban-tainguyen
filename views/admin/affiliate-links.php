<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => 'Danh sách liên kết tiếp thị',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap5.min.css">
';
$body['footer'] = '
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_affiliate') != true){
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back().location.reload();}</script>');
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
$where = " `ref_click` > 0 ";
$username = '';
$user_id = '';
$sort_ref_total_price = '';
$sort_ref_price = '';
$sort_total_members = '';
$sort_ref_click = '';

if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")){
        $where .= ' AND `id` =  "'.$idUser['id'].'" ';
    }else{
        $where .= ' AND `id` =  "" ';
    }
}
if(!empty($_GET['user_id'])){
    $user_id = check_string($_GET['user_id']);
    $where .= ' AND `id` = "'.$user_id.'" ';
}
if(!empty($_GET['sort_ref_total_price'])){
    $sort_ref_total_price = check_string($_GET['sort_ref_total_price']);
}
if(!empty($_GET['sort_ref_price'])){
    $sort_ref_price = check_string($_GET['sort_ref_price']);
}
if(!empty($_GET['sort_total_members'])){
    $sort_total_members = check_string($_GET['sort_total_members']);
}
if(!empty($_GET['sort_ref_click'])){
    $sort_ref_click = check_string($_GET['sort_ref_click']);
}

// Xây dựng câu SQL với sắp xếp
$orderBy = " ORDER BY u.`ref_click` DESC ";
$orderParts = [];

// Sắp xếp theo tổng tiền hoa hồng đã kiếm
if($sort_ref_total_price == 'asc' || $sort_ref_total_price == 'desc'){
    $orderParts[] = "u.`ref_total_price` " . ($sort_ref_total_price == 'asc' ? 'ASC' : 'DESC');
}

// Sắp xếp theo số tiền hoa hồng hiện tại
if($sort_ref_price == 'asc' || $sort_ref_price == 'desc'){
    $orderParts[] = "u.`ref_price` " . ($sort_ref_price == 'asc' ? 'ASC' : 'DESC');
}

// Sắp xếp theo số thành viên đăng ký
if($sort_total_members == 'asc' || $sort_total_members == 'desc'){
    $orderParts[] = "total_members " . ($sort_total_members == 'asc' ? 'ASC' : 'DESC');
}

// Sắp xếp theo số lượng click
if($sort_ref_click == 'asc' || $sort_ref_click == 'desc'){
    $orderParts[] = "u.`ref_click` " . ($sort_ref_click == 'asc' ? 'ASC' : 'DESC');
}

// Kết hợp các điều kiện sắp xếp
if(!empty($orderParts)){
    $orderBy = " ORDER BY " . implode(", ", $orderParts) . ", u.`ref_click` DESC ";
}

// Query danh sách users có ref_click > 0 với thông tin đếm số thành viên đăng ký
$listDatatable = $CMSNT->get_list(" 
    SELECT 
        u.*,
        (SELECT COUNT(*) FROM `users` WHERE `ref_id` = u.`id`) as total_members
    FROM `users` u 
    WHERE $where 
    $orderBy
    LIMIT $from,$limit 
");

$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `users` WHERE $where ");
// Tạo URL pagination với các tham số filter
$pagination_url = base_url_admin('affiliate-links');
$pagination_url .= '&limit='.$limit;
if(!empty($user_id)) $pagination_url .= '&user_id='.urlencode($user_id);
if(!empty($username)) $pagination_url .= '&username='.urlencode($username);
if(!empty($sort_ref_total_price)) $pagination_url .= '&sort_ref_total_price='.urlencode($sort_ref_total_price);
if(!empty($sort_ref_price)) $pagination_url .= '&sort_ref_price='.urlencode($sort_ref_price);
if(!empty($sort_total_members)) $pagination_url .= '&sort_total_members='.urlencode($sort_total_members);
if(!empty($sort_ref_click)) $pagination_url .= '&sort_ref_click='.urlencode($sort_ref_click);
$pagination_url .= '&';
$urlDatatable = pagination($pagination_url, $from, $totalDatatable, $limit);

?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">Danh sách liên kết tiếp thị</h1>
        </div>
        <!-- Form tìm kiếm -->
        <div class="card custom-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="toggleFilterForm()">
                <h6 class="mb-0">
                    <i class="fa-solid fa-filter me-2"></i><?=__('Bộ lọc tìm kiếm');?>
                </h6>
                <button type="button" class="btn btn-sm btn-light" id="toggleFilterBtn">
                    <i class="fa-solid fa-chevron-down" id="filterIcon"></i>
                </button>
            </div>
            <div class="card-body" id="filterFormBody" style="display: none;">
                <form method="GET" action="<?=base_url_admin();?>">
                    <input type="hidden" name="module" value="admin">
                    <input type="hidden" name="action" value="affiliate-links">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label"><?=__('ID User');?></label>
                            <input type="number" class="form-control" name="user_id" 
                                value="<?=$user_id;?>" 
                                placeholder="<?=__('ID người dùng');?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?=__('Username');?></label>
                            <input type="text" class="form-control" name="username" 
                                value="<?=htmlspecialchars($username);?>" 
                                placeholder="<?=__('Username...');?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?=__('Sắp xếp theo tổng tiền hoa hồng đã kiếm');?></label>
                            <select name="sort_ref_total_price" class="form-select">
                                <option value=""><?=__('Mặc định');?></option>
                                <option <?=$sort_ref_total_price == 'asc' ? 'selected' : '';?> value="asc"><?=__('Tăng dần');?></option>
                                <option <?=$sort_ref_total_price == 'desc' ? 'selected' : '';?> value="desc"><?=__('Giảm dần');?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?=__('Sắp xếp theo số tiền hoa hồng hiện tại');?></label>
                            <select name="sort_ref_price" class="form-select">
                                <option value=""><?=__('Mặc định');?></option>
                                <option <?=$sort_ref_price == 'asc' ? 'selected' : '';?> value="asc"><?=__('Tăng dần');?></option>
                                <option <?=$sort_ref_price == 'desc' ? 'selected' : '';?> value="desc"><?=__('Giảm dần');?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?=__('Sắp xếp theo số thành viên đăng ký');?></label>
                            <select name="sort_total_members" class="form-select">
                                <option value=""><?=__('Mặc định');?></option>
                                <option <?=$sort_total_members == 'asc' ? 'selected' : '';?> value="asc"><?=__('Tăng dần');?></option>
                                <option <?=$sort_total_members == 'desc' ? 'selected' : '';?> value="desc"><?=__('Giảm dần');?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?=__('Sắp xếp theo số lượng click');?></label>
                            <select name="sort_ref_click" class="form-select">
                                <option value=""><?=__('Mặc định');?></option>
                                <option <?=$sort_ref_click == 'asc' ? 'selected' : '';?> value="asc"><?=__('Tăng dần');?></option>
                                <option <?=$sort_ref_click == 'desc' ? 'selected' : '';?> value="desc"><?=__('Giảm dần');?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?=__('Số lượng/trang');?></label>
                            <select class="form-select" name="limit">
                                <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                <option <?=$limit == 500 ? 'selected' : '';?> value="500">500</option>
                                <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000</option>
                            </select>
                        </div>
                        <div class="col-md-12 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fa-solid fa-filter me-1"></i><?=__('Lọc');?>
                            </button>
                            <a href="<?=base_url_admin('affiliate-links');?>" class="btn btn-secondary">
                                <i class="fa-solid fa-times me-1"></i><?=__('Bỏ lọc');?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách liên kết tiếp thị -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-0">
                        <?php if(count($listDatatable) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover border text-nowrap">
                                <thead>
                                    <tr>
                                        <th><?=__('Username');?></th>
                                        <th class="text-center"><?=__('Link tiếp thị');?></th>
                                        <th class="text-center"><?=__('Số lượng click');?></th>
                                        <th class="text-center"><?=__('Số thành viên đăng ký');?></th>
                                        <th class="text-center"><?=__('Tổng tiền hoa hồng đã kiếm');?></th>
                                        <th class="text-center"><?=__('Số tiền hoa hồng hiện tại');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <?php
                                        $affiliate_link = base_url('?aff='.$row['id']);
                                    ?>
                                    <tr>
                                        <td><a class="text-primary"
                                                href="<?=base_url_admin('user-edit&id='.$row['id']);?>"><?=$row['username'];?>
                                                [ID <?=$row['id'];?>]</a>
                                        </td>
                                        <td class="text-center">
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-sm" 
                                                    id="aff_link_<?=$row['id'];?>" 
                                                    value="<?=$affiliate_link;?>" readonly>
                                                <button class="btn btn-sm btn-primary" 
                                                    type="button" 
                                                    data-clipboard-target="#aff_link_<?=$row['id'];?>"
                                                    title="Copy link">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center"><?=format_cash($row['ref_click']);?></td>
                                        <td class="text-center">
                                            <?php if($row['total_members'] > 0): ?>
                                                <a href="javascript:void(0);" 
                                                   class="text-primary view-members" 
                                                   data-ref-id="<?=$row['id'];?>"
                                                   style="cursor: pointer; text-decoration: underline;">
                                                    <?=format_cash($row['total_members']);?>
                                                </a>
                                            <?php else: ?>
                                                <?=format_cash($row['total_members']);?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right"><?=format_currency($row['ref_total_price']);?></td>
                                        <td class="text-right"><?=format_currency($row['ref_price']);?></td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang -->
                        <?php if($totalDatatable > $limit): ?>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-sm-12 col-md-5">
                                    <p class="dataTables_info"><?=__('Showing');?> <?=$limit;?> <?=__('of');?> <?=number_format($totalDatatable);?> <?=__('Results');?></p>
                                </div>
                                <div class="col-sm-12 col-md-7">
                                    <?=$urlDatatable;?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="alert alert-warning m-3">
                            <i class="fa-solid fa-exclamation-circle me-2"></i><?=__('Chưa có dữ liệu nào.');?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal hiển thị danh sách thành viên -->
<div class="modal fade" id="modalAffiliateMembers" tabindex="-1" aria-labelledby="modalAffiliateMembersLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAffiliateMembersLabel">Danh sách thành viên đăng ký</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" id="memberInfo" style="display: none;">
                    Link affiliate của <strong id="refUsername"></strong> - Tổng: <strong id="totalMembers">0</strong> thành viên
                </div>
                <div class="table-responsive">
                    <table id="membersTable" class="table table-striped table-hover table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th class="text-center">Số dư hiện tại</th>
                                <th class="text-center">Tổng nạp</th>
                                <th class="text-center">Ngày đăng ký</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
var membersDataTable = null;

document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo Clipboard.js cho tất cả các nút copy
    var clipboard = new ClipboardJS('[data-clipboard-target]');
    
    clipboard.on('success', function(e) {
        e.trigger.innerHTML = '<i class="fa fa-check"></i>';
        e.trigger.classList.add('btn-success');
        e.trigger.classList.remove('btn-primary');
        setTimeout(function() {
            e.trigger.innerHTML = '<i class="fa fa-copy"></i>';
            e.trigger.classList.remove('btn-success');
            e.trigger.classList.add('btn-primary');
        }, 2000);
    });
    
    clipboard.on('error', function(e) {
        alert('Không thể copy link!');
    });
    
    // Xử lý click vào số lượng thành viên
    document.querySelectorAll('.view-members').forEach(function(element) {
        element.addEventListener('click', function() {
            var refId = this.getAttribute('data-ref-id');
            loadAffiliateMembers(refId);
        });
    });
});

function loadAffiliateMembers(refId) {
    // Hiển thị modal
    var modal = new bootstrap.Modal(document.getElementById('modalAffiliateMembers'));
    modal.show();
    
    // Destroy DataTable cũ nếu có
    if (membersDataTable) {
        membersDataTable.destroy();
        membersDataTable = null;
    }
    
    // Reset info
    document.getElementById('memberInfo').style.display = 'none';
    
    // Gọi AJAX để lấy danh sách thành viên
    $.ajax({
        url: '<?=base_url('ajaxs/admin/view.php');?>',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_affiliate_members',
            ref_id: refId
        },
        success: function(response) {
            if(response.status === 'success') {
                document.getElementById('refUsername').textContent = response.ref_username;
                document.getElementById('totalMembers').textContent = response.total;
                document.getElementById('memberInfo').style.display = 'block';
                
                // Khởi tạo DataTable với dữ liệu
                membersDataTable = $('#membersTable').DataTable({
                    data: response.data,
                    columns: [
                        { 
                            data: null,
                            render: function(data, type, row) {
                                return '<a class="text-primary" href="' + row.edit_url + '">' + row.username + ' [ID ' + row.id + ']</a>';
                            }
                        },
                        { data: 'email' },
                        { 
                            data: 'money_formatted',
                            className: 'text-right'
                        },
                        { 
                            data: 'total_money_formatted',
                            className: 'text-right'
                        },
                        { 
                            data: 'create_date',
                            className: 'text-center'
                        }
                    ],
                    language: {
                        search: "Tìm kiếm:",
                        lengthMenu: "Hiển thị _MENU_ bản ghi",
                        info: "Hiển thị _START_ đến _END_ trong _TOTAL_ bản ghi",
                        infoEmpty: "Không có dữ liệu",
                        infoFiltered: "(lọc từ _MAX_ bản ghi)",
                        zeroRecords: "Không tìm thấy bản ghi nào",
                        paginate: {
                            first: "Đầu",
                            last: "Cuối",
                            next: "Sau",
                            previous: "Trước"
                        }
                    },
                    pageLength: 10,
                    order: [[4, 'desc']], // Sắp xếp theo ngày đăng ký mới nhất
                    responsive: true,
                    autoWidth: false
                });
            } else {
                cuteAlert({
                    type: "error",
                    title: "Lỗi",
                    message: response.msg,
                    buttonText: "Đóng"
                });
            }
        },
        error: function() {
            cuteAlert({
                type: "error",
                title: "Lỗi",
                message: "Có lỗi xảy ra khi tải dữ liệu",
                buttonText: "Đóng"
            });
        }
    });
}

// Cleanup khi đóng modal
$('#modalAffiliateMembers').on('hidden.bs.modal', function () {
    if (membersDataTable) {
        membersDataTable.destroy();
        membersDataTable = null;
    }
});

// Toggle filter form
function toggleFilterForm() {
    var filterBody = document.getElementById('filterFormBody');
    var filterIcon = document.getElementById('filterIcon');
    
    if (filterBody.style.display === 'none') {
        filterBody.style.display = 'block';
        filterIcon.className = 'fa-solid fa-chevron-up';
        localStorage.setItem('affiliate_links_filter_expanded', 'true');
    } else {
        filterBody.style.display = 'none';
        filterIcon.className = 'fa-solid fa-chevron-down';
        localStorage.setItem('affiliate_links_filter_expanded', 'false');
    }
}

// Khôi phục trạng thái filter form khi load trang
$(document).ready(function() {
    var isFilterExpanded = localStorage.getItem('affiliate_links_filter_expanded');
    <?php 
    // Tự động mở nếu có filter đang active
    $has_active_filter = !empty($user_id) || !empty($username) || !empty($sort_ref_total_price) 
                        || !empty($sort_ref_price) || !empty($sort_total_members) || !empty($sort_ref_click);
    ?>
    <?php if($has_active_filter): ?>
    // Có filter đang active, tự động mở
    document.getElementById('filterFormBody').style.display = 'block';
    document.getElementById('filterIcon').className = 'fa-solid fa-chevron-up';
    <?php else: ?>
    // Không có filter, kiểm tra localStorage
    if (isFilterExpanded === 'true') {
        document.getElementById('filterFormBody').style.display = 'block';
        document.getElementById('filterIcon').className = 'fa-solid fa-chevron-up';
    }
    <?php endif; ?>
});
</script>

<?php
require_once(__DIR__.'/footer.php');
?>
