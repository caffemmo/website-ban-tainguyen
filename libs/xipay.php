<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once __DIR__ . '/session.php';
}

/* *
 * Lớp dịch vụ SDK của Rainbow Epay (彩虹易支付)
 * Mô tả:
 * Bao gồm các chức năng: khởi tạo thanh toán, truy vấn đơn hàng, xác minh callback, v.v.
 */

class EpayCore
{
    // Các biến cấu hình
    private $pid;            // ID thương nhân
    private $key;            // Khóa bí mật (merchant key)
    private $submit_url;     // URL để thực hiện thanh toán dạng chuyển hướng (page pay)
    private $mapi_url;       // URL API thanh toán (dành cho API interface)
    private $api_url;        // URL API chung (cho các chức năng truy vấn, hoàn trả, v.v.)
    private $sign_type = 'MD5'; // Loại chữ ký sử dụng (ở đây dùng MD5)

    // Hàm khởi tạo, nhận cấu hình từ mảng $config
    function __construct($config){
        $this->pid = $config['pid'];                // Lấy Merchant ID từ cấu hình
        $this->key = $config['key'];                // Lấy khóa bí mật từ cấu hình
        $this->submit_url = $config['apiurl'].'submit.php';  // URL thanh toán dạng trang (page pay)
        $this->mapi_url = $config['apiurl'].'mapi.php';      // URL API thanh toán (mapi)
        $this->api_url = $config['apiurl'].'api.php';        // URL API khác (truy vấn, hoàn trả, v.v.)
    }

    // Hàm khởi tạo thanh toán (Page Pay) - chuyển hướng người dùng
    public function pagePay($param_tmp, $button='正在跳转'){
        // Xây dựng tham số yêu cầu, bao gồm chữ ký
        $param = $this->buildRequestParam($param_tmp);

        // Tạo form HTML ẩn tự động submit để chuyển hướng thanh toán
        $html = '<form id="dopay" action="'.$this->submit_url.'" method="post">';
        foreach ($param as $k=>$v) {
            $html.= '<input type="hidden" name="'.$k.'" value="'.$v.'"/>';
        }
        $html .= '<input type="submit" value="'.$button.'"></form><script>document.getElementById("dopay").submit();</script>';

        return $html;
    }

    // Hàm lấy liên kết thanh toán (dạng GET)
    public function getPayLink($param_tmp){
        // Xây dựng tham số yêu cầu có chữ ký
        $param = $this->buildRequestParam($param_tmp);
        // Tạo URL chuyển hướng bằng cách nối query string
        $url = $this->submit_url.'?'.http_build_query($param);
        return $url;
    }

    // Hàm thực hiện thanh toán qua API (server-to-server)
    public function apiPay($param_tmp){
        $param = $this->buildRequestParam($param_tmp);
        // Gửi POST tới URL API mapi và nhận kết quả
        $response = $this->getHttpResponse($this->mapi_url, http_build_query($param));
        $arr = json_decode($response, true);
        return $arr;
    }

    // Hàm xác minh callback bất đồng bộ (notify) từ hệ thống thanh toán
    public function verifyNotify(){
        if(empty($_GET)) return false;

        // Tính lại chữ ký từ dữ liệu nhận được
        $sign = $this->getSign($_GET);

        if($sign === $_GET['sign']){
            $signResult = true;
        }else{
            $signResult = false;
        }

        return $signResult;
    }

    // Hàm xác minh callback đồng bộ (return) từ hệ thống thanh toán
    public function verifyReturn(){
        if(empty($_GET)) return false;

        // Tính lại chữ ký từ dữ liệu nhận được
        $sign = $this->getSign($_GET);

        if($sign === $_GET['sign']){
            $signResult = true;
        }else{
            $signResult = false;
        }

        return $signResult;
    }

    // Hàm kiểm tra trạng thái thanh toán của đơn hàng
    public function orderStatus($trade_no){
        $result = $this->queryOrder($trade_no);
        if($result['status']==1){
            return true;
        }else{
            return false;
        }
    }

    // Hàm truy vấn thông tin đơn hàng
    public function queryOrder($trade_no){
        // Gửi yêu cầu GET với các tham số: act=order, pid, key, trade_no
        $url = $this->api_url.'?act=order&pid=' . $this->pid . '&key=' . $this->key . '&trade_no=' . $trade_no;
        $response = $this->getHttpResponse($url);
        $arr = json_decode($response, true);
        return $arr;
    }

    // Hàm yêu cầu hoàn trả đơn hàng (refund)
    public function refund($trade_no, $money){
        $url = $this->api_url.'?act=refund';
        // Xây dựng dữ liệu POST cho refund
        $post = 'pid=' . $this->pid . '&key=' . $this->key . '&trade_no=' . $trade_no . '&money=' . $money;
        $response = $this->getHttpResponse($url, $post);
        $arr = json_decode($response, true);
        return $arr;
    }

    // Hàm xây dựng tham số yêu cầu (thêm chữ ký và loại chữ ký)
    private function buildRequestParam($param){
        $mysign = $this->getSign($param);
        $param['sign'] = $mysign;
        $param['sign_type'] = $this->sign_type;
        return $param;
    }

    // Hàm tính chữ ký MD5
    // Quy tắc: sắp xếp tham số theo thứ tự tăng dần theo key (ASCII), bỏ qua "sign" và "sign_type" và những giá trị rỗng
    // Nối thành chuỗi theo dạng: key1=value1&key2=value2&... rồi nối thêm khóa bí mật
    // Sau đó áp dụng hàm md5 và trả về kết quả
    private function getSign($param){
        ksort($param);
        reset($param);
        $signstr = '';
    
        foreach($param as $k => $v){
            if($k != "sign" && $k != "sign_type" && $v != ''){
                $signstr .= $k.'='.$v.'&';
            }
        }
        // Loại bỏ ký tự & cuối cùng
        $signstr = substr($signstr,0,-1);
        // Nối khóa bí mật vào cuối chuỗi (lưu ý: không có dấu & giữa chuỗi và khóa)
        $signstr .= $this->key;
        $sign = md5($signstr);
        return $sign;
    }

    // Hàm gửi yêu cầu HTTP (dùng cURL) để lấy dữ liệu từ URL bên ngoài
    // Nếu $post được truyền vào, gửi dữ liệu POST
    private function getHttpResponse($url, $post = false, $timeout = 10){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept: */*";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
        $httpheader[] = "Connection: close";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($post){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
