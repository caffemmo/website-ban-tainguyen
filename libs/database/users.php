<?php


if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if (!class_exists('SecurityValidator')) {
    require_once dirname(__DIR__) . '/session.php';
}

use Detection\MobileDetect;

class users extends DB
{
    protected $_table_name = 'users';
    protected $_key = 'id';
    public function __construct()
    {
        parent::connect();
    }
    public function __destruct()
    {
        parent::dis_connect();
    }
    public function add_new($data)
    {
        return parent::insert($this->_table_name, $data);
    }
    public function delete_by_id($id)
    {
        return $this->remove($this->_table_name, $this->_key.'='.(int)$id);
    }
    public function update_by_id($data, $id)
    {
        return $this->update($this->_table_name, $data, $this->_key."=".(int)$id);
    }
    public function select_by_id($select, $id)
    {
        $sql = "SELECT $select FROM ".$this->_table_name." WHERE ".$this->_key." = ".(int)$id;
        return $this->get_row($sql);
    }
    public function get_row_by_id($where)
    {
        $sql = "SELECT * FROM ".$this->_table_name." WHERE ".$where;
        return $this->get_row($sql);
    }
    public function get_list_by_id($where)
    {
        $sql = "SELECT * FROM ".$this->_table_name." WHERE ".$where;
        return $this->get_list($sql);
    }
    public function num_rows_by_id($where)
    {
        $sql = "SELECT * FROM ".$this->_table_name." WHERE ".$where;
        return $this->num_rows($sql);
    }
    public function AddCredits($user_id, $amount, $reason, $transid = NULL){
        if($transid == NULL){
            $transid = uniqid().'_'.mt_rand(0, 9999999);
        }
        
        // Bắt đầu transaction để đảm bảo tính nhất quán
        parent::query("START TRANSACTION");
        
        try {
            // Tạo log giao dịch
            $isInsert = parent::insert("dongtien", array(
                'sotientruoc' => getUser($user_id, 'money'),
                'sotienthaydoi' => $amount,
                'sotiensau' => getUser($user_id, 'money') + $amount,
                'thoigian' => gettime(),
                'noidung' => $reason,
                'user_id'   => $user_id,
                'transid'   => $transid
            ));
            
            if($isInsert){
                // Cộng tiền cho user
                $isUpdate = parent::cong("users", "money", $amount, " `id` = '$user_id' ");
                
                if($isUpdate) {
                    // Cập nhật tổng tiền nạp
                    $isUpdateTotal = parent::cong("users", "total_money", $amount, " `id` = '$user_id' ");
                    
                    if($isUpdateTotal) {
                        // Commit transaction nếu mọi thứ thành công
                        parent::query("COMMIT");
                        return true;
                    } else {
                        // Rollback nếu không thể cập nhật tổng tiền nạp
                        parent::query("ROLLBACK");
                        return false;
                    }
                } else {
                    // Rollback nếu không thể cộng tiền
                    parent::query("ROLLBACK");
                    return false;
                }
            } else {
                // Rollback nếu không thể tạo log
                parent::query("ROLLBACK");
                return false;
            }
        } catch (Exception $e) {
            // Rollback nếu có bất kỳ lỗi nào xảy ra
            parent::query("ROLLBACK");
            return false;
        }
    }
    public function RefundCredits($user_id, $amount, $reason, $transid = NULL){
        if($transid == NULL){
            $transid = uniqid().'_'.mt_rand(0, 9999999);
        }
        
        // Bắt đầu transaction để đảm bảo tính nhất quán
        parent::query("START TRANSACTION");
        
        try {
            // Tạo log giao dịch
            $isInsert = parent::insert("dongtien", array(
                'sotientruoc' => getUser($user_id, 'money'),
                'sotienthaydoi' => $amount,
                'sotiensau' => getUser($user_id, 'money') + $amount,
                'thoigian' => gettime(),
                'noidung' => $reason,
                'user_id'   => $user_id,
                'transid'   => $transid
            ));
            
            if($isInsert){
                // Cộng tiền cho user
                $isUpdate = parent::cong("users", "money", $amount, " `id` = '$user_id' ");
                
                if ($isUpdate) {
                    // Commit transaction nếu mọi thứ thành công
                    parent::query("COMMIT");
                    return true;
                } else {
                    // Rollback nếu không thể cộng tiền
                    parent::query("ROLLBACK");
                    return false;
                }
            } else {
                // Rollback nếu không thể tạo log
                parent::query("ROLLBACK");
                return false;
            }
        } catch (Exception $e) {
            // Rollback nếu có bất kỳ lỗi nào xảy ra
            parent::query("ROLLBACK");
            return false;
        }
    }
    public function RemoveCredits($user_id, $amount, $reason, $transid = NULL){
        if($transid == NULL){
            $transid = uniqid().'_'.mt_rand(0, 9999999);
        }
        
        // Bắt đầu transaction để đảm bảo tính nhất quán
        parent::query("START TRANSACTION");
        
        try {
            // Tạo log giao dịch
            $isInsert = parent::insert("dongtien", array(
                'sotientruoc' => getUser($user_id, 'money'),
                'sotienthaydoi' => $amount,
                'sotiensau' => getUser($user_id, 'money') - $amount,
                'thoigian'  => gettime(),
                'noidung'   => $reason,
                'user_id'   => $user_id,
                'transid'   => $transid
            ));
            
            if($isInsert){
                // Trừ tiền của user
                $isRemove = parent::tru("users", "money", $amount, " `id` = '$user_id' ");
                
                if ($isRemove) {
                    // Commit transaction nếu mọi thứ thành công
                    parent::query("COMMIT");
                    return true;
                } else {
                    // Rollback nếu không thể trừ tiền
                    parent::query("ROLLBACK");
                    return false;
                }
            } else {
                // Rollback nếu không thể tạo log
                parent::query("ROLLBACK");
                return false;
            }
        } catch (Exception $e) {
            // Rollback nếu có bất kỳ lỗi nào xảy ra
            parent::query("ROLLBACK");
            return false;
        }
    }
    public function Banned($user_id, $reason){
        parent::insert("logs", [
            'user_id'       => $user_id,
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Tài khoản bị khoá lý do ('.$reason.')'
        ]);
        parent::update("users", [
            'banned' => 1
        ], " `id` = '$user_id' ");
    }
    public function AddSpin($user_id, $amount, $reason){
        parent::insert("logs", [
            'user_id'       => $user_id,
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $reason
        ]);
        $isUpdate = parent::cong("users", "spin", $amount, " `id` = '$user_id' ");
        if ($isUpdate) {
            return true;
        }
        return false;
    }
    public function AddCommission($ref_id, $user_id, $amount, $reason){
        // ref_id = ID CTV nhận hoa hồng
        // user_id = ID thành viên nạp tiền
        parent::insert("aff_log", array(
            'sotientruoc' => getUser($ref_id, 'ref_price'),
            'sotienthaydoi' => $amount,
            'sotienhientai' => getUser($ref_id, 'ref_price') + $amount,
            'create_gettime' => gettime(),
            'reason' => $reason,
            'user_id' => $ref_id
        ));
        $isUpdate = parent::cong("users", "ref_price", $amount, " `id` = '$ref_id' ");
        if ($isUpdate) {
            parent::cong("users", "ref_total_price", $amount, " `id` = '$ref_id' ");
            parent::cong("users", "ref_amount", $amount, " `id` = '$user_id' ");
            return true;
        }
        return false;
    }
    public function RemoveCommission($user_id, $amount, $reason){
        parent::insert("aff_log", array(
            'sotientruoc' => getUser($user_id, 'ref_price'),
            'sotienthaydoi' => $amount,
            'sotienhientai' => getUser($user_id, 'ref_price') - $amount,
            'create_gettime' => gettime(),
            'reason' => $reason,
            'user_id' => $user_id
        ));
        $isRemove = parent::tru("users", "ref_price", $amount, " `id` = '$user_id' ");
        if ($isRemove) {
            return true;
        } else {
            return false;
        }
    }
    public function RefundCommission($user_id, $amount, $reason){
        parent::insert("aff_log", array(
            'sotientruoc' => getUser($user_id, 'ref_price'),
            'sotienthaydoi' => $amount,
            'sotienhientai' => getUser($user_id, 'ref_price') + $amount,
            'create_gettime' => gettime(),
            'reason' => $reason,
            'user_id' => $user_id
        ));
        $isUpdate = parent::cong("users", "ref_price", $amount, " `id` = '$user_id' ");
        if ($isUpdate) {
            return true;
        }
        return false;
    }
}
