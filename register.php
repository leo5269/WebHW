<?php
session_start();

// 連接到 MySQL 資料庫
$conn = new mysqli("localhost", "root", "", "weblogin");

// 檢查連接
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 檢查表單是否已提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $color = $_POST['favorite_color'];

    // 將選擇的性別值轉換為 female, male 或 other
    if ($gender == "女") {
        $gender = "female";
    } elseif ($gender == "男") {
        $gender = "male";
    } else {
        $gender = "other";
    }

    // 檢查帳號是否已存在
    $stmt = $conn->prepare("SELECT id FROM login_data WHERE username = ?");
    if (!$stmt) {
        die("SQL 錯誤: " . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // 如果帳號已存在，顯示錯誤訊息並返回註冊頁面
        echo "<script>alert('帳號已存在，請選擇其他帳號！'); window.location.href='register.html';</script>";
    } else {
        // 哈希密碼
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 插入新會員資料到資料庫
        $stmt = $conn->prepare("INSERT INTO login_data (username, password, email, gender, color) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("SQL 錯誤: " . $conn->error);
        }
        $stmt->bind_param("sssss", $username, $hashed_password, $email, $gender, $color);

        if ($stmt->execute()) {
            // 使用JavaScript alert顯示註冊成功訊息，並在1秒後自動跳轉到登入頁面
            echo "<script>
                    alert('註冊成功！');
                    setTimeout(function(){
                        window.location.href = 'WebHW.html';
                    }, 200); // 0.2秒後跳轉
                  </script>";
        } else {
            echo "註冊失敗: " . $stmt->error;
        }
    }

    $stmt->close();
}

$conn->close();
?>
