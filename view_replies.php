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

// 獲取文章 ID
$post_id = $_GET['post_id'];

// 獲取文章資料
$stmt = $conn->prepare("SELECT posts.id, posts.title, posts.content, posts.created_at, login_data.username FROM posts JOIN login_data ON posts.user_id = login_data.id WHERE posts.id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$stmt->bind_result($post_id, $post_title, $post_content, $post_created_at, $post_author);
$stmt->fetch();
$stmt->close();

// 檢查是否發送了 POST 請求以添加新回復
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content'])) {
    $reply_content = $_POST['reply_content'];

    // 獲取當前用戶的 ID
    $stmt = $conn->prepare("SELECT id FROM login_data WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // 插入新的回復
    $stmt = $conn->prepare("INSERT INTO replies (content, post_id, user_id) VALUES (?, ?, ?)");
    if ($stmt === false) {
        die("SQL 語句準備失敗: " . $conn->error);
    }

    $stmt->bind_param("sii", $reply_content, $post_id, $user_id);
    if ($stmt->execute()) {
        echo "<script>alert('回復已成功發布！');</script>";
    } else {
        echo "<script>alert('回復發布失敗: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// 獲取所有回復
$sql = "SELECT replies.id, replies.content, replies.created_at, login_data.username FROM replies 
        JOIN login_data ON replies.user_id = login_data.id WHERE replies.post_id = ? ORDER BY replies.created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看回復</title>
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
        .post-container, .reply-container {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .post-title, .reply-content {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .post-content, .reply-content {
            margin-bottom: 10px;
        }
        .post-meta, .reply-meta {
            color: #777;
            font-size: 0.9em;
        }
        .reply-actions {
            margin-top: 10px;
        }
        .edit-form {
            display: none;
            margin-top: 10px;
        }
    </style>
    <script>
        function toggleEditForm(replyId) {
            var editForm = document.getElementById('edit-form-' + replyId);
            if (editForm.style.display === 'none') {
                editForm.style.display = 'block';
            } else {
                editForm.style.display = 'none';
            }
        }
    </script>
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
    <h2>查看回復</h2>
    
    <!-- 顯示文章 -->
    <div class="post-container">
        <div class="post-title"><?php echo htmlspecialchars($post_title); ?></div>
        <div class="post-content"><?php echo nl2br(htmlspecialchars($post_content)); ?></div>
        <div class="post-meta"><?php echo htmlspecialchars($post_author); ?> <?php echo htmlspecialchars($post_created_at); ?></div>
        <?php if ($post_author == $username): ?>
            <div class="reply-actions">
                <a href="edit_post.php?post_id=<?php echo $post_id; ?>" class="btn btn-warning btn-sm">修改留言</a>
                <a href="delete_post.php?post_id=<?php echo $post_id; ?>" class="btn btn-danger btn-sm">刪除留言</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- 顯示所有回復 -->
    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="reply-container">
                <div class="reply-content"><?php echo nl2br(htmlspecialchars($row['content'])); ?></div>
                <div class="reply-meta"><?php echo htmlspecialchars($row['username']); ?> <?php echo htmlspecialchars($row['created_at']); ?></div>
                <div class="reply-actions">
                    <?php if ($row['username'] == $username): ?>
                        <button class="btn btn-sm btn-warning" onclick="toggleEditForm(<?php echo $row['id']; ?>)">修改留言</button>
                        <a href="delete_reply.php?reply_id=<?php echo $row['id']; ?>&post_id=<?php echo $post_id; ?>" class="btn btn-sm btn-danger">刪除留言</a>
                    <?php endif; ?>
                </div>
                <form action="view_replies.php?post_id=<?php echo $post_id; ?>" method="post" class="edit-form" id="edit-form-<?php echo $row['id']; ?>">
                    <input type="hidden" name="reply_id" value="<?php echo $row['id']; ?>">
                    <textarea name="edit_content" class="form-control" rows="2"><?php echo htmlspecialchars($row['content']); ?></textarea>
                    <button type="submit" class="btn btn-primary mt-2">修改</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>暫無回復。</p>
    <?php endif; ?>

    <!-- 添加新回復 -->
    <form action="view_replies.php?post_id=<?php echo $post_id; ?>" method="post" class="mt-4">
        <div class="mb-3">
            <label for="reply_content" class="form-label">請輸入內容</label>
            <textarea class="form-control" id="reply_content" name="reply_content" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">回復</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

</body>
</html>

<?php
// 處理修改回復的請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_content'])) {
    $edit_content = $_POST['edit_content'];
    $reply_id = $_POST['reply_id'];

    // 更新回復內容
    $stmt = $conn->prepare("UPDATE replies SET content = ? WHERE id = ? AND user_id = (SELECT id FROM login_data WHERE username = ?)");
    $stmt->bind_param("sis", $edit_content, $reply_id, $username);
    if ($stmt->execute()) {
        echo "<script>alert('回復已成功修改！'); window.location.href = 'view_replies.php?post_id=" . $post_id . "';</script>";
    } else {
        echo "<script>alert('回復修改失敗: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

$conn->close();
?>
