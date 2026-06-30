<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}


$body = [
    'title' => __('Công cụ Check Live UID Facebook').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
 
';
$body['footer'] = '

';

if (isSecureCookie('user_login') == true) {
    require_once(__DIR__ . '/../../models/is_user.php');
}

if($CMSNT->site('status_menu_tools') != 1){
    redirect(base_url('client/home'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
?>


<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="posterd home mb-3" style="background-image: url(<?=base_url('mod/img/bg-intro.webp');?>)">
                    <div class="welcomto">
                        <div class="box-intro">
                            <img src="<?=base_url('mod/img/icon-facebook.png');?>" alt="Accnice" width="70" height="70">
                        </div>
                        <div class="">
                            <div
                                style="font-size: 15px; text-shadow: rgba(0, 0, 0, 0.25) 0px 3px 5px;font-family: Robot,Roboto,sans-serif;">
                                <?=__('Bạn đang xem');?></div>
                            <h1
                                style="color: #fff; font-size: 25px; font-weight:500; margin-top: 10px; text-shadow: rgba(0, 0, 0, 0.25) 0px 3px 5px;font-family: Robot,Roboto,sans-serif;">
                                <?=__('Tool Check live UID Facebook');?></h1>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once(__DIR__.'/widget_tools.php');?>
            <div class="mb-5"></div>
            <div class="col-12 d-flex justify-content-center mb-3">
                <div class="d-flex">
                    <span class="mx-1 px-4 py-3 border rounded bg-success text-white fw-bold">
                        Live: <span id="liveCount">0</span>
                    </span>
                    <span class="mx-1 px-4 py-3 border rounded bg-danger text-white fw-bold">
                        Dead: <span id="dieCount">0</span>
                    </span>
                    <span class="mx-1 px-4 py-3 border rounded bg-warning text-white fw-bold">
                        Checked: <span id="totalCount">0</span> account
                    </span>
                </div>
            </div>

            <div class="col-md-12">
                <div class="account-card pt-3">
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <label for
                                        class="form-label fw-bold text-success"><?=__('Nhập danh sách UID');?></label>
                                </div>
                                <div class="form-group">
                                    <textarea class="form-control" name id="listId"
                                        placeholder="<?=__('Mỗi dòng 1 UID');?>" rows="10" autofocus></textarea>
                                </div>
                            </div>
                            <center>
                                <button class="btn btn-primary fw-bold mb-5" id="btnStart">
                                    <i class="fa-solid fa-play"></i> Start </button>
                            </center>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <label for
                                                class="form-label fw-bold text-success"><?=__('Tài khoản Live');?></label>
                                            <button class="btn btn-secondary btn-sm"
                                                id="btnCopyLive"><b>Copy</b></button>
                                        </div>
                                        <div class="form-group">
                                            <textarea class="form-control" readonly name id="listLive"
                                                rows="10"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <label for
                                                class="form-label fw-bold text-danger"><?=__('Tài khoản Die');?></label>
                                            <button class="btn btn-secondary btn-sm"
                                                id="btnCopyDie"><b>Copy</b></button>
                                        </div>
                                        <div class="form-group">
                                            <textarea class="form-control" readonly name id="listDie"
                                                rows="10"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<?php
require_once(__DIR__.'/footer.php');
?>

<script>
$(document).ready(() => {

    $("#btnCopyLive").click(function() {
        $("#listLive").select();
        document.execCommand('copy');
        showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
    });
    $("#btnCopyDie").click(function() {
        $("#listDie").select();
        document.execCommand('copy');
        showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
    });

    let live = 0
    let dies = 0
    let c = 0;
    var n;
    var arrclone;

    $('#btnStart').click(() => {
        //get data

        if (!$('#listId').val().trim()) {
            get = false;
            return;
        }

        $("#listLive").empty()
        $("#listDie").empty()

        die = 0
        live = 0

        n = 0
        let data = $('#listId').val().split(/\r?\n/);
        //post len server

        data = [...new Set(data)];


        data = data.filter(nx => nx)
        arrclone = data
        c = arrclone.length;

        $("#totalCount").html(c)

        $('#btnStart').html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled',
            true);

        for (var i = 0; i < 20; i++) {
            check_live_uid_progress();
        }

        $("#listId").val(data.join("\n"))


    })

    function check_live_uid_progress() {


        $("#dieCount").html(dies.length)

        n = n + 1;
        var m = n - 1;

        if (!arrclone[m])
            return;

        var uid = get_uid(arrclone[m]);
        var url = 'https://graph.facebook.com/' + uid + '/picture?type=normal';
        fetch(url).then((response) => {
            if (/100x100/.test(response.url)) {
                $('#liveCount').show();
                live++;
                $('#liveCount').html(live);
                $('#listLive').append(arrclone[m] + "\n");
            } else {
                $('#dieCount').show();
                die++;
                $('#dieCount').html(die);
                $('#listDie').append(arrclone[m] + "\n");
            }
            // var r = $(".progress-bar");
            // var t = Math.floor(n * 100 / c);
            // r.css("width", t + "%"), jQuery("span", r).html(t + "%");
            if (n < c) {
                check_live_uid_progress();
            } else {
                $('#btnStart').html('<i class="fa-solid fa-play"></i> Start ').prop('disabled', false);
                return false;
            }
        });


    }

    function get_uid(data) {
        if (data && data.includes("|")) {
            var clone = data.split("|");
            return clone[0];
        }
        return data;

    }


})
</script>

<script type="text/javascript">
new ClipboardJS(".copy");

function copy() {
    showMessage("<?=__('Đã sao chép vào bộ nhớ tạm');?>", 'success');
}
</script>