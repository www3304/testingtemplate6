<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['old-pw'];
    $new_pw = $_POST['new-pw'];
    $new_pw_re = $_POST['new-pw-re'];

    $stmt = $pdo->prepare("SELECT * FROM domain_users WHERE domain_name = ? AND user_id = ?");
    $stmt->execute([$_SESSION['user']['domain'], $_SESSION['user']['user_id']]);
    $user = $stmt->fetch();

    if ($user && password_verify($pw, $user['password']) && $new_pw === $new_pw_re) {
      $hashedPassword = password_hash($new_pw, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("UPDATE domain_users SET password=? WHERE domain_name=? AND user_id=? AND role=?");
      $stmt->execute([$hashedPassword, $_SESSION['user']['domain'], $_SESSION['user']['user_id'], $_SESSION['user']['role']]);
      $msg = '✅ Password updated successfully. Redirecting to login page...' ;
      echo "<script>
              alert('$msg');
              window.location.href = 'login.php';
            </script>";
    //   header('Location: login.php');
      exit;
    } else {
        if(!($new_pw === $new_pw_re))
          $msg = '❌ Re-entered new password doesn\'t match';
        else
          $msg = '❌ Invalid credentials.';
    }
}
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Password Management</title>
  <style>
    body {
      background-color: #f9fafb;
      font-family: 'Inter', sans-serif;
      color: #1f2937;
      margin: 0;
      padding: 1rem;
    }

    .form-wrapper {
      max-width: 800px;
      margin: auto;
    }
    
    .h2-section {
      text-align: center;
      font-size: 1.4rem;
      margin-bottom: 1.5rem;
      color: #111827;
    }
    
    .section-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .section-card label {
      font-weight: 600;
      font-size: 0.95rem;
      color: #374151;
      margin-bottom: 0.25rem;
      display: block;
    }
    
    .section-card input[type="password"] {
      width: 100%;
      padding: 0.75rem 1rem;
      margin-top: 0.3rem;
      margin-bottom: 1rem;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      background-color: #f9fafb;
      font-size: 0.95rem;
      transition: border 0.2s;
    }
    
    .section-card input[type="password"]:focus{
      border-color: #3b82f6;
      outline: none;        
    }

    button[type="submit"] {
      background: linear-gradient(90deg, #3b82f6, #2563eb);
      color: white;
      border: none;
      padding: 0.6rem 1.5rem;
      font-size: 0.95rem;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.3s;
      margin-top: 1rem;
      /* display: block; */
      margin-left: auto;
      margin-right: auto;
    }

    button[type="submit"]:hover {
      background: linear-gradient(90deg, #2563eb, #1d4ed8);
    }
    
    .msg{
        color: #dc3545;
        text-align: center;
        margin-bottom: 15px;
        font-size: 16px;
    }
  </style>
</head>
<body>
  <?php include 'nav.php'; ?>
  <div class="main">
    <div class="form-wrapper">
      <h2 class="h2-section" style="text-align:center;">Change Password</h2>
      <form method="POST">
        <div class="section-card">
          <label for="old-pw">Current Password:</label><input id="old-pw" name="old-pw" type="password" required>
          <label for="new-pw">New Password:</label><input id="new-pw" name="new-pw" type="password" required>
          <label for="new-pw-re">Re-enter New Password:</label><input id="new-pw-re" name="new-pw-re" type="password" required>
        </div>
        <?php if ($msg): ?>
            <div class="msg"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <button type="submit" name="save_sections">Save All</button>
      </form>
    </div>
  </div>
</body>