<?php
// blog.php
include 'config.php';

$language_id = isset($_GET['lang']) ? intval($_GET['lang']) : 1;
$blog_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$blog_id) {
    echo "Article not found.";
    exit;
}

// Domain & prefix logic
$host = $_SERVER['HTTP_HOST'];
$domain = str_replace('www.', '', strtolower(trim($host)));

$stmt = $pdo->prepare("SELECT company_name FROM domain_list WHERE domain_name = ?");
$stmt->execute([$domain]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "Domain not configured.";
    exit;
}
$company = $row['company_name'];
$prefix = $company . "_";

// Company info
$stmt = $pdo->prepare("SELECT blog_title, blog_sub_title FROM {$prefix}companyInfo WHERE domain = ? AND language_id = ?");
$stmt->execute([$domain, $language_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Blog data
$stmt = $pdo->prepare("SELECT * FROM {$prefix}blogs WHERE id = ? AND language_id = ?");
$stmt->execute([$blog_id, $language_id]);
$blog = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$blog) {
    echo "Blog not found.";
    exit;
}

// Category (if exists)
$categoryName = '';
if (!empty($blog['category_id'])) {
    $stmt = $pdo->prepare("SELECT name FROM {$prefix}blogCategories WHERE id = ? AND language_id = ?");
    $stmt->execute([$blog['category_id'], $language_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    $categoryName = $category['name'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= ($blog['title']) ?> - <?= ($info['blog_title'] ?? '') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: "Inter", sans-serif;
      color: #333;
      background: #fff;
      line-height: 1.6;
    }

    .container {
      max-width: 900px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .post-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .post-header h1 {
      font-size: 2.4rem;
      margin-bottom: 8px;
      color: #222;
    }
    .post-meta {
      display: flex;
      justify-content: center;
      gap: 16px;
      flex-wrap: wrap;
      font-size: 0.9rem;
      color: #777;
      margin-bottom: 15px;
    }
    .post-meta span {
      background: #f5f5f5;
      padding: 4px 10px;
      border-radius: 20px;
    }
    .post-header .post-subtitle {
      font-size: 1.1rem;
      color: #555;
      margin-top: 10px;
    }

    .post-image {
      width: 100%;
      max-height: 420px;
      overflow: hidden;
      margin-bottom: 30px;
      border-radius: 12px;
    }
    .post-image img {
      width: 100%;
      height: auto;
      object-fit: cover;
    }

    .post-content {
      margin-bottom: 40px;
    }
    .post-content img {
      max-width: 100%;
      border-radius: 8px;
      margin: 20px 0;
    }
    .post-content h2, .post-content h3 {
      margin-top: 32px;
      margin-bottom: 16px;
      color: #222;
    }
    .post-content p {
      margin-bottom: 18px;
    }
    .post-content blockquote {
      margin: 24px 0;
      padding: 16px;
      border-left: 4px solid #0073aa;
      background: #f9fafb;
      color: #555;
      font-style: italic;
    }
    .post-content a {
      color: #0073aa;
      text-decoration: none;
    }
    .post-content a:hover {
      text-decoration: underline;
    }

    .post-footer {
      text-align: center;
      margin-top: 40px;
    }
    .btn-back {
      display: inline-block;
      padding: 10px 22px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      color: #0073aa;
      border: 1px solid rgba(0, 115, 170, 0.3);
      border-radius: 50px;
      text-decoration: none;
      font-size: 14px;
      transition: all 0.3s ease;
    }
    .btn-back:hover {
      background: rgba(0, 115, 170, 0.1);
      transform: translateY(-2px);
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="post-header">
      <h1><?= ($blog['title']) ?></h1>

      <div class="post-meta">
        <span><?= date('d M Y', strtotime($blog['created_at'])) ?></span>
        <?php if (!empty($categoryName)): ?><span><?= ($categoryName) ?></span><?php endif; ?>
        <?php if (!empty($blog['author'])): ?><span>By <?= ($blog['author']) ?></span><?php endif; ?>
      </div>

      <?php if (!empty($info['blog_sub_title'])): ?>
        <div class="post-subtitle"><?= ($info['blog_sub_title']) ?></div>
      <?php endif; ?>
    </div>

    <?php if (!empty($blog['image'])): ?>
      <div class="post-image">
        <img src="<?= 'uploads/blogs/' . ($blog['image']) ?>" alt="<?= ($blog['title']) ?>">
      </div>
    <?php endif; ?>

    <div class="post-content">
      <?= $blog['content'] ?>
    </div>

    <div class="post-footer">
      <a href="javascript:history.back()" class="btn-back">← Back to Articles</a>
    </div>
  </div>
</body>
</html>
