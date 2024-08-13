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

$action = '查看訂閱';
$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $action);
$stmt->execute();

// 獲取當前用戶的 ID
$stmt = $conn->prepare("SELECT id FROM login_data WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

// 檢查用戶是否已訂閱
$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$isSubscribed = $result->num_rows > 0;
$stmt->close();

// 處理訂閱和取消訂閱的請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['subscribe'])) {
        // 訂閱操作
        $stmt = $conn->prepare("INSERT INTO subscriptions (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        $isSubscribed = true;
	$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
        $action = '訂閱成功';
        $stmt->bind_param("ss", $username, $action);
        $stmt->execute();
    } elseif (isset($_POST['unsubscribe'])) {
        // 取消訂閱操作
        $stmt = $conn->prepare("DELETE FROM subscriptions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        $isSubscribed = false;
	$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
        $action = '取消訂閱';
        $stmt->bind_param("ss", $username, $action);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂閱</title>
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
        .subscription-container {
            text-align: center;
            margin-top: 50px;
        }
        .carousel-item img {
            max-width: 60%; /* 調整圖片的最大寬度 */
            height: auto;  /* 保持圖片的縱橫比 */
            margin: 0 auto; /* 使圖片在輪播框中居中 */
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

<div class="container subscription-container">
    <form action="orders.php" method="post">
        <?php if ($isSubscribed): ?>
            <button type="submit" name="unsubscribe" class="btn btn-primary">🔔取消訂閱</button>
        <?php else: ?>
            <button type="submit" name="subscribe" class="btn btn-primary">🔔訂閱</button>
        <?php endif; ?>
    </form>

    <?php if ($isSubscribed): ?>
        <!-- 這裡開始是你訂閱後顯示的內容 -->
        <div id="carouselExampleIndicators" class="carousel slide mt-4" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <!-- 在這裡放置第一張圖片 -->
                    <img src="images/真的我寶寶.png" class="d-block w-100" alt="第一張圖片">
                </div>
                <div class="carousel-item">
                    <!-- 在這裡放置第二張圖片 -->
                    <img src="images/絕頂.png" class="d-block w-100" alt="第二張圖片">
                </div>
                <div class="carousel-item">
                    <!-- 在這裡放置第三張圖片 -->
                    <img src="images/受不鳥 好可愛.png" class="d-block w-100" alt="第三張圖片">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">上一張</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">下一張</span>
            </button>
        </div>
        <!-- 這裡結束是訂閱後顯示的內容 -->
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

</body>
</html>
