<?php
session_start();

// 確保只有admin可以訪問
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
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

// 搜尋會員名稱的關鍵字
$search_keyword = isset($_GET['search']) ? $_GET['search'] : '';

// 查詢操作紀錄
if (empty($search_keyword)) {
    // 當無關鍵字時，顯示admin自己的紀錄
    $stmt = $conn->prepare("SELECT * FROM user_logs WHERE username = ?");
    $stmt->bind_param("s", $username);
} else {
    // 當有關鍵字時，搜尋所有符合條件的紀錄
    $search_keyword = "%" . $search_keyword . "%";
    $stmt = $conn->prepare("SELECT * FROM user_logs WHERE username LIKE ?");
    $stmt->bind_param("s", $search_keyword);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>會員管理</title>
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
        .search-container {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .log-table {
            width: 100%;
        }
        .log-table th, .log-table td {
            padding: 10px;
            text-align: left;
        }
        .log-table th {
            background-color: #f8f8f8;
        }
        .log-table tbody tr:nth-child(odd) {
            background-color: #f2f2f2;
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
        <a href="admin_log.php">會員管理</a>
    </div>
    <div class="right-nav">
        <span class="welcome-message"><?php echo htmlspecialchars($username); ?>，您好!</span>
        <a href="logout.php" class="btn btn-link text-dark">登出</a>
    </div>
</nav>

<div class="container">
    <div class="search-container">
        <form action="admin_log.php" method="get" class="d-flex align-items-center">
            <button type="submit" class="btn btn-primary me-2">搜尋會員紀錄</button>
            <input type="text" class="form-control" style="width: 1100px;" name="search" placeholder="請輸入會員名稱" value="<?php echo htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
        </form>
    </div>

    <table class="log-table table table-striped">
        <thead>
            <tr>
                <th>會員名稱</th>
                <th>執行時間</th>
                <th>紀錄</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                        <td><?php echo htmlspecialchars($row['action']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">暫無紀錄。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
