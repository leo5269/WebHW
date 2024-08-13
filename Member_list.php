<?php
session_start();

// 如果用戶未登入，重定向到登入頁面
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

// 獲取會員列表
$sql = "SELECT username, email, gender, color FROM login_data";
$result = $conn->query($sql);
$action = '查看會員列表';
$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $action);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>會員列表</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar {
            background-color: #fff;
            padding: 10px;
        }
        .navbar a {
            color: #333;
            margin-right: 15px;
            text-decoration: none;
        }
        .navbar a:hover {
            text-decoration: underline;
        }
        .welcome-message {
            color: #333;
            float: right;
        }
        .table-container {
            margin-top: 0px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
            background-color: #333;
        }
        table {
            width: 100%;
            margin-bottom: 20px;
            color: #fff; /* 將表格文字顏色設為白色 */
        }
        th, td {
            padding: 12px;
            text-align: left;
            font-size: 16px;
            background-color: #333; /* 背景色為深色 */
        }
        th {
            background-color: #444;
        }
        tbody tr:hover {
            background-color: #555;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="Member_list.php">會員列表</a>
    <a href="modify_member.php">修改個人資料</a>
    <a href="file_management.php">檔案總管</a>
    <a href="message_board.php">留言板</a>
    <a href="orders.php">訂閱</a>
    <?php if ($username == 'admin'): ?>
        <a href="admin_log.php">會員管理</a>
    <?php endif; ?>
    <span class="welcome-message"><?php echo htmlspecialchars($username); ?>，您好! <a href="logout.php" class="btn btn-link text-light">登出</a></span>
</nav>

<div class="table-container">
    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>會員帳號</th>
                <th>會員信箱</th>
                <th>會員性別</th>
                <th>會員喜好顏色</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                        <td style="background-color:<?php echo htmlspecialchars($row['color']); ?>;"></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">暫無會員資料</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>
