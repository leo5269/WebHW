<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: WebHW.html");
    exit();
}

$username = $_SESSION['username'];

// 連接到 MySQL 資料庫
$conn = new mysqli("localhost", "root", "", "weblogin");

// 檢查連接
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 獲取回復 ID 和文章 ID
$reply_id = $_GET['reply_id'];
$post_id = $_GET['post_id'];

// 檢查該回復是否屬於當前用戶
$stmt = $conn->prepare("SELECT replies.id FROM replies JOIN login_data ON replies.user_id = login_data.id WHERE replies.id = ? AND login_data.username = ?");
$stmt->bind_param("is", $reply_id, $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // 刪除該回復
    $delete_stmt = $conn->prepare("DELETE FROM replies WHERE id = ?");
    $delete_stmt->bind_param("i", $reply_id);
    if ($delete_stmt->execute()) {
        echo "<script>alert('回復已成功刪除！');</script>";
    } else {
        echo "<script>alert('回復刪除失敗: " . $delete_stmt->error . "');</script>";
    }
    $delete_stmt->close();
} else {
    echo "<script>alert('您無權刪除此回復。');</script>";
}

$stmt->close();
$conn->close();

// 返回查看回復頁面
header("Location: view_replies.php?post_id=$post_id");
exit();
?>
