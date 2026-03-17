<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
    header('Location: login.php');
    exit;
}

// 获取当前公司域名信息
$host = $_SERVER['HTTP_HOST'];
$domain = str_replace('www.', '', strtolower(trim($host)));

$stmt = $pdo->prepare("SELECT company_name FROM domain_list WHERE domain_name = ?");
$stmt->execute([$domain]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  echo "<p style='color:red'>❌ Domain not found in domain_list.</p>";
  exit;
}

$company = $row['company_name'];
$prefix = $company . "_";

// 获取所有 section
$sections = $pdo->query("SELECT * FROM {$prefix}companySections")->fetchAll(PDO::FETCH_ASSOC);

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sections'])) {
  foreach ($_POST['section'] as $sectionKey => $data) {
    $status = $data['status'];
    $stmt = $pdo->prepare("UPDATE {$prefix}companySections SET status = ? WHERE section_key = ?");
    $stmt->execute([$status, $sectionKey]);
  }
  echo "<script>alert('✅ Sections updated successfully!'); window.location.href = window.location.href;</script>";
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Section Management</title>
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

    .section-key {
      font-size: 0.95rem;
      background: #f3f4f6;
      padding: 0.4rem 0.8rem;
      border-radius: 6px;
      font-family: monospace;
      text-transform: uppercase;
      margin: 0.25rem 0 0.75rem;
      border: 1px dashed #d1d5db;
      display: inline-block;
    }

    select {
      width: 100%;
      padding: 0.5rem 0.75rem;
      font-size: 0.95rem;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      background-color: #fff;
      transition: border 0.2s;
    }

    select:focus {
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

    @media (max-width: 600px) {
      body {
        padding: 0.5rem;
      }

      .form-wrapper {
        padding: 0 0.25rem;
      }

      .h2-section {
        font-size: 1.2rem;
        margin-bottom: 1rem;
      }

      .section-card {
        padding: 0.85rem;
        margin-bottom: 1.25rem;
      }

      .section-card label {
        font-size: 1rem;
      }

      .section-key {
        display: block;
        font-size: 0.85rem;
        word-break: break-word;
        padding: 0.35rem 0.65rem;
      }

      select {
        width: 100%;
        padding: 0.6rem 0.75rem;
        font-size: 1rem;
      }

      button[type="submit"] {
        width: 100%;
        margin-top: 1rem;
        padding: 0.75rem;
        font-size: 1rem;
      }
    }
  </style>

</head>

<body>
  <?php include 'nav.php'; ?>
  <div class="main">
    <div class="form-wrapper">
      <h2 class="h2-section" style="text-align:center;">Section Management</h2>
      <form method="POST">
        <?php foreach ($sections as $section): ?>
          <div class="section-card">
            <label>Section Key:</label>
            <p class="section-key"><?= strtoupper(htmlspecialchars($section['section_key'])) ?></p>

            <input type="hidden" name="section[<?= $section['section_key'] ?>][section_key]" value="<?= htmlspecialchars($section['section_key']) ?>">

            <label>Status:</label>
            <select name="section[<?= $section['section_key'] ?>][status]">
              <option value="active" <?= $section['status'] === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $section['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
              <?php if($section['section_key'] === 'address'): ?>
                <option value="map-only" <?= $section['status'] === 'map-only' ? 'selected' : '' ?>>Map Only</option>
              <?php endif ?>
            </select>
          </div>
        <?php endforeach; ?>
        <button type="submit" name="save_sections">Save All</button>
      </form>
    </div>
  </div>
</body>

</html>