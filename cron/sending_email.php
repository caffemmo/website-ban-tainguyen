<?php

    define("IN_SITE", true);

    require_once(__DIR__.'/../libs/db.php');
    require_once(__DIR__.'/../config.php');
    require_once(__DIR__.'/../libs/helper.php');
    require_once(__DIR__.'/../libs/lang.php');
    require_once(__DIR__.'/../libs/sendEmail.php');
    $CMSNT = new DB();


    // Nếu có đặt key cron job thì kiểm tra key hợp lệ
    if(!empty($CMSNT->site('key_cron_job'))){
        if(empty($_GET['key']) || $_GET['key'] != $CMSNT->site('key_cron_job')){
            die(__('Key không hợp lệ'));
        }
    }
    
    /* START CHỐNG SPAM */
    $elapsed = time() - (int)$CMSNT->site('check_time_cron_sending_email');
    if ($elapsed >= 0 && $elapsed < 3) {
        die('Thao tác quá nhanh, vui lòng đợi');
    }

    $CMSNT->update("settings", [
        'value' => time()
    ], " `name` = 'check_time_cron_sending_email' ");


    if($CMSNT->site('smtp_status') != 1){
        die('Vui lòng cấu hình SMTP');
    }
    if($CMSNT->site('smtp_email') == '' || $CMSNT->site('smtp_password') == ''){
        die('Vui lòng cấu hình SMTP');
    }
    $arrContextOptions=array(
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ),
    ); 

    $checkAddon = true;
    
    foreach($CMSNT->get_list_safe(" SELECT * FROM `email_campaigns` WHERE `status` = ? ", [0]) as $camp){

        foreach($CMSNT->get_list_safe(" SELECT * FROM `email_sending` WHERE `camp_id` = ? AND `status` = ? ORDER BY id ASC LIMIT ? ", [$camp['id'], 0, 20]) as $row){

            $content = $camp['content'];
            $title = $camp['subject'];
            $content_email = file_get_contents(base_url('libs/mails/notification.php'), false, stream_context_create($arrContextOptions));
            $content_email = str_replace('{title}', $title, $content_email);
            $content_email = str_replace('{content}', $content, $content_email);
            $email = getRowRealtime('users', $row['user_id'], 'email');
            $response = 'Vui lòng kích hoạt Addon này';
            $status = 2;
            
            if($email == ''){
                $response = 'Không tìm thấy Email người nhận';
                $status = 2;
            } else {
                $response = sendCSM($email, $camp['cc'], $title, $content_email, $camp['bcc']);
                
                // Kiểm tra nếu lỗi liên quan đến giới hạn SMTP
                if (strpos($response, 'SMTP đạt giới hạn') !== false || 
                    strpos($response, 'quota') !== false || 
                    strpos($response, 'limit') !== false) {
                    // Giữ nguyên trạng thái = 0 để có thể thử lại sau
                    $status = 0;
                } else if ($response === true) {
                    // Gửi thành công
                    $status = 1;
                } else {
                    // Các lỗi khác
                    $status = 2;
                }
            }

            $CMSNT->update('email_sending', [
                'status'            => $status,
                'update_gettime'    => gettime(),
                'response'          => $response
            ], " `id` = '".$row['id']."' ");
        }



        
        if(!$CMSNT->get_row_safe(" SELECT * FROM `email_sending` WHERE `camp_id` = ? AND `status` = ? ", [$camp['id'], 0])){
            $CMSNT->update('email_campaigns', [
                'status'            => 1,
                'update_gettime'    => gettime()
            ], " `id` = '".$camp['id']."' ");
        }


    }
 


