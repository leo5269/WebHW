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

$action = '查看留言板';
$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $action);
$stmt->execute();

// 檢查是否發送了 POST 請求以添加新文章
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['content'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];

    // 獲取當前用戶的 ID
    $stmt = $conn->prepare("SELECT id FROM login_data WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // 插入新的文章
    $stmt = $conn->prepare("INSERT INTO posts (title, content, user_id) VALUES (?, ?, ?)");
    if ($stmt === false) {
        die("SQL 語句準備失敗: " . $conn->error);
    }

    $stmt->bind_param("ssi", $title, $content, $user_id);
    if ($stmt->execute()) {
        echo "<script>alert('文章已成功發布！');</script>";
	$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
        $action = '發布文章';
        $stmt->bind_param("ss", $username, $action);
        $stmt->execute();
    } else {
        echo "<script>alert('文章發布失敗: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// 獲取所有文章
$sql = "SELECT posts.id, posts.title, posts.content, posts.created_at, login_data.username FROM posts 
        JOIN login_data ON posts.user_id = login_data.id ORDER BY posts.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>留言板</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        .navbar {
            background-color: #ffffff;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-nav {
            flex-direction: row; 
            gap: 5px; 
        }
        .navbar-nav a {
            color: #333;
            text-decoration: none;
            padding: 5px 10px; 
        }
        .navbar-nav a:hover {
            text-decoration: underline;
        }
        .right-nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .welcome-message {
            color: #333;
            margin-right: 10px; 
        }
        .post-form {
            margin-bottom: 30px;
        }
        .post-container {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .post-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .post-content {
            margin-bottom: 10px;
        }
        .post-meta {
            color: #777;
            font-size: 0.9em;
        }
        .post-actions {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-nav">
        <a href="Member_list.php">會員列表</a>
        <a href="modify_member.php">修改個人資料</a>
        <a href="file_management.php">檔案總管</a>
        <a href="message_board.php">留言板</a>
        <a href="orders.php">訂閱</a>
        <?php if ($username == 'admin'): ?>
        <a href="admin_log.php">會員管理</a>
        <?php endif; ?>
    </div>
    <div class="right-nav">
        <span class="welcome-message"><?php echo htmlspecialchars($username); ?>，您好!</span>
        <a href="logout.php" class="btn btn-link text-dark">登出</a>
    </div>
</nav>

<div class="container mt-4">
    <h2>發布留言</h2>
    <form action="message_board.php" method="post" class="post-form">
        <div class="mb-3">
            <label for="title" class="form-label">標題</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        <div class="mb-3">
            <label for="content" class="form-label">內容</label>
            <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">發布</button>
    </form>

    <h2>留言板</h2>
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="post-container">
                <div class="post-title"><?php echo htmlspecialchars($row['title']); ?></div>
                <div class="post-content"><?php echo nl2br(htmlspecialchars($row['content'])); ?></div>
                <div class="post-meta"><?php echo htmlspecialchars($row['username']); ?> <?php echo htmlspecialchars($row['created_at']); ?></div>
                <div class="post-actions">
                    <a href="view_replies.php?post_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">查看回復</a>
                    <?php if ($row['username'] == $username): ?>
                        <a href="edit_post.php?post_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">修改留言</a>
                        <a href="delete_post.php?post_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('確定要刪除此留言嗎？');">刪除留言</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>暫無文章。</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"  crossorigin="anonymous"></script>

</body>
</html>

<?php
$conn->close();
?>
