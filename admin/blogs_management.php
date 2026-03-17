<?php
// admin_blogs.php
session_start();
include '../config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ----- AUTH -----
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header('Location: login.php');
  exit;
}

// ----- DOMAIN / PREFIX (same pattern you used) -----
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
$prefix = $company . "_"; // table prefix used below

// ----- ensure upload dir exists -----
$uploadBase = __DIR__ . '/../uploads/blogs/';
if (!is_dir($uploadBase)) {
  @mkdir($uploadBase, 0755, true);
}

// ----- Helper functions -----
function slugify($text)
{
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  $text = strtolower($text);
  return $text ?: 'n-a';
}

function uploadImage($fileInputName, $uploadBase)
{
  if (empty($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
    return null;
  }
  $f = $_FILES[$fileInputName];
  $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
  if (!in_array($f['type'], $allowed))
    return null;
  $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
  $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $dest = rtrim($uploadBase, '/') . '/' . $filename;
  if (move_uploaded_file($f['tmp_name'], $dest)) {
    return $filename;
  }
  return null;
}

// ----- Load languages for this company (reuse companyLanguages table pattern) -----
$langsTable = $prefix . "companyLanguages";
try {
  $languages = $pdo->query("SELECT * FROM {$langsTable} ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  echo "<p style='color:red'>❌ Missing languages table: {$langsTable}</p>";
  exit;
}

$currentLangId = isset($_GET['lang_id']) ? intval($_GET['lang_id']) : ($languages[0]['id'] ?? 1);

// ----- Tables used -----
// {prefix}blogs
// {prefix}blogCategories
$blogsTable = $prefix . "blogs";
$catsTable = $prefix . "blogCategories";

// Create tables if not exist (safe - minimal schema)
$pdo->exec("
CREATE TABLE IF NOT EXISTS `{$catsTable}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `language_id` int NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS `{$blogsTable}` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `language_id` int NOT NULL DEFAULT 1,
  `category_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ----- Handle actions: add/edit/delete/category create -----
$action = $_GET['action'] ?? null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// POST: save blog
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_blog'])) {
  $language_id = isset($_POST['language_id']) ? (int) $_POST['language_id'] : $currentLangId;
  $title = trim($_POST['title'] ?? '');
  $content = $_POST['content'] ?? '';
  $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
  $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
  $author = substr(trim($_POST['author'] ?? $_SESSION['user']['username'] ?? 'admin'), 0, 100);
  $created_at = !empty($_POST['created_at']) ? date('Y-m-d H:i:s', strtotime($_POST['created_at'])) : date('Y-m-d H:i:s');

  // image handling
  $uploadedFilename = uploadImage('image', $uploadBase);

  if (!empty($_POST['id'])) {
    // UPDATE
    $bid = (int) $_POST['id'];
    // fetch existing to delete old image if replaced
    $old = $pdo->prepare("SELECT image FROM {$blogsTable} WHERE id = ?");
    $old->execute([$bid]);
    $oldRow = $old->fetch(PDO::FETCH_ASSOC);

    $sql = "UPDATE {$blogsTable} SET language_id=?, category_id=?, title=?, slug=?, content=?, author=?, status=?, updated_at=NOW(), created_at=?";
    $params = [$language_id, $category_id, $title, slugify($title), $content, $author, $status, $created_at];

    if ($uploadedFilename) {
      $sql .= ", image=?";
      $params[] = $uploadedFilename;
    }

    $sql .= " WHERE id = ?";
    $params[] = $bid;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // delete old image if replaced
    if ($uploadedFilename && !empty($oldRow['image'])) {
      @unlink($uploadBase . $oldRow['image']);
    }

    $_SESSION['flash'] = "✅ Blog updated.";
    header("Location: ?lang_id={$language_id}");
    exit;
  } else {
    // INSERT
    $sql = "INSERT INTO {$blogsTable} (language_id, category_id, title, slug, content, image, author, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $slug = slugify($title);
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$language_id, $category_id, $title, $slug, $content, $uploadedFilename, $author, $status, $created_at]);

    $_SESSION['flash'] = "✅ Blog created.";
    header("Location: ?lang_id={$language_id}");
    exit;
  }
}

// POST: add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
  $catName = trim($_POST['category_name'] ?? '');
  $langIdForCat = isset($_POST['category_language_id']) ? (int) $_POST['category_language_id'] : $currentLangId;
  if ($catName !== '') {
    $stmt = $pdo->prepare("INSERT INTO {$catsTable} (name, language_id) VALUES (?, ?)");
    $stmt->execute([$catName, $langIdForCat]);
    $_SESSION['flash'] = "✅ Category added.";
  }
  header("Location: ?lang_id={$langIdForCat}");
  exit;
}

// GET: delete blog
if ($action === 'delete' && $id) {
  $stmt = $pdo->prepare("SELECT image FROM {$blogsTable} WHERE id = ?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row) {
    $del = $pdo->prepare("DELETE FROM {$blogsTable} WHERE id = ?");
    $del->execute([$id]);
    if (!empty($row['image']))
      @unlink($uploadBase . $row['image']);
    $_SESSION['flash'] = "✅ Blog deleted.";
  }
  header("Location: ?lang_id={$currentLangId}");
  exit;
}

// GET: delete category
if ($action === 'delcat' && $id) {
  // set category_id to null for blogs using it
  $pdo->prepare("UPDATE {$blogsTable} SET category_id = NULL WHERE category_id = ?")->execute([$id]);
  $pdo->prepare("DELETE FROM {$catsTable} WHERE id = ?")->execute([$id]);
  $_SESSION['flash'] = "✅ Category removed (blogs preserved).";
  header("Location: ?lang_id={$currentLangId}");
  exit;
}

// ----- Fetch data for listing and edit form -----
$categories = $pdo->prepare("SELECT * FROM {$catsTable} WHERE language_id = ? ORDER BY name ASC");
$categories->execute([$currentLangId]);
$categories = $categories->fetchAll(PDO::FETCH_ASSOC);

$blogsStmt = $pdo->prepare("SELECT b.*, c.name as category_name FROM {$blogsTable} b LEFT JOIN {$catsTable} c ON b.category_id = c.id WHERE b.language_id = ? ORDER BY b.created_at DESC");
$blogsStmt->execute([$currentLangId]);
$blogs = $blogsStmt->fetchAll(PDO::FETCH_ASSOC);

$editItem = null;
if ($action === 'edit' && $id) {
  $stmt = $pdo->prepare("SELECT * FROM {$blogsTable} WHERE id = ?");
  $stmt->execute([$id]);
  $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Flash messages
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?><!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - Blogs Management</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* ====== Admin Panel Enhanced Layout ====== */
    body {
      background: #f7f9fc;
      font-family: "Segoe UI", Roboto, sans-serif;
      color: #333;
    }

    .container {
      max-width: 1600px;
      margin-left: 260px;
      padding: 16px;
    }

    .topbar {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
    }

    .lang-selector select {
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      background: #fff;
      transition: all 0.2s ease;
    }

    .lang-selector select:hover {
      border-color: #0073aa;
    }

    .card {
      background: #fff;
      border: 1px solid #e6e6e6;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 16px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
      transition: box-shadow 0.2s ease;
    }

    .card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
    }

    th {
      background: #f0f3f7;
      text-align: left;
      padding: 12px;
      font-weight: 600;
      border-bottom: 2px solid #e0e0e0;
    }

    td {
      padding: 10px 12px;
      border-bottom: 1px solid #f1f1f1;
      vertical-align: middle;
    }

    tr:hover td {
      background: #f9fcff;
    }

    .actions a {
      margin-right: 8px;
      color: white;
      font-weight: 500;
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .actions a:hover {
      color: #b33f40;
    }

    .thumb {
      max-width: 140px;
      height: auto;
      border-radius: 6px;
      border: 1px solid #ddd;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .form-row {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .form-row .col {
      flex: 1;
      min-width: 240px;
    }

    label {
      font-weight: 600;
      margin-bottom: 6px;
      display: inline-block;
    }

    input[type="text"],
    input[type="file"],
    select,
    textarea {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      transition: border-color 0.2s ease;
      background: #fff;
    }

    input:focus,
    textarea:focus,
    select:focus {
      outline: none;
      border-color: #0073aa;
      box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
    }

    textarea {
      resize: vertical;
      min-height: 100px;
    }

    .small {
      font-size: 13px;
      color: #666;
    }

    .btn {
      display: inline-block;
      padding: 8px 14px;
      background: #0073aa;
      color: #fff;
      border-radius: 6px;
      text-decoration: none;
      border: none;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s ease, transform 0.1s ease;
    }

    .btn:hover {
      background: #b33f40;
      transform: translateY(-1px);
    }

    .btn.red {
      background: #d64545;
    }

    .btn.red:hover {
      background: #b93838;
    }

    .btn.gray {
      background: red;
    }

    .btn.gray:hover {
      background: #5a6268;
    }

    .notice {
      padding: 10px 14px;
      border-radius: 6px;
      margin-bottom: 16px;
      font-size: 14px;
    }

    .notice.success {
      background: #e6ffed;
      border: 1px solid #b7f0c7;
      color: #2e7d32;
    }

    .notice.error {
      background: #ffe6e6;
      border: 1px solid #f0b7b7;
      color: #b71c1c;
    }

    @media (max-width: 768px) {
      .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }

      .form-row .col {
        min-width: 100%;
      }
    }
  </style>


  <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.0/tinymce.min.js"></script>
</head>

<body>
  <?php include 'nav.php'; ?>
  <div class="container">
    <div class="topbar">
      <div>
        <h1>Blogs Management</h1>
        <div class="small">Manage blogs in multiple languages (English / Chinese)</div>
      </div>
      <div class="lang-selector">
        <form id="langForm" method="get">
          <label for="langSelect">Language</label>
          <select id="langSelect" name="lang_id" onchange="document.getElementById('langForm').submit();">
            <?php foreach ($languages as $lang): ?>
              <option value="<?= htmlspecialchars($lang['id']) ?>" <?= ($lang['id'] == $currentLangId ? 'selected' : '') ?>>
                <?= strtoupper(htmlspecialchars($lang['language'])) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="notice success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="card">
      <h2>Create / Edit Blog</h2>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($editItem['id'] ?? '') ?>">
        <input type="hidden" name="language_id"
          value="<?= htmlspecialchars($editItem['language_id'] ?? $currentLangId) ?>">

        <div class="form-row">
          <div class="col">
            <label>Title</label><br>
            <input type="text" name="title" value="<?= htmlspecialchars($editItem['title'] ?? '') ?>"
              style="width:100%;padding:8px" required>
          </div>

          <div class="col">
            <label>Category</label><br>
            <select name="category_id" style="width:100%;padding:8px">
              <option value="">— None —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (isset($editItem['category_id']) && $editItem['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col">
            <label>Status</label><br>
            <select name="status" style="width:100%;padding:8px">
              <option value="draft" <?= (isset($editItem['status']) && $editItem['status'] == 'draft') ? 'selected' : '' ?>>Draft</option>
              <option value="published" <?= (isset($editItem['status']) && $editItem['status'] == 'published') ? 'selected' : '' ?>>Published</option>
            </select>
          </div>
        </div>

        <div style="margin-top:12px;">
          <label>Featured Image (optional)</label><br>
          <?php if (!empty($editItem['image'])): ?>
            <img src="<?= '../uploads/blogs/' . htmlspecialchars($editItem['image']) ?>" class="thumb" alt="thumb"><br>
            <small class="small">Uploading a new image will replace the existing one.</small><br>
          <?php endif; ?>
          <input type="file" name="image" accept="image/*">
        </div>

        <div style="margin-top:12px;">
          <label>Content</label>
          <textarea name="content" class="wysiwyg"><?= htmlspecialchars($editItem['content'] ?? '') ?></textarea>
        </div>

        <div style="margin-top:12px;">
          <label>Author</label><br>
          <input type="text" name="author"
            value="<?= htmlspecialchars($editItem['author'] ?? $_SESSION['user']['username'] ?? '') ?>"
            style="width:300px;padding:8px">
        </div>

        <div style="margin-top:12px;">
          <label>Created Date & Time</label><br>
          <input type="datetime-local" name="created_at"
            value="<?= isset($editItem['created_at']) ? date('Y-m-d\TH:i', strtotime($editItem['created_at'])) : date('Y-m-d\TH:i') ?>"
            style="width:300px;padding:8px">
          <small class="small">Adjust publish date/time if needed.</small>
        </div>

        <div style="margin-top:12px;">
          <button type="submit" name="save_blog" class="btn"><?= $editItem ? 'Update Blog' : 'Create Blog' ?></button>
          <?php if ($editItem): ?>
            <a href="?lang_id=<?= $currentLangId ?>" class="btn gray">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card">
      <h2>Categories (Language scoped)</h2>
      <form method="post" style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="category_language_id" value="<?= $currentLangId ?>">
        <input type="text" name="category_name" placeholder="New category name" style="padding:8px">
        <button type="submit" name="add_category" class="btn">Add</button>
      </form>

      <div style="margin-top:12px">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $catListStmt = $pdo->prepare("SELECT * FROM {$catsTable} WHERE language_id = ? ORDER BY name ASC");
            $catListStmt->execute([$currentLangId]);
            $catList = $catListStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php foreach ($catList as $c): ?>
              <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td class="actions">
                  <a class="btn gray" href="?action=delcat&id=<?= $c['id'] ?>&lang_id=<?= $currentLangId ?>"
                    onclick="return confirm('Delete this category? Blogs will keep their content but category will be removed.')">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($catList)): ?>
              <tr>
                <td colspan="3" class="small">No categories for this language yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h2>All Blogs
        (<?= strtoupper(htmlspecialchars($languages[array_search($currentLangId, array_column($languages, 'id'))]['language'] ?? '')) ?>)
      </h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Category</th>
            <th>Image</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($blogs) === 0): ?>
            <tr>
              <td colspan="7" class="small">No blogs for this language yet.</td>
            </tr>
          <?php endif; ?>
          <?php foreach ($blogs as $b): ?>
            <tr>
              <td><?= $b['id'] ?></td>
              <td><?= htmlspecialchars($b['title']) ?></td>
              <td><?= htmlspecialchars($b['category_name'] ?? '—') ?></td>
              <td>
                <?php if (!empty($b['image'])): ?>
                  <img src="<?= '../uploads/blogs/' . htmlspecialchars($b['image']) ?>" class="thumb" alt="img">
                <?php else: ?>
                  <span class="small">No image</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($b['status']) ?></td>
              <td><?= htmlspecialchars($b['created_at']) ?></td>
              <td class="actions">
                <a class="btn" href="?action=edit&id=<?= $b['id'] ?>&lang_id=<?= $currentLangId ?>">Edit</a>
                <a class="btn red" href="?action=delete&id=<?= $b['id'] ?>&lang_id=<?= $currentLangId ?>"
                  onclick="return confirm('Delete this blog? This action cannot be undone.')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="margin:18px 0;color:#999;font-size:13px">
      <strong>Notes:</strong>
      <ul>
        <li>All blog data is language-scoped by the language selector.</li>
        <li>Images are saved to <code>/uploads/blogs/</code>. Make sure the folder is writable by PHP.</li>
        <li>If you want to change table names, update <code>$prefix</code> and the table variables near the top.</li>
      </ul>
    </div>

  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      tinymce.init({
        selector: '.wysiwyg',
        height: 420,
        menubar: false,
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste help wordcount',
        toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | link image media | removeformat | code | preview',
        branding: false,
        relative_urls: false,
        remove_script_host: false
      });
    });
  </script>
</body>

</html>