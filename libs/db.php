<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once(__DIR__ . '/session.php');

include_once(__DIR__.'/../vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();
 

class DB
{
    private $ketnoi;
    public $debug;

    public function __construct()
    {
        $this->debug = isset($_ENV['DEBUG']) ? (strtolower((string)$_ENV['DEBUG']) === 'true' || (string)$_ENV['DEBUG'] === '1') : false;
    }



    private function isTransientConnectError($errno)
    {
        // Non-retryable: sai thông tin cấu hình hoặc không tồn tại
        // 1045: Access denied (sai user/pass)
        // 1044: Access denied (sai quyền DB)
        // 1049: Unknown database (sai tên DB)
        // 2005: Unknown MySQL server host (sai host)
        $nonRetryableErrors = array(1045, 1044, 1049, 2005);
        return !in_array((int)$errno, $nonRetryableErrors, true);
    }

    private function handleError($sql)
    {
        $isConnected = ($this->ketnoi instanceof \mysqli);
        $message = $isConnected ? @mysqli_error($this->ketnoi) : @mysqli_connect_error();
        $code = $isConnected ? @mysqli_errno($this->ketnoi) : @mysqli_connect_errno();

        if ($this->debug == true) {
            $error = array(
                'message' => $message,
                'code' => $code,
                'file' => isset(debug_backtrace()[1]['file']) ? debug_backtrace()[1]['file'] : __FILE__,
                'line' => isset(debug_backtrace()[1]['line']) ? debug_backtrace()[1]['line'] : __LINE__,
                'query' => $sql,
                'time' => date('Y-m-d H:i:s')
            );

            echo '<div style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); font-family: monospace;">';
            echo '<h3 style="color: #dc3545; margin-bottom: 15px;">Database Error</h3>';
            echo '<div style="margin-bottom: 10px;"><strong>Message:</strong> ' . htmlspecialchars((string)$error['message']) . '</div>';
            echo '<div style="margin-bottom: 10px;"><strong>Error Code:</strong> ' . htmlspecialchars((string)$error['code']) . '</div>';
            echo '<div style="margin-bottom: 10px;"><strong>File:</strong> ' . htmlspecialchars((string)$error['file']) . '</div>';
            echo '<div style="margin-bottom: 10px;"><strong>Line:</strong> ' . htmlspecialchars((string)$error['line']) . '</div>';
            echo '<div style="margin-bottom: 10px;"><strong>Query:</strong> ' . htmlspecialchars((string)$error['query']) . '</div>';
            echo '<div style="margin-bottom: 10px;"><strong>Time:</strong> ' . $error['time'] . '</div>';
            echo '</div>';
            return false;
        } else {
            // echo '<div style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); font-family: monospace;">';
            // echo '<h3 style="color: #dc3545; margin-bottom: 15px;">Database Error</h3>';
            // echo '<div style="margin-bottom: 10px;">Không thể kết nối đến máy chủ hoặc đang quá tải, vui lòng thử lại sau.</div>';
            // echo '</div>';
            return false;
        }
    }

    public function connect()
    {
        if ($this->ketnoi) {
            return;
        }

        $startTime = microtime(true);
        $lastError = '';

        do {
            $this->ketnoi = @mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_DATABASE']);
            if ($this->ketnoi) {
                mysqli_set_charset($this->ketnoi, 'utf8mb4');
                @mysqli_query($this->ketnoi, "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
                return;
            }
            $errno = @mysqli_connect_errno();
            $lastError = '['.$errno.'] '.@mysqli_connect_error();
            if (!$this->isTransientConnectError($errno)) {
                $this->handleError('Unable to connect to Database: ' . $lastError);
                exit;
            }
            usleep(200000);
        } while ((microtime(true) - $startTime) < 6);

        $this->handleError('Unable to connect to Database: ' . $lastError);
        exit;
    }
    public function dis_connect()
    {
        if ($this->ketnoi) {
            mysqli_close($this->ketnoi);
        }
    }
    public function site($data)
    {
        $this->connect();
        $sql = "SELECT * FROM `settings` WHERE `name` = '$data'";
        $result = $this->ketnoi->query($sql);
        if (!$result) {
            $this->handleError($sql);
            return false;
        }
        $row = $result->fetch_array();
        if (!$row) {
            $this->handleError("Không tìm thấy setting với name = '$data'");
            return false;
        }
        return $row['value'];
    }
    public function query($sql)
    {
        $this->connect();
        $row = $this->ketnoi->query($sql);
        if (!$row) {
            $this->handleError($sql);
        }
        return $row;
    }
    public function cong($table, $data, $sotien, $where, $where_params = [])
    {
        $this->connect();
        // Xác thực tên bảng/cột để tránh injection vào identifier
        if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$table) || !preg_match('/^[a-zA-Z0-9_]+$/', (string)$data)) {
            $this->handleError('Invalid table/column name');
            return false;
        }
        if (!is_numeric($sotien)) {
            $this->handleError('Invalid amount for cong');
            return false;
        }
        $amount = (float)$sotien;

        $sql = "UPDATE `$table` SET `$data` = `$data` + ? WHERE $where";
        $stmt = $this->ketnoi->prepare($sql);
        if (!$stmt) {
            $this->handleError("Prepare failed: " . $this->ketnoi->error);
            return false;
        }

        // Chuẩn bị types và tham số bind: bắt đầu với 'd' cho số tiền
        $types = 'd';
        $bind_values = [$amount];
        if (!empty($where_params)) {
            foreach ($where_params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bind_values[] = $param;
            }
        }

        $stmt->bind_param($types, ...$bind_values);

        $result = $stmt->execute();
        if (!$result) {
            $this->handleError("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        $stmt->close();
        return $result;
    }
    public function tru($table, $data, $sotien, $where, $where_params = [])
    {
        $this->connect();
        // Xác thực tên bảng/cột để tránh injection vào identifier
        if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$table) || !preg_match('/^[a-zA-Z0-9_]+$/', (string)$data)) {
            $this->handleError('Invalid table/column name');
            return false;
        }
        if (!is_numeric($sotien)) {
            $this->handleError('Invalid amount for tru');
            return false;
        }
        $amount = (float)$sotien;

        $sql = "UPDATE `$table` SET `$data` = `$data` - ? WHERE $where";
        $stmt = $this->ketnoi->prepare($sql);
        if (!$stmt) {
            $this->handleError("Prepare failed: " . $this->ketnoi->error);
            return false;
        }

        // Chuẩn bị types và tham số bind: bắt đầu với 'd' cho số tiền
        $types = 'd';
        $bind_values = [$amount];
        if (!empty($where_params)) {
            foreach ($where_params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bind_values[] = $param;
            }
        }

        $stmt->bind_param($types, ...$bind_values);

        $result = $stmt->execute();
        if (!$result) {
            $this->handleError("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        $stmt->close();
        return $result;
    }
    public function insert($table, $data)
    {
        $this->connect();
        
        // Tạo danh sách field và placeholder
        $fields = array_keys($data);
        $field_list = '`' . implode('`, `', $fields) . '`';
        $placeholders = str_repeat('?,', count($data));
        $placeholders = rtrim($placeholders, ',');
        
        $sql = "INSERT INTO `$table` ($field_list) VALUES ($placeholders)";
        
        $stmt = $this->ketnoi->prepare($sql);
        if (!$stmt) {
            $this->handleError("Prepare failed: " . $this->ketnoi->error);
            return false;
        }
        
        // Tạo types string và values array
        $types = '';
        $values = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        $stmt->bind_param($types, ...$values);
        
        if (!$stmt->execute()) {
            $this->handleError("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $insert_id = $this->ketnoi->insert_id;
        $stmt->close();
        
        // Trả về ID của bản ghi vừa thêm
        return $insert_id;
    }
    public function update($table, $data, $where, $where_params = [])
    {
        $this->connect();
        
        // Nếu $where_params không được cung cấp, sử dụng cách cũ (không an toàn)
        if (empty($where_params)) {
            // Cách cũ - giữ tương thích ngược nhưng vẫn dùng prepared statements cho SET clause
            $set_parts = [];
            $set_values = [];
            $types = '';
            
            foreach ($data as $key => $value) {
                $set_parts[] = "`$key` = ?";
                $set_values[] = $value;
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            $sql = "UPDATE `$table` SET " . implode(', ', $set_parts) . " WHERE $where";
            
            $stmt = $this->ketnoi->prepare($sql);
            if (!$stmt) {
                $this->handleError("Prepare failed: " . $this->ketnoi->error);
                return false;
            }
            
            if (!empty($set_values)) {
                $stmt->bind_param($types, ...$set_values);
            }
            
            $result = $stmt->execute();
            if (!$result) {
                $this->handleError("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            // Kiểm tra xem có lỗi nào sau khi execute không (ví dụ: duplicate key, constraint violation, etc.)
            if ($stmt->error) {
                $this->handleError("Execute error: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            return $affected_rows > 0;
        } else {
            // Cách mới - an toàn hoàn toàn
            $set_parts = [];
            $set_values = [];
            $types = '';
            
            foreach ($data as $key => $value) {
                $set_parts[] = "`$key` = ?";
                $set_values[] = $value;
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            // Thêm types cho where parameters
            foreach ($where_params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            $sql = "UPDATE `$table` SET " . implode(', ', $set_parts) . " WHERE $where";
            
            $stmt = $this->ketnoi->prepare($sql);
            if (!$stmt) {
                $this->handleError("Prepare failed: " . $this->ketnoi->error);
                return false;
            }
            
            $all_values = array_merge($set_values, $where_params);
            if (!empty($all_values)) {
                $stmt->bind_param($types, ...$all_values);
            }
            
            $result = $stmt->execute();
            if (!$result) {
                $this->handleError("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            // Kiểm tra xem có lỗi nào sau khi execute không (ví dụ: duplicate key, constraint violation, etc.)
            if ($stmt->error) {
                $this->handleError("Execute error: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            return $affected_rows > 0;
        }
    }
    public function update_value($table, $data, $where, $value1)
    {
        $this->connect();
        $sql = '';
        foreach ($data as $key => $value) {
            $sql .= "$key = '".mysqli_real_escape_string($this->ketnoi, $value)."',";
        }
        $sql = 'UPDATE '.$table. ' SET '.trim($sql, ',').' WHERE '.$where.' LIMIT '.$value1;
        $result = mysqli_query($this->ketnoi, $sql);
        if (!$result) {
            $this->handleError($sql);
        }
        return $result;
    }
    public function remove($table, $where, $where_params = [])
    {
        $this->connect();
        
        // Nếu $where_params không được cung cấp, sử dụng cách cũ (không an toàn)
        if (empty($where_params)) {
            $sql = "DELETE FROM `$table` WHERE $where";
            $result = mysqli_query($this->ketnoi, $sql);
            if (!$result) {
                $this->handleError($sql);
                return false;
            }
            return $result;
        } else {
            // Cách mới - an toàn
            $sql = "DELETE FROM `$table` WHERE $where";
            
            $stmt = $this->ketnoi->prepare($sql);
            if (!$stmt) {
                $this->handleError("Prepare failed: " . $this->ketnoi->error);
                return false;
            }
            
            // Tạo types string
            $types = '';
            foreach ($where_params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            $stmt->bind_param($types, ...$where_params);
            
            $result = $stmt->execute();
            if (!$result) {
                $this->handleError("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            // Kiểm tra xem có lỗi nào sau khi execute không (ví dụ: duplicate key, constraint violation, etc.)
            if ($stmt->error) {
                $this->handleError("Execute error: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            return $affected_rows > 0;
        }
    }
    public function get_list($sql)
    {
        $this->connect();
        $result = mysqli_query($this->ketnoi, $sql);
        if (!$result) {
            $this->handleError($sql);
        }
        $return = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $return[] = $row;
        }
        mysqli_free_result($result);
        return $return;
    }
    public function get_row($sql)
    {
        $this->connect();
        $result = mysqli_query($this->ketnoi, $sql);
        if (!$result) {
            $this->handleError($sql);
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        if ($row) {
            return $row;
        }
        return false;
    }
    public function num_rows($sql)
    {
        $this->connect();
        $result = mysqli_query($this->ketnoi, $sql);
        if (!$result) {
            $this->handleError($sql);
        }
        $row = mysqli_num_rows($result);
        mysqli_free_result($result);
        if ($row) {
            return $row;
        }
        return false;
    }
    
    // Các hàm mới hỗ trợ prepared statements - giữ tương thích ngược
    public function get_list_safe($sql, $params = [])
    {
        $this->connect();
        
        if (empty($params)) {
            // Nếu không có params, sử dụng cách cũ
            return $this->get_list($sql);
        }
        
        $stmt = $this->ketnoi->prepare($sql);
        if (!$stmt) {
            $this->handleError("Prepare failed: " . $this->ketnoi->error);
            return [];
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $this->handleError("Execute failed: " . $stmt->error);
            $stmt->close();
            return [];
        }
        
        // Sử dụng store_result() và fetch_assoc() thay vì get_result()
        $stmt->store_result();
        $meta = $stmt->result_metadata();
        $fields = $meta->fetch_fields();
        
        $return = array();
        $row = array();
        $bind_params = array();
        
        // Tạo mảng bind cho các cột
        foreach ($fields as $field) {
            $bind_params[] = &$row[$field->name];
        }
        call_user_func_array(array($stmt, 'bind_result'), $bind_params);
        
        while ($stmt->fetch()) {
            $row_copy = array();
            foreach ($row as $key => $value) {
                $row_copy[$key] = $value;
            }
            $return[] = $row_copy;
        }
        
        $meta->close();
        $stmt->close();
        return $return;
    }
    
    public function get_row_safe($sql, $params = [])
    {
        $this->connect();
        
        if (empty($params)) {
            // Nếu không có params, sử dụng cách cũ
            return $this->get_row($sql);
        }
        
        $stmt = $this->ketnoi->prepare($sql);
        if (!$stmt) {
            $this->handleError("Prepare failed: " . $this->ketnoi->error);
            return false;
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $this->handleError("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        // Sử dụng store_result() và fetch_assoc() thay vì get_result()
        $stmt->store_result();
        $meta = $stmt->result_metadata();
        $fields = $meta->fetch_fields();
        
        $row = array();
        $bind_params = array();
        
        // Tạo mảng bind cho các cột
        foreach ($fields as $field) {
            $bind_params[] = &$row[$field->name];
        }
        call_user_func_array(array($stmt, 'bind_result'), $bind_params);
        
        $result = $stmt->fetch();
        $meta->close();
        $stmt->close();
        
        if ($result) {
            return $row;
        }
        return false;
    }
    
    public function num_rows_safe($sql, $params = [])
    {
        $this->connect();
        
        if (empty($params)) {
            // Nếu không có params, sử dụng cách cũ
            return $this->num_rows($sql);
        }
        
        $stmt = $this->ketnoi->prepare($sql);
        if (!$stmt) {
            $this->handleError("Prepare failed: " . $this->ketnoi->error);
            return 0;
        }
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            $this->handleError("Execute failed: " . $stmt->error);
            $stmt->close();
            return 0;
        }
        
        // Sử dụng store_result() để đếm số hàng thay vì get_result()
        $stmt->store_result();
        $count = $stmt->num_rows;
        $stmt->close();
        return $count;
    }
}