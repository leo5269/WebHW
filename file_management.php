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

$action = '查看檔案總管';
$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $action);
$stmt->execute();

// 設置正確的時區
date_default_timezone_set("Asia/Taipei");

// 處理下載文件記錄
if (isset($_POST['download_file'])) {
    $file_name = $_POST['file_name'];
    
    // 插入下載操作記錄
    $stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
    $action = '下載檔案';
    $stmt->bind_param("ss", $username, $action);
    $stmt->execute();
    
    // 重定向到文件下載
    header("Location: uploads/" . $file_name);
    exit();
}

// 處理文件上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploaded_file'])) {
    $target_dir = "uploads/";
    $file_name = basename($_FILES["uploaded_file"]["name"]);
    $target_file = $target_dir . $file_name;

    // 檢查文件是否已存在
    if (file_exists($target_file)) {
        if (isset($_POST['overwrite']) && $_POST['overwrite'] === 'yes') {
            if (move_uploaded_file($_FILES["uploaded_file"]["tmp_name"], $target_file)) {
                // 插入文件信息到資料庫
                $file_size = round($_FILES["uploaded_file"]["size"] / 1024, 2); // KB
                $upload_time = date("Y-m-d H:i:s");
                $stmt = $conn->prepare("INSERT INTO files (filename, size, upload_time, username) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siss", $file_name, $file_size, $upload_time, $username);
                $stmt->execute();
                $stmt->close();
                echo "<script>alert('檔案上傳成功！');</script>";
                $stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
                $action = '上傳檔案';
                $stmt->bind_param("ss", $username, $action);
                $stmt->execute();
            } else {
                echo "<script>alert('檔案上傳失敗！');</script>";
            }
        }
    } else {
        if (move_uploaded_file($_FILES["uploaded_file"]["tmp_name"], $target_file)) {
            // 插入文件信息到資料庫
            $file_size = round($_FILES["uploaded_file"]["size"] / 1024, 2); // KB
            $upload_time = date("Y-m-d H:i:s");
            $stmt = $conn->prepare("INSERT INTO files (filename, size, upload_time, username) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $file_name, $file_size, $upload_time, $username);
            $stmt->execute();
            $stmt->close();
            echo "<script>alert('檔案上傳成功！');</script>";
            $stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
            $action = '上傳檔案';
            $stmt->bind_param("ss", $username, $action);
            $stmt->execute();
        } else {
            echo "<script>alert('檔案上傳失敗！');</script>";
        }
    }
}

// 處理文件重命名
if (isset($_POST['rename_file']) && isset($_POST['new_name'])) {
    $old_name = $_POST['old_name'];
    $new_name = $_POST['new_name'];
    
    // 確保新檔案名稱合法，且不包含非法字符
    $new_name = basename($new_name);

    // 構建完整的舊檔案與新檔案路徑
    $old_path = "uploads/" . $old_name;
    $new_path = "uploads/" . $new_name;

    // 檢查新檔案名稱是否已存在
    if (file_exists($new_path)) {
        echo "<script>alert('檔案名稱已存在，請選擇其他名稱！');</script>";
    } else {
        // 執行檔案重命名
        if (rename($old_path, $new_path)) {
            // 更新資料庫中的檔案名稱
            $stmt = $conn->prepare("UPDATE files SET filename = ? WHERE filename = ?");
            $stmt->bind_param("ss", $new_name, $old_name);
            $stmt->execute();
            $stmt->close();
            echo "<script>alert('檔案重命名成功！');</script>";
            $stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
            $action = '修改檔名';
            $stmt->bind_param("ss", $username, $action);
            $stmt->execute();
        } else {
            echo "<script>alert('檔案重命名失敗！請確認檔案是否存在並重試。');</script>";
        }
    }
}

// 處理文件刪除
if (isset($_POST['delete_file'])) {
    $file_name = $_POST['file_name'];
    $file_path = "uploads/" . $file_name;

    if (unlink($file_path)) {
        $stmt = $conn->prepare("DELETE FROM files WHERE filename = ?");
        $stmt->bind_param("s", $file_name);
        $stmt->execute();
        $stmt->close();
        echo "<script>alert('檔案刪除成功！');</script>";
        $stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
        $action = '刪除檔案';
        $stmt->bind_param("ss", $username, $action);
        $stmt->execute();
    } else {
        echo "<script>alert('檔案刪除失敗！');</script>";
    }
}

// 獲取用戶的文件列表
$sql = "SELECT * FROM files WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>檔案總管</title>
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
            flex-direction: row; /* 使導航項目在同一行內顯示 */
            gap: 5px; 
        }
        .navbar-nav a {
            color: #333;
            text-decoration: none;
            padding: 5px 10px; /* 減小內部填充，使他們更緊湊 */
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
            margin-right: 10px; /* 使 welcome message 與登出按鈕分開 */
        }
        .file-table {
            width: 100%;
            margin-bottom: 20px;
            color: #fff;
            margin-top: 50px; /* 增加這行，將表格往下移 */
        }
        th, td {
            padding: 12px;
            text-align: left;
            font-size: 16px;
            background-color: #333;
        }
        th {
            background-color: #444;
        }
        tbody tr:hover {
            background-color: #555;
        }
        .upload-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .btn-large {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #fff; /* 白色背景 */
            color: #000; /* 黑色文字 */
            border: 1px solid #ccc; /* 邊框 */
        }

        .rename-form {
            display: inline-flex;
            align-items: center;
        }

        .rename-form input[type="text"] {
            margin-left: 10px;
            margin-right: 10px;
        }
	/* 這段CSS將在螢幕寬度小於768px時顯示三條線 */
@media (max-width: 768px) {
    .navbar-toggler {
        display: none;
    }
}

/* 這段CSS將在螢幕寬度大於等於768px時隱藏三條線 */
@media (min-width: 768px) {
    .navbar-toggler {
        display: flex;
    }
}

    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <!-- 三條線的按鈕 -->
        <button class="navbar-toggler" type="button" onclick="toggleNavbar()" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- 導覽列 -->
        <div class="navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="Member_list.php">會員列表</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="modify_member.php">修改個人資料</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="file_management.php">檔案總管</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="message_board.php">留言板</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders.php">訂閱</a>
                </li>
                <?php if ($username == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_log.php">會員管理</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <span class="navbar-text ms-auto order-lg-2">
            <?php echo htmlspecialchars($username); ?>，您好! 
            <a href="logout.php" class="btn btn-link text-dark">登出</a>
        </span>
    </div>
</nav>


<div class="upload-section container mt-4 d-flex align-items-center">
    <form id="uploadForm" action="file_management.php" method="post" enctype="multipart/form-data" class="w-100 d-flex align-items-center">
        <input type="hidden" name="overwrite" id="overwrite" value="no">
        <input type="file" id="uploaded_file" name="uploaded_file" class="form-control" style="width: 70%;" onchange="updateFileName(this)">
        <span id="file_name_display" class="ms-2" style="flex-grow: 1; visibility: hidden;"></span>
        <button type="submit" class="btn btn-large ms-3">📤</button>
    </form>
</div>

<div class="container mt-4">
    <table class="file-table table table-dark table-striped">
        <thead>
            <tr>
                <th>檔案名稱</th>
                <th>檔案大小 (KB)</th>
                <th>檔案上傳時間</th>
                <th>檔案功能</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['filename']); ?></td>
                        <td><?php echo htmlspecialchars($row['size']); ?> KB</td>
                        <td><?php echo htmlspecialchars($row['upload_time']); ?></td>
                        <td>
                            <form action="file_management.php" method="post" style="display:inline;">
                                <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($row['filename']); ?>">
                                <button type="submit" name="download_file" class="btn btn-large">下載</button>
                            </form>
                            <form action="file_management.php" method="post" style="display:inline;">
                                <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($row['filename']); ?>">
                                <button type="submit" name="delete_file" class="btn btn-large">刪除</button>
                            </form>
                            <button class="btn btn-large rename-btn">改檔名</button>
                            <form action="file_management.php" method="post" class="rename-form" style="display:none; margin-top: 10px;"> <!-- 初始隱藏 -->
                                <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($row['filename']); ?>">
                                <input type="text" name="new_name" placeholder="新檔案名稱">
                                <button type="submit" name="rename_file" class="btn btn-large">確定</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">暫無檔案資料</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function updateFileName(input) {
    const fileName = input.files[0] ? input.files[0].name : '';
    document.getElementById('file_name_display').textContent = fileName;
    document.getElementById('file_name_display').style.visibility = fileName ? 'hidden' : 'hidden';
}

function toggleNavbar() {
    var navbar = document.getElementById('navbarNav');
    if (navbar.style.display === 'block') {
        navbar.style.display = 'none';
    } else {
        navbar.style.display = 'block';
    }
}

document.querySelectorAll('.rename-btn').forEach(button => {
    button.addEventListener('click', function() {
        // 隱藏所有其他的 rename-form
        document.querySelectorAll('.rename-form').forEach(form => {
            if (form !== this.nextElementSibling) {
                form.style.display = 'none';
            }
        });

        // 切換當前的 rename-form 的顯示狀態
        const renameForm = this.nextElementSibling;
        renameForm.style.display = renameForm.style.display === 'none' ? 'inline-flex' : 'none';
    });
});
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
