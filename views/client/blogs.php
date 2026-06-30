<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Blogs').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="'.BASE_URL('public/client/').'css/blog-grid.css">
';
$body['footer'] = '
 

 
';

if (isSecureCookie('user_login') == true) {
    require_once(__DIR__ . '/../../models/is_user.php');
}

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');


// ✅ Sử dụng validation functions an toàn
$limit = validate_int($_GET['limit'] ?? 10, 10, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;

$from = ($page - 1) * $limit;

// ✅ Sử dụng prepared statements pattern
$where_conditions = ["`status` = ?"];
$where_params = [1];

$keyword = '';
$shortby = 1;
$category = '';

// ✅ An toàn cho category filtering
if(!empty($_GET['category'])){
    $category = validate_int($_GET['category'], 1);
    if($category !== false) {
        $where_conditions[] = '`category_id` = ?';
        $where_params[] = $category;
    }
}

// ✅ An toàn cho keyword search
if(!empty($_GET['keyword'])){
    $keyword = validate_string($_GET['keyword'], 255, 2);
    if($keyword !== false) {
        $where_conditions[] = '`title` LIKE ?';
        $where_params[] = '%'.$keyword.'%';
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

// ✅ An toàn cho shortby parameter với whitelist
if(isset($_GET['shortby'])){
    $shortby = validate_int($_GET['shortby'], 1, 2) ?: 1;
}

// ✅ An toàn cho ORDER BY clause
$order_clause = '';
if($shortby == 1){
    $order_clause = " ORDER BY `id` DESC ";
}
if($shortby == 2){
    $order_clause = " ORDER BY `view` DESC ";
}

// ✅ Sử dụng prepared statements an toàn
$where_clause = implode(' AND ', $where_conditions);
$sql_list = "SELECT * FROM `posts` WHERE $where_clause $order_clause LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_with_limit);

$sql_count = "SELECT COUNT(*) as total FROM `posts` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($sql_count, $where_params)['total'];

$urlDatatable = pagination_client(base_url("?action=blogs&limit=$limit&keyword=$keyword&shortby=$shortby&category=$category&"), $from, $totalDatatable, $limit);
?>
<?php if($category != 0):?>
<section class="inner-section single-banner"
    style="background: url(<?=base_url(getRowRealtime('post_category', $category, 'icon'));?>) no-repeat center;">
    <div class="container">
        <h2><?=getRowRealtime('post_category', $category, 'name');?></h2>
    </div>
</section>
<?php endif?>
<section class="inner-section <?=$category == 0 ? 'py-5' : '';?> blog-grid">
    <form action="<?=base_url();?>" method="GET">
        <input type="hidden" name="action" value="blogs">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="top-filter">
                                <div class="filter-show"><label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                        <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                        <option <?=$limit == 40 ? 'selected' : '';?> value="40">40</option>
                                        <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                        <option <?=$limit == 400 ? 'selected' : '';?> value="400">400</option>
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000</option>
                                    </select>
                                </div>
                                <div class="filter-action"><label class="filter-label">Short by :</label>
                                    <select name="shortby" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?=$shortby == 1 ? 'selected' : '';?> value="1"><?=__('Mặc định');?>
                                        </option>
                                        <option <?=$shortby == 2 ? 'selected' : '';?> value="2"><?=__('Phổ biến');?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php foreach ($listDatatable as $row):?>
                        <div class="col-md-6 col-lg-6">
                            <div class="blog-card">
                                <div class="blog-media"><a class="blog-img"
                                        href="<?=base_url('blog/'.$row['slug']);?>"><img
                                            style="width: 100%; height: 300px;" src="<?=base_url($row['image']);?>"
                                            alt="blog"></a></div>
                                <div class="blog-content">
                                    <ul class="blog-meta">
                                        <li><i
                                                class="fas fa-user"></i><span><?=getRowRealtime('users', $row['user_id'], 'fullname');?></span>
                                        </li>
                                        <li><i class="fas fa-calendar-alt"></i><span><?=$row['create_gettime'];?></span>
                                        </li>
                                    </ul>
                                    <h4 class="blog-title"><a
                                            href="<?=base_url('blog/'.$row['slug']);?>"><?=$row['title'];?></a></h4>
                                    <p class="blog-desc">
                                        <?=strip_tags(substr(base64_decode($row['content']), 0, 200)) . ' ...';?></p><a
                                        class="blog-btn"
                                        href="<?=base_url('blog/'.$row['slug']);?>"><span><?=__('Xem thêm');?></span><i
                                            class="icofont-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach?>

                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="bottom-paginate">
                                <p class="page-info">Showing <?=$limit;?> of <?=$totalDatatable;?> Results</p>
                                <div class="pagination">
                                    <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="blog-widget">
                        <h3 class="blog-widget-title"><?=__('Tìm kiếm bài viết');?></h3>
                        <form class="blog-widget-form"></form>
                        <form class="blog-widget-form" action="<?=base_url();?>" method="GET">
                            <input type="hidden" name="action" value="blogs">
                            <input type="text" name="keyword" value="<?=$keyword;?>"
                                placeholder="<?=__('Search blogs');?>">
                            <button class="icofont-search-1"></button>
                        </form>
                    </div>
                    <div class="blog-widget">
                        <h3 class="blog-widget-title"><?=__('Bài viết phổ biến');?></h3>
                        <ul class="blog-widget-feed">
                            <?php foreach($CMSNT->get_list_safe("SELECT * FROM `posts` WHERE `status` = ? ORDER BY `view` DESC", [1]) as $popular):?>
                            <li>
                                <a class="blog-widget-media" href="<?=base_url('blog/'.$popular['slug']);?>"><img
                                        style="height: 100%;" src="<?=base_url($popular['image']);?>"
                                        alt="blog-widget"></a>
                                <h6 class="blog-widget-text"><a
                                        href="<?=base_url('blog/'.$popular['slug']);?>"><?=$popular['title'];?></a><span
                                        class="fw-bold text-dark"><?=getRowRealtime('post_category', $popular['category_id'], 'name');?></span>
                                </h6>
                            </li>
                            <?php endforeach?>

                        </ul>
                    </div>
                    <div class="blog-widget">
                        <h3 class="blog-widget-title"><?=__('Chuyên mục');?></h3>
                        <ul class="blog-widget-category">
                            <?php foreach($CMSNT->get_list_safe("SELECT * FROM `post_category` WHERE `status` = ?", [1]) as $category):?>
                            <li><a href="<?=base_url('?action=blogs&category='.$category['id']);?>"><?=$category['name'];?>
                                    <span><?=$CMSNT->get_row_safe("SELECT COUNT(id) as total FROM `posts` WHERE `category_id` = ?", [$category['id']])['total'];?></span></a>
                            </li>
                            <?php endforeach?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>


<?php
require_once(__DIR__.'/footer.php');
?>