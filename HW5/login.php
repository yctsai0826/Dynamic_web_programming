

<?php

// 111550035 蔡昀錚第5次作業12/6
// 111550035 Yun-Cheng Tsai The Fifth Homework 12/6

session_start();
$conn = new mysqli('localhost', 'root', '', 'GameDB');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 檢查帳號是否存在
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password);

    if ($stmt->num_rows > 0) {
        // 帳號存在，檢查密碼
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            echo "登入成功！";
            header('Location: game.php');
            exit;
        } else {
            // 密碼錯誤
            echo "<script>alert('密碼錯誤，請再試一次！');</script>";
        }
    } else {
        // 帳號不存在
        echo "<script>alert('帳號不存在，請檢查您的輸入或註冊新帳號！');</script>";
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入頁面</title>
    <style>
        @font-face {
            font-family: 'enfont';
            src: url('./font/enfont.ttf') format('truetype');
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-family: 'enfont';
            position: relative;
        }

        /* 輪播容器 */
        .background-slider {
            position: absolute;
            top: 0;
            left: 0;
            width: 500%; /* 包含所有圖片的寬度 */
            height: 100%;
            display: flex;
            transition: transform 1.5s ease-in-out; /* 每次單張圖片的過渡 */
        }

        .background-slider img {
            width: 100vw; /* 每張圖片佔滿整個視窗 */
            height: 100%;
            object-fit: cover;
        }

        /* 半透明黑色遮罩 */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* 半透明黑色遮罩 */
            z-index: 1;
        }

        /* 表單容器 */
        .login-container {
            position: relative;
            z-index: 5;
            background: white;
            padding: 30px 40px;
            border-radius: 3px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.8);
            height: 46vh;
            width: 17vw;
            animation: slideIn 1s ease-out;
        }

        .login-container h2 {
            margin-top: 2.5vh;
            margin-bottom: 2vh;
        }

        .placeholder2 {
            margin-bottom: 3vh;
        }

        /* 表單內的元素 */
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        input {
            margin: 10px 0;
            padding: 10px;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: rgb(56, 39, 32);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: rgb(88, 68, 59);
        }

        .register-button {
            display: block;
            margin-top: 15px;
            text-align: center;
            text-decoration: none;
            color: white;
            background-color: rgb(118, 96, 82);;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .register-button:hover {
            background-color: rgb(88, 68, 59);
        }

        /* 中間方框的漸入動畫 */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- 背景圖片輪播 -->
    <div class="background-slider">
        <img src="img/bg1.jpg" alt="Background 1">
        <img src="img/bg2.jpg" alt="Background 2">
        <img src="img/bg3.jpg" alt="Background 3">
    </div>
    <div class="overlay"></div> <!-- 半透明黑色遮罩 -->

    <div class="login-container">
        <form method="POST">
            <h2>- Log in -</h2>
            <input type="text" name="username" placeholder="帳號" required>
            <input class="placeholder2" type="password" name="password" placeholder="密碼" required>
            <button type="submit">Log in</button>
        </form>
        <!-- 註冊按鈕 -->
        <a href="index.php" class="register-button">Go to Register</a>
    </div>

    <script>
        const slider = document.querySelector('.background-slider');
        const images = document.querySelectorAll('.background-slider img');
        const totalSlides = images.length; // 總圖片數量
        let currentIndex = 0;

        let forward = 1;
        function showNextImage() {
            if (currentIndex === totalSlides - 1) {
                forward = 0;
            } else if (currentIndex === 0) {
                forward = 1;
            }
            if (forward){
                currentIndex = (currentIndex + 1) % totalSlides;
            } else {
                currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
            }
            const offset = -currentIndex * 100; // 計算每次滑動的偏移量
            slider.style.transform = `translateX(${offset}vw)`; // 使用 vw 單位進行滑動
        }

        setInterval(showNextImage, 4000);
    </script>
</body>
</html>
