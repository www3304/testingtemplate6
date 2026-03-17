<?php
session_start();
session_unset();  // 清除所有 session 变量
session_destroy(); // 销毁 session

// 跳转回登录页（你可以根据你的登录页路径修改）
header('Location: login.php');
exit;
