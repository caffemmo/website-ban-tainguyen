<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once(__DIR__.'/../../models/is_user.php');

 
 

$body = [
    'title' => __('Lịch sử đơn hàng').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/wallet.css">

';
$body['footer'] = '
 
';

 
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');



// ✅ Sử dụng validation functions an toàn
$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;

$from = ($page - 1) * $limit;

// ✅ Sử dụng prepared statements pattern
$where_conditions = ["`buyer` = ?"];
$where_params = [$getUser['id']];

$shortByDate = '';
$trans_id = '';
$time = '';
$account = '';

// ✅ An toàn cho trans_id filtering
if(!empty($_GET['trans_id'])){
    $trans_id = validate_alphanumeric($_GET['trans_id'], 50);
    if($trans_id !== false) {
        $where_conditions[] = '`trans_id` = ?';
        $where_params[] = $trans_id;
    }
}

// ✅ An toàn cho account filtering (tìm trong product_sold)
if(!empty($_GET['account'])){
    $account = validate_string($_GET['account'], 100);
    if($account !== false) {
        $product_sold_rows = $CMSNT->get_list_safe('SELECT `trans_id` FROM `product_sold` WHERE `account` LIKE ? AND `buyer` = ?', ['%' . $account . '%', $getUser['id']]);
        if (!empty($product_sold_rows)) {
            $trans_ids = array_map(function ($row) {
                return $row['trans_id'];
            }, $product_sold_rows);
            $placeholders = implode(',', array_fill(0, count($trans_ids), '?'));
            $where_conditions[] = '`trans_id` IN (' . $placeholders . ')';
            $where_params = array_merge($where_params, $trans_ids);
        } else {
            // Không tìm thấy account nào, trả về kết quả rỗng
            $where_conditions[] = '1 = 0';
        }
    }
}

// ✅ An toàn cho date range filtering
if(!empty($_GET['time'])){
    $time = validate_string($_GET['time'], 50);
    if($time !== false) {
        $create_date_1 = str_replace('-', '/', $time);
        $create_date_1 = explode(' to ', $create_date_1);
        if(count($create_date_1) == 2 && $create_date_1[0] != $create_date_1[1]){
            $start_date = $create_date_1[0].' 00:00:00';
            $end_date = $create_date_1[1].' 23:59:59';
            // Validate date format
            if(validate_date($create_date_1[0], 'Y/m/d') !== false && validate_date($create_date_1[1], 'Y/m/d') !== false) {
                $where_conditions[] = '`create_gettime` >= ? AND `create_gettime` <= ?';
                $where_params[] = $start_date;
                $where_params[] = $end_date;
            }
        }
    }
}

// ✅ An toàn cho shortByDate filtering
if(isset($_GET['shortByDate'])){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false) {
        $yesterday = date('Y-m-d', strtotime("-1 day"));
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");

        if($shortByDate == 1){
            $where_conditions[] = '`create_gettime` LIKE ?';
            $where_params[] = '%'.$currentDate.'%';
        }
        if($shortByDate == 2){
            $where_conditions[] = 'YEAR(create_gettime) = ? AND WEEK(create_gettime, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = 'MONTH(create_gettime) = ? AND YEAR(create_gettime) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}
// ✅ Sử dụng prepared statements an toàn
$where_clause = implode(' AND ', $where_conditions);
$sql_list = "SELECT * FROM `product_order` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_with_limit);

$sql_count = "SELECT COUNT(*) as total FROM `product_order` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($sql_count, $where_params)['total'];

$urlDatatable = pagination_client(base_url("?action=product-orders&limit=$limit&trans_id=$trans_id&shortByDate=$shortByDate&time=$time&account=$account&"), $from, $totalDatatable, $limit);

?>


<div style="margin-bottom:40px;"></div>
<section class="inner-section">
    <div class="container">
        <?php if($CMSNT->site('notice_orders') != ''):?>
        <div class="col-md-12">
            <div class="account-card pt-3">
                <?=$CMSNT->site('notice_orders');?>
            </div>
        </div>
        <?php endif?>
        <div class="row">
            <div class="col-lg-12">
                <div class="home-heading mb-3">
                    <h3><i class="fa-solid fa-cart-shopping m-2"></i> <?=mb_strtoupper(__('Lịch sử đơn hàng'));?>
                    </h3>
                </div>
                <div class="account-card pt-3">
                    <form action="<?=base_url();?>" method="GET">
                        <input type="hidden" name="action" value="product-orders">
                        <div class="row">
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control mb-2" type="hidden" value="<?=$getUser['token'];?>"
                                    id="token">
                                <input class="form-control mb-2" type="text" value="<?=$trans_id;?>" name="trans_id"
                                    placeholder="<?=__('Mã đơn hàng');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input class="form-control mb-2" type="text" value="<?=$account;?>" name="account"
                                    placeholder="<?=__('Account');?>">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <input type="text" class="js-flatpickr form-control mb-2" id="example-flatpickr-range"
                                    name="time" placeholder="<?=__('Chọn thời gian cần tìm');?>" value="<?=$time;?>"
                                    data-mode="range">
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <button class="shop-widget-btn mb-2"><i
                                        class="fas fa-search"></i><span><?=__('Tìm kiếm');?></span></button>
                            </div>
                            <div class="col-lg col-md-4 col-6">
                                <a href="<?=base_url('product-orders/');?>" class="shop-widget-btn mb-2"><i
                                        class="far fa-trash-alt"></i><span><?=__('Bỏ lọc');?></span></a>
                            </div>
                        </div>
                        <div class="top-filter">
                            <div class="filter-show"><label class="filter-label">Show :</label>
                                <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                    <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                    <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                    <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                    <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                    <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                    <option <?=$limit == 500 ? 'selected' : '';?> value="500">500</option>
                                    <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000
                                    </option>
                                </select>
                            </div>
                            <div class="filter-short">
                                <label class="filter-label"><?=__('Short by Date:');?></label>
                                <select name="shortByDate" onchange="this.form.submit()"
                                    class="form-select filter-select">
                                    <option value=""><?=__('Tất cả');?></option>
                                    <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1">
                                        <?=__('Hôm nay');?>
                                    </option>
                                    <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2">
                                        <?=__('Tuần này');?>
                                    </option>
                                    <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3">
                                        <?=__('Tháng này');?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="table-scroll table-wrapper">
                        <table class="table fs-sm text-nowrap table-hover  mb-0">
                            <thead>
                                <th class="text-center">
                                    <input type="checkbox" class="form-check-input" name="check_all"
                                        id="check_all_checkbox" value="option1">
                                </th>
                                <th class="text-center"><?=__('Thao tác');?></th>
                                <th class="text-center"><?=__('Mã đơn hàng');?></th>
                                <th class="text-center"><?=__('Sản phẩm');?></th>
                                <th class="text-center"><?=__('Số lượng');?></th>
                                <th class="text-center"><?=__('Thanh toán');?></th>
                                <th class="text-center"><?=__('Ghi chú cá nhân');?></th>
                                <th class="text-center"><?=__('Thời gian');?></th>
                            </thead>
                            <tbody>
                                <?php foreach ($listDatatable as $order) {?>
                                <tr
                                    style="vertical-align: middle;<?=$order['trash'] == 1 ? 'background-color:#ffd6d6;' :'';?>">
                                    <td class="text-center">
                                        <?php if($order['trash'] == 0):?>
                                        <input type="checkbox" class="form-check-input checkbox"
                                            data-id="<?=$order['id'];?>" name="checkbox" value="<?=$order['id'];?>" />
                                        <?php endif?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($order['trash'] == 1):?>
                                        <strong><?=__('Đơn hàng đã bị xóa');?></strong>
                                        <?php else:?>
                                        <a class="btn btn-info btn-sm"
                                            href="<?=base_url('product-order/'.$order['trans_id']);?>" type="button"><i
                                                class="fa-solid fa-eye"></i>
                                            <?=__('View');?></a>
                                        <button class="btn btn-primary btn-sm"
                                            onclick="downloadOrder(`<?=$order['trans_id'];?>`)"><i
                                                class="fa-solid fa-cloud-arrow-down"></i>
                                            <?=__('Download');?></button>
                                        <button type="button" onclick="deleteOrder(`<?=$order['id'];?>`)"
                                            class="btn btn-danger btn-sm">
                                            <i class="fa-solid fa-trash"></i> <?=__('Delete');?>
                                        </button>
                                        <?php endif?>
                                    </td>
                                    <td class="text-center">
                                        <?=$order['trans_id'];?>
                                    </td>
                                    <td class="text-center">
                                        <strong><small><?=$order['product_name'];?></small></strong>
                                    </td>
                                    <td class="text-right">
                                        <span class="badge bg-primary"><?=format_cash($order['amount']);?></span>
                                    </td>
                                    <td class="text-right">
                                        <span class="badge bg-danger"><?=format_currency($order['pay']);?></span>
                                    </td>
                                    <td class="text-center">
                                        <textarea class="saveNote" rows="1"
                                            data-id="<?=$order['id'];?>"><?=$order['note'];?></textarea>
                                    </td>
                                    <td class="text-center">
                                        <strong data-toggle="tooltip" data-placement="bottom"
                                            title="<?=timeAgo(strtotime($order['create_gettime']));?>"><small><?=$order['create_gettime'];?></small></strong>
                                    </td>
                                </tr>
                                <?php }?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <button type="button" id="btn_delete" class="btn btn-danger btn-sm"
                                                    data-toggle="tooltip" data-placement="bottom"
                                                    title="<?=__('Xóa đơn hàng đã chọn khỏi lịch sử của bạn');?>">
                                                    <i class="fa-solid fa-trash"></i> <?=__('Xóa đơn hàng');?>
                                                </button>
                                            </div>
                                            <div class="col-md-6 text-right">
                                                <?php 
                                                // ⚡ Tối ưu: Gọi 1 query duy nhất để lấy thống kê
                                                $stats = $CMSNT->get_row_safe("SELECT 
                                                    SUM(`amount`) as total_amount,
                                                    SUM(`pay`) as total_pay
                                                    FROM `product_order` 
                                                    WHERE $where_clause
                                                ", $where_params);
                                                $total_amount = $stats['total_amount'] ?? 0;
                                                $total_pay = $stats['total_pay'] ?? 0;
                                                ?>
                                                <strong><?=__('Tổng số lượng tài khoản:');?></strong> 
                                                <strong style="color: #0d6efd;"><?=format_cash($total_amount);?></strong>
                                                |
                                                <strong><?=__('Tổng tiền hàng:');?></strong> 
                                                <strong style="color: #dc3545;"><?=format_currency($total_pay);?></strong>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="bottom-paginate">
                        <p class="page-info">Showing <?=$limit;?> of <?=$totalDatatable;?> Results</p>
                        <div class="pagination">
                            <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<script>
$(document).ready(function() {
    $('.saveNote').on('input', function() {
        saveNote($(this));
    });

    function saveNote(textarea) {
        var note = textarea.val();
        var id = textarea.data('id');
        $.ajax({
            url: "<?=BASE_URL("ajaxs/client/update.php");?>",
            method: "POST",
            dataType: "JSON",
            data: {
                id: id,
                note: note,
                token: $("#token").val(),
                action: 'saveNoteOrder'
            },
            success: function(result) {},
            error: function() {}
        });
    }
});
</script>
<script>
$(function() {
    $('#check_all_checkbox').on('click', function() {
        $('.checkbox').prop('checked', this.checked);
    });
    $('.checkbox').on('click', function() {
        $('#check_all_checkbox').prop('checked', $('.checkbox:checked')
            .length === $('.checkbox').length);
    });
});

function delete_records() {
    var checkbox = document.getElementsByName('checkbox');

    function postUpdatesSequentially(index) {
        if (index < checkbox.length) {
            if (checkbox[index].checked === true) {
                post_remove(checkbox[index].value);
            }
            setTimeout(function() {
                postUpdatesSequentially(index + 1);
            }, 100);
        } else {
            Swal.fire({
                title: "<?=__('Thành công!');?>",
                text: "<?=__('Xóa đơn hàng thành công');?>",
                icon: "success"
            });
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    }
    postUpdatesSequentially(0);
}

$("#btn_delete").click(function() {
    var checkboxes = document.querySelectorAll('input[name="checkbox"]:checked');
    if (checkboxes.length === 0) {
        showMessage('<?=__('Vui lòng chọn ít nhất một đơn hàng.');?>', 'error');
        return;
    }
    Swal.fire({
        title: "<?=__('Bạn có chắc không?');?>",
        text: "<?=__('Hệ thống sẽ xóa');?> " + checkboxes.length +
            " <?=__('đơn hàng bạn chọn khi nhấn Đồng Ý');?>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Đóng');?>"
    }).then((result) => {
        if (result.isConfirmed) {
            delete_records();
        }
    });
});
</script>

<script>
function post_remove(id) {
    $.ajax({
        url: "<?=BASE_URL("ajaxs/client/remove.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            id: id,
            token: $("#token").val(),
            action: 'removeOrder'
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, result.status);
            } else {
                showMessage(result.msg, result.status);
            }
        },
        error: function() {
            alert(html(response));
            location.reload();
        }
    });
}

function deleteOrder(id) {
    Swal.fire({
        title: "<?=__('Bạn có chắc không?');?>",
        text: "<?=__('Hệ thống sẽ xóa đơn hàng khỏi lịch sử của bạn khi bạn nhấn đồng ý');?>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Đóng');?>",
    }).then((result) => {
        if (result.isConfirmed) {
            post_remove(id);
            setTimeout(function() {
                location.reload();
            }, 500);
        }
    });
}
</script>



<script>
function downloadOrder(trans_id) {
    Swal.fire({
        title: "<?=__('Bạn có chắc không?');?>",
        text: "<?=__('Hệ thống sẽ tải về đơn hàng khi bạn nhấn đồng ý');?>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Đóng');?>",
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "<?=BASE_URL("ajaxs/client/view.php");?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'download_order',
                    trans_id: trans_id,
                    token: $("#token").val(),
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, result.status);
                        downloadTXT(result.filename, result.accounts);
                    } else {
                        Swal.fire({
                            title: "<?=__('Thất bại!');?>",
                            text: result.msg,
                            icon: "error"
                        });
                    }
                },
                error: function() {
                    alert(html(response));
                    location.reload();
                }
            });
        }
    });
}

function downloadTXT(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}
</script>



<?php
require_once(__DIR__.'/footer.php');
?>

<script>
Dashmix.helpersOnLoad(['js-flatpickr', 'jq-datepicker', 'jq-maxlength', 'jq-select2', 'jq-rangeslider',
    'jq-masked-inputs', 'jq-pw-strength'
]);
</script>