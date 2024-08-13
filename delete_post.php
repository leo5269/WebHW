<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: WebHW.html");
    exit();
}

$username = $_SESSION['username'];
$post_id = $_GET['post_id'];

// 連接到 MySQL 資料庫
$conn = new mysqli("localhost", "root", "", "weblogin");

// 檢查連接
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 刪除文章
$stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = (SELECT id FROM login_data WHERE username = ?)");
$stmt->bind_param("is", $post_id, $username);

if ($stmt->execute()) {
    echo "<script>alert('文章已成功刪除！'); window.location.href='message_board.php';</script>";
	$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
        $action = '刪除留言';
        $stmt->bind_param("ss", $username, $action);
        $stmt->execute();		
} else {
    echo "<script>alert('文章刪除失敗: " . $stmt->error . "');</script>";
}

$stmt->close();
$conn->close();
?>
