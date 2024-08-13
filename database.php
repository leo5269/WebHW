<?php
// 資料庫配置
$host = 'localhost';
$dbname = 'weblogin';
$user = 'root';
$pass = '';

// 創建資料庫連接
$conn = new mysqli($host, $user, $pass, $dbname);

// 檢查連接是否成功
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}
