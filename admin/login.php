<?php
session_start();
include '../config.php';

$error = '';

// 当前 domain，例如 abc.com
$domain = str_replace('www.', '', strtolower($_SERVER['HTTP_HOST']));

// 接收登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['email']; // 实际是 user_id
    $password = $_POST['password'];

    // 先查这个 domain 的用户
    $stmt = $pdo->prepare("SELECT * FROM domain_users WHERE domain_name = ? AND user_id = ?");
    $stmt->execute([$domain, $user_id]);
    $user = $stmt->fetch();

    // 如果没找到，再查 superadmin（忽略 domain）
    if (!$user) {
        $stmt = $pdo->prepare("SELECT * FROM domain_users WHERE role = 'superadmin' AND user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }

    // 验证密码
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'user_id' => $user['user_id'],
            'role' => $user['role'],
            'domain' => $user['domain_name'] ?? null
        ];
        header('Location: admin.php');
        exit;
    } else {
        $error = '❌ Invalid credentials.';
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            display: flex;
            min-height: 100vh;
            justify-content: center;
            align-items: center;
        }
        .login-box {
            background: #fff;
            padding: 30px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 420px;
        }
        .login-box h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
            font-size: 24px;
        }
        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 18px;
        }
        .login-box button {
            width: 100%;
            background: #007bff;
            color: white;
            border: none;
            padding: 14px;
            font-size: 18px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .login-box button:hover {
            background: #0056b3;
        }
        .error {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
            font-size: 16px;
        }
        @media (max-width: 480px) {
            .login-box {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Admin Login</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="email" placeholder="User ID" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
