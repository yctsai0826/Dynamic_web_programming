
<?php

// 111550035 蔡昀錚第5次作業12/6
// 111550035 Yun-Cheng Tsai The Fifth Homework 12/6
session_start();

// 銷毀 SESSION
session_unset();
session_destroy();

// 將使用者重定向回登入頁面
header('Location: login.php');
exit;
?>
