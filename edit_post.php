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

// 獲取文章的現有內容
$stmt = $conn->prepare("SELECT title, content FROM posts WHERE id = ? AND user_id = (SELECT id FROM login_data WHERE username = ?)");
$stmt->bind_param("is", $post_id, $username);
$stmt->execute();
$stmt->bind_result($title, $content);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title = $_POST['title'];
    $new_content = $_POST['content'];

    // 更新文章
    $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ? AND user_id = (SELECT id FROM login_data WHERE username = ?)");
    $stmt->bind_param("ssis", $new_title, $new_content, $post_id, $username);
    
    if ($stmt->execute()) {
        echo "<script>alert('文章已成功更新！'); window.location.href='message_board.php';</script>";
	$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
        $action = '修改留言';
        $stmt->bind_param("ss", $username, $action);
        $stmt->execute();
    } else {
        echo "<script>alert('文章更新失敗: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改留言</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>

<div class="container mt-4">
    <h2>修改留言</h2>
    <form action="edit_post.php?post_id=<?php echo $post_id; ?>" method="post">
        <div class="mb-3">
            <label for="title" class="form-label">標題</label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
        </div>
        <div class="mb-3">
            <label for="content" class="form-label">內容</label>
            <textarea class="form-control" id="content" name="content" rows="4" required><?php echo htmlspecialchars($content); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">修改</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"  crossorigin="anonymous"></script>

</body>
</html>

<?php
$conn->close();
?>
