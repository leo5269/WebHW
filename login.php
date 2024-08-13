<?php
session_start();

// 連接到 MySQL 資料庫
$conn = new mysqli("localhost", "root", "", "weblogin");

// 檢查連接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 檢查表格是否提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 查詢用戶名和密碼
    $stmt = $conn->prepare("SELECT id, password FROM login_data WHERE username = ?");
    if (!$stmt) {
        die("SQL 錯誤: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // 檢查用戶名是否存在
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();
        
        // 驗證密碼
        if (password_verify($password, $hashed_password)) {
            // 將用戶名存在會話中
            $_SESSION['username'] = $username;

            // 插入操作到 user_logs 表
            $stmt_log = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
            $action = '進入首頁';
            $stmt_log->bind_param("ss", $username, $action);
            $stmt_log->execute();
            $stmt_log->close();
            
            // 重定向到 Member_list.php 页面
            header("Location: Member_list.php"); // 確保文件名正確且路徑正確匹配
            exit(); // 確保sql在重定向後停止執行
        } else {
            echo "<script>alert('密碼錯誤!');                    
		   setTimeout(function(){
                        window.location.href = 'WebHW.html';
                    }, 200); // 0.2秒後跳轉
	    </script>";
        }
    } else {
        echo "用戶名不存在。";
    }
    $stmt->close();
}

$conn->close();
?>
