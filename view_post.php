// view_post.php
<?php
session_start();
require 'database.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// 獲取用戶的 ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];

// 獲取特定文章與回復
$post_id = $_GET['post_id'];
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT replies.*, users.username FROM replies JOIN users ON replies.user_id = users.id WHERE post_id = ? ORDER BY replies.created_at ASC");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$replies = $stmt->get_result();

// 處理回復發佈
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $reply_content = $_POST['content'];
    $stmt = $conn->prepare("INSERT INTO replies (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $reply_content);
    $stmt->execute();
    header("Location: view_post.php?post_id=$post_id");
    exit();
}

// 處理回復的刪除與修改
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reply'])) {
    $reply_id = $_POST['reply_id'];
    $stmt = $conn->prepare("DELETE FROM replies WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $reply_id, $user_id);
    $stmt->execute();
    header("Location: view_post.php?post_id=$post_id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_reply'])) {
    $reply_id = $_POST['reply_id'];
    $new_content = $_POST['new_content'];
    $stmt = $conn->prepare("UPDATE replies SET content = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $new_content, $reply_id, $user_id);
    $stmt->execute();
    header("Location: view_post.php?post_id=$post_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查看留言</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
    <p class="text-muted">由 <?php echo htmlspecialchars($post['user_id']); ?> 發佈於 <?php echo $post['created_at']; ?></p>
    <hr>

    <!-- 顯示回復列表 -->
    <h4>回覆</h4>
    <?php while ($reply = $replies->fetch_assoc()): ?>
        <div class="card mt-3">
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                <p class="text-muted">由 <?php echo htmlspecialchars($reply['username']); ?> 回復於 <?php echo $reply['created_at']; ?></p>
                <?php if ($reply['user_id'] == $user_id): ?>
                    <div>
                        <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="collapse" data-bs-target="#editReply<?php echo $reply['id']; ?>">修改回復</button>
                        <form method="POST" action="view_post.php?post_id=<?php echo $post_id; ?>" style="display:inline;">
                            <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                            <button type="submit" name="delete_reply" class="btn btn-outline-danger btn-sm">刪除回復</button>
                        </form>
                    </div>
                    <div class="collapse mt-3" id="editReply<?php echo $reply['id']; ?>">
                        <form method="POST" action="view_post.php?post_id=<?php echo $post_id; ?>">
                            <div class="mb-3">
                                <textarea class="form-control" name="new_content" rows="3" required><?php echo htmlspecialchars($reply['content']); ?></textarea>
                            </div>
                            <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                            <button type="submit" name="edit_reply" class="btn btn-outline-success btn-sm">修改</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>

    <!-- 發佈回復 -->
    <div class="mt-5">
        <h4>回復留言</h4>
        <form method="POST" action="view_post.php?post_id=<?php echo $post_id; ?>">
            <div class="mb-3">
                <textarea class="form-control" name="content" rows="3" required></textarea>
            </div>
            <button type="submit" name="reply" class="btn btn-primary">回復</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>