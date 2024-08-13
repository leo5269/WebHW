<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: WebHW.html");
    exit();
}

$username = $_SESSION['username'];

$conn = new mysqli("localhost", "root", "", "weblogin");

if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

$action = '查看修改個人資料';
$stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $action);
$stmt->execute();

$sql = "SELECT username, password, email, gender, color FROM login_data WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    $current_username = $row['username'];
    $current_password = ""; // 顯示時空白，但資料庫會抓到正確的值
    $current_email = $row['email'];
    $current_gender = $row['gender'];
    $current_color = $row['color'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST['username'];
    $new_password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $row['password'];
    $new_email = $_POST['email'];
    $new_gender = $_POST['gender'];
    $new_color = $_POST['favorite_color'];

    $stmt = $conn->prepare("UPDATE login_data SET username = ?, password = ?, email = ?, gender = ?, color = ? WHERE username = ?");
    $stmt->bind_param("ssssss", $new_username, $new_password, $new_email, $new_gender, $new_color, $username);

    if ($stmt->execute()) {
        echo "<script>alert('修改成功！'); window.location.href='Member_list.php';</script>";
	    $stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
    	    $action = '修改個人資料';
            $stmt->bind_param("ss", $username, $action);
            $stmt->execute();
    } else {
        echo "<script>alert('修改失敗: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改個人資料</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar {
            background-color: #ffffff;
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
        .form-container {
            margin-top: 20px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .form-control {
            margin-bottom: 15px;
            width: 100%; /* 讓輸入框寬度延伸 */
        }
        .form-group label {
            font-size: 16px;
            color: #fff;
            display: block;
            margin-bottom: 5px;
        }
        .form-group input[type="radio"] + label {
            color: #fff;
            margin-right: 10px;
        }

        .form-group input[type="color"] {
            width: 100%;
            height: 50px;
            padding: 0;
            margin-bottom: 15px;
        }
        .form-container form {
            background-color: #333;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        .form-container form input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
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
    <span class="welcome-message"><?php echo htmlspecialchars($username); ?>，您好! <a href="logout.php" class="btn btn-link text-dark">登出</a></span>
</nav>

<div class="form-container">
    <form action="modify_member.php" method="post">
        <div class="form-group">
            <label for="username">帳號：</label>
            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($current_username); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">密碼：</label>
            <input type="password" class="form-control" id="password" name="password" value="">
        </div>
        <div class="form-group">
            <label for="email">電子信箱：</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" required>
        </div>
        <div class="form-group">
            <label for="gender">性別：</label><br>
            <input type="radio" id="female" name="gender" value="female" <?php echo ($current_gender == 'female') ? 'checked' : ''; ?>>
            <label for="female" style="color: #fff; display: inline-block;">女</label>
            <input type="radio" id="male" name="gender" value="male" <?php echo ($current_gender == 'male') ? 'checked' : ''; ?>>
            <label for="male" style="color: #fff; display: inline-block;">男</label>
            <input type="radio" id="other" name="gender" value="other" <?php echo ($current_gender == 'other') ? 'checked' : ''; ?>>
            <label for="other" style="color: #fff; display: inline-block;">其他</label>
        </div>
        <div class="form-group">
            <label for="favorite_color">喜好顏色：</label>
            <input type="color" class="form-control" id="favorite_color" name="favorite_color" value="<?php echo htmlspecialchars($current_color); ?>">
        </div>
        <input type="submit" value="修改">
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"  crossorigin="anonymous"></script>
<script>
document.getElementById("favorite_color").addEventListener("input", function() {
    this.style.backgroundColor = this.value;
});
</script>

</body>
</html>
