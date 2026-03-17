<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}

$msg = '';
$current_version = 'v1'; // 默认版本

// 获取当前版本
$stmt = $pdo->prepare("SELECT version FROM domain_list WHERE domain_name = ?");
$stmt->execute([$_SESSION['user']['domain']]);
$result = $stmt->fetch();
if ($result && $result['version']) {
    $current_version = $result['version'];
}

// 处理POST请求 - 保存新版本
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['version'])) {
    $version = trim($_POST['version']);
    
    // 验证版本值
    $allowed_versions = ['v1', 'v2', 'v3', 'v4'];
    if (in_array($version, $allowed_versions)) {
        try {
            $stmt = $pdo->prepare("UPDATE domain_list SET version = ? WHERE domain_name = ?");
            $stmt->execute([$version, $_SESSION['user']['domain']]);
            $current_version = $version;
            $msg = '✅ Version updated successfully to ' . htmlspecialchars($version);
        } catch (Exception $e) {
            $msg = '❌ Error updating version: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        $msg = '❌ Invalid version selected.';
    }
}
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Version Control</title>
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
    
    .section-card select {
      width: 100%;
      padding: 0.75rem 1rem;
      margin-top: 0.3rem;
      margin-bottom: 1rem;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      background-color: #ffffff;
      font-size: 0.95rem;
      transition: border 0.2s;
      cursor: pointer;
    }
    
    .section-card select:focus {
      border-color: #3b82f6;
      outline: none;        
    }

    .version-info {
      background-color: #f0f9ff;
      border: 1px solid #bfdbfe;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      font-size: 0.9rem;
      color: #1e40af;
    }

    .version-description {
      margin-top: 0.5rem;
      padding-top: 0.5rem;
      border-top: 1px solid #bfdbfe;
      font-size: 0.85rem;
      line-height: 1.5;
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
      margin-left: auto;
      margin-right: auto;
      display: block;
    }

    button[type="submit"]:hover {
      background: linear-gradient(90deg, #2563eb, #1d4ed8);
    }
    
    .msg {
      color: #dc3545;
      text-align: center;
      margin-bottom: 15px;
      font-size: 16px;
      padding: 0.75rem 1rem;
      border-radius: 6px;
    }

    .msg.success {
      color: #155724;
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
    }

    .msg.error {
      color: #721c24;
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>
<body>
  <?php include 'nav.php'; ?>
  <div class="main">
    <div class="form-wrapper">
      <h2 class="h2-section">Version Control</h2>
      
      <div class="version-info">
        <strong>Current Version:</strong> <span style="font-size: 1.1rem; font-weight: 700;"><?= htmlspecialchars($current_version) ?></span>
        <div class="version-description">
          The selected version determines which template layout and features are displayed on your website.
        </div>
      </div>

      <form method="POST">
        <div class="section-card">
          <label for="version">Select Version:</label>
          <select id="version" name="version" required>
            <option value="v1" <?= $current_version === 'v1' ? 'selected' : '' ?>>v1</option>
            <option value="v2" <?= $current_version === 'v2' ? 'selected' : '' ?>>v2</option>
            <option value="v3" <?= $current_version === 'v3' ? 'selected' : '' ?>>v3</option>
            <option value="v4" <?= $current_version === 'v4' ? 'selected' : '' ?>>v4</option>
          </select>
        </div>

        <?php if ($msg): ?>
            <div class="msg <?= strpos($msg, '✅') !== false ? 'success' : 'error' ?>">
              <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <button type="submit" name="save_version">Save Version</button>
      </form>
    </div>
  </div>
</body>
```
