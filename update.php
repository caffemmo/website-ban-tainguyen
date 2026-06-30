<?php
define("IN_SITE", true);
require_once(__DIR__ . '/libs/db.php');
require_once(__DIR__ . '/libs/lang.php');
require_once(__DIR__ . '/libs/helper.php');
require_once(__DIR__ . '/config.php');
$CMSNT = new DB();


$whitelist = array(
    '::1'
);

$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);


if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
    die('Localhost không thể sử dụng chức năng này');
}
if ($CMSNT->site('status_update') == 1) {
    // Lấy phiên bản cũ
    $old_version = $config['version'];
    $project = $config['project'];
    // Lấy phiên bản mới từ API
    $new_version = curl_get_contents('http://api.cmsnt.co/version.php?version=' . $project, 4);

    // API sập hoặc timeout → bỏ qua
    if (empty($new_version) || $new_version === false) {
        die('Không thể kiểm tra phiên bản mới. Vui lòng thử lại sau.');
    }

    if ($old_version != $new_version) {
        //CONFIG THÔNG SỐ
        define('filename', 'update_' . random('ABC123456789', 6) . '.zip');
        $license_key = $CMSNT->site('license_key');
        define('serverfile', 'https://api.cmsnt.co/update_path_code.php?license=' . urlencode($license_key) . '&type=' . $project . '&domain=' . urlencode($_SERVER['SERVER_NAME']));
        // TIẾN HÀNH TẢI BẢN CẬP NHẬT TỪ SERVER VỀ
        $downloadContent = curl_get_contents(serverfile, 30);
        // Kiểm tra file tải về có hợp lệ không
        if (empty($downloadContent) || $downloadContent === false) {
            die('Cập nhật thất bại! Lỗi: Không thể tải file cập nhật từ server. Vui lòng kiểm tra license key và thử lại.');
        }
        file_put_contents(filename, $downloadContent);

        // Kiểm tra file đã được lưu thành công
        if (!file_exists(filename) || filesize(filename) == 0) {
            die('Cập nhật thất bại! Lỗi: File cập nhật tải về bị rỗng hoặc không thể lưu.');
        }

        // TIẾN HÀNH GIẢI NÉN BẢN CẬP NHẬT VÀ GHI ĐÈ VÀO HỆ THỐNG
        $file = filename;
        $path = pathinfo(realpath($file), PATHINFO_DIRNAME);
        $zip = new ZipArchive();
        $res = $zip->open($file);
        if ($res === true) {
            // Giải nén và kiểm tra kết quả
            $extractResult = $zip->extractTo($path);
            if ($extractResult === false) {
                $zipStatus = $zip->getStatusString();
                $zip->close();
                @unlink(filename);
                die('Cập nhật thất bại! Lỗi giải nén: ' . $zipStatus);
            }
            $zip->close();
            // XÓA FILE ZIP CẬP NHẬT TRÁNH TỤI KHÔNG MUA ĐÒI XÀI FREE
            unlink(filename);
            // TIẾN HÀNH INSTALL DATABASE MỚI
            $query = file_get_contents(BASE_URL('install.php'), false, stream_context_create($arrContextOptions));
            // RENAME FILE INSTALL DATABASE (ẩn file install, giữ lại cho chức năng Sửa lỗi nhanh)
            if ($query) {
                $install_hidden_name = $CMSNT->site('install_file_name');
                // Xóa file install đã rename trước đó (nếu có)
                if (!empty($install_hidden_name) && file_exists($install_hidden_name)) {
                    @unlink($install_hidden_name);
                }
                // Rename install.php sang tên ẩn
                if (!empty($install_hidden_name)) {
                    rename('install.php', $install_hidden_name);
                } else {
                    // Fallback: nếu chưa có setting thì xóa như cũ
                    unlink('install.php');
                }
            }

            // GHI LOG VÀO DATABASE
            $log_action = "Cập nhật hệ thống từ phiên bản {$old_version} lên phiên bản {$new_version}";
            $CMSNT->insert("logs", [
                'user_id'   => 0,
                'ip'        => myip(),
                'device'    => getUserAgent(),
                'createdate' => gettime(),
                'action'    => $log_action
            ]);

            die('Cập nhật thành công!');
        } else {
            // Map mã lỗi ZipArchive sang thông báo chi tiết
            $zipErrors = [
                ZipArchive::ER_EXISTS   => 'File đã tồn tại',
                ZipArchive::ER_INCONS   => 'File ZIP không nhất quán (bị hỏng)',
                ZipArchive::ER_INVAL    => 'Tham số không hợp lệ',
                ZipArchive::ER_MEMORY   => 'Lỗi cấp phát bộ nhớ (Memory allocation failure)',
                ZipArchive::ER_NOENT    => 'File ZIP không tồn tại',
                ZipArchive::ER_NOZIP    => 'File tải về không phải định dạng ZIP hợp lệ',
                ZipArchive::ER_OPEN     => 'Không thể mở file ZIP',
                ZipArchive::ER_READ     => 'Lỗi đọc file ZIP',
                ZipArchive::ER_SEEK     => 'Lỗi tìm kiếm trong file ZIP (Seek error)',
            ];
            $errorMsg = isset($zipErrors[$res]) ? $zipErrors[$res] : 'Lỗi không xác định (Mã lỗi: ' . $res . ')';
            $fileSize = @filesize(filename);
            @unlink(filename);
            die('Cập nhật thất bại! Lỗi mở file ZIP: ' . $errorMsg . '. Kích thước file: ' . ($fileSize !== false ? $fileSize . ' bytes' : 'không xác định'));
        }
    }
    die('Không có phiên bản mới nhất');
} else {
    die('Chức năng cập nhật tự động đang được tắt');
}
