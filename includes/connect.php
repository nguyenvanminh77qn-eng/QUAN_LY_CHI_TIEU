<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');


    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "quan_ly_chi_tieu";
    $port = 3306;

    try {
        // Cấu hình chuỗi DSN cho PDO
       $dsn = "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8";
        
        // Các tùy chọn bổ sung cho PDO
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Đẩy lỗi ra ngoại lệ để try...catch bắt được
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Mặc định trả về mảng kết hợp
        ];

        // Tạo kết nối
        $conn = new PDO($dsn, $username, $password, $options);

    } catch (Exception $e) {
        echo '<div style="color: red; border: 1px solid red; padding: 10px;">';
        echo "Lỗi kết nối Database: " . $e->getMessage();
        echo '</div>';
        die(); // Dừng chương trình nếu lỗi
    }
?>