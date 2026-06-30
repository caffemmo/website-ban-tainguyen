<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/session.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendCSM($mail_nhan, $ten_nhan, $chu_de, $noi_dung, $bcc = '', $path = '')
{
    $CMSNT = new DB();
    if ($noi_dung == '') {
        return 'Lỗi: Nội dung email không được để trống';
    }
    if ($CMSNT->site('smtp_status') == 1) {
        try {
            $mail = new PHPMailer(true); // true để bật chế độ ngoại lệ

            // Cấu hình SMTP
            $mail->SMTPDebug = 0;
            $mail->Debugoutput = "html";
            $mail->isSMTP();
            $mail->Host = $CMSNT->site('smtp_host');
            $mail->SMTPAuth = true;
            $mail->Username = $CMSNT->site('smtp_email');
            $mail->Password = $CMSNT->site('smtp_password');
            $mail->SMTPSecure = $CMSNT->site('smtp_encryption');
            $mail->Port = $CMSNT->site('smtp_port');

            // Cấu hình người gửi và người nhận
            $mail->setFrom($CMSNT->site('smtp_from_email'), $bcc);
            $mail->addAddress($mail_nhan, $ten_nhan);

            // Thêm tệp đính kèm nếu có
            if (!empty($path)) {
                $mail->addAttachment($path);
            }

            $mail->addReplyTo($CMSNT->site('smtp_from_email'), $bcc);
            $mail->isHTML(true);
            $mail->Subject = $chu_de;
            $mail->Body = $noi_dung;
            $mail->CharSet = 'UTF-8';

            // Gửi email
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Phân loại lỗi và trả về thông báo chi tiết
            $errorMessage = $e->getMessage();

            // Kiểm tra các loại lỗi phổ biến
            if (strpos($errorMessage, 'SMTP connect() failed') !== false) {
                return 'Lỗi: Không thể kết nối đến máy chủ SMTP';
            } elseif (strpos($errorMessage, 'SMTP authentication failed') !== false) {
                return 'Lỗi: Xác thực SMTP thất bại - tên đăng nhập hoặc mật khẩu không đúng';
            } elseif (strpos($errorMessage, 'Invalid address') !== false || strpos($errorMessage, 'Invalid email address') !== false) {
                return 'Lỗi: Địa chỉ email không hợp lệ';
            } elseif (strpos($errorMessage, 'Recipient address rejected') !== false) {
                return 'Lỗi: Địa chỉ người nhận bị từ chối';
            } elseif (strpos($errorMessage, 'quota') !== false || strpos($errorMessage, 'limit') !== false) {
                return 'Lỗi: SMTP đạt giới hạn gửi email';
            } elseif (strpos($errorMessage, 'timeout') !== false) {
                return 'Lỗi: Kết nối SMTP bị timeout';
            } else {
                // Trả về lỗi gốc nếu không khớp với các loại lỗi đã biết
                return 'Lỗi SMTP: ' . $errorMessage;
            }
        }
    }
    return 'Lỗi: Chưa cấu hình SMTP';
}
