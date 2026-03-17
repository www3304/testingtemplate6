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

// 获取 Google Translate 设置
$companyInfo = $pdo->query("SELECT * FROM {$prefix}companyInfo LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$companyInfo) {
    echo "<script>alert('⚠️ Please create company info first before managing languages.'); window.location.href='admin.php';</script>";
    exit;
}

// 获取语言列表
$languages = $pdo->query("SELECT * FROM {$prefix}companyLanguages ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 保存 Google Translate 设置
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_translate_setting'])) {
    $translateChoice = $_POST['googletranslate'] ?? 'google';
    $stmt = $pdo->prepare("UPDATE {$prefix}companyInfo SET googletranslate = ? WHERE id = ?");
    $stmt->execute([$translateChoice, $companyInfo['id']]);
    header("Location: languages_management.php?msg=Google Translate setting updated!");
    exit;
}

// 添加语言
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_language'])) {
    $language = trim($_POST['language']);
    if ($language) {
        $stmt = $pdo->prepare("INSERT INTO {$prefix}companyLanguages (language) VALUES (?)");
        $stmt->execute([$language]);
        header("Location: languages_management.php?msg=Language added!");
        exit;
    }
}

// 更新语言
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_language'])) {
    $lang_id = (int)$_POST['lang_id'];
    $language = trim($_POST['language']);
    if ($lang_id && $language) {
        $stmt = $pdo->prepare("UPDATE {$prefix}companyLanguages SET language = ? WHERE id = ?");
        $stmt->execute([$language, $lang_id]);
        header("Location: languages_management.php?msg=Language updated!");
        exit;
    }
}

// 删除语言
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    // ❌ 防止删除第一个语言（id = 1）
    if ($delete_id === 1) {
        header("Location: languages_management.php?msg=❌ The first language cannot be deleted!");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM {$prefix}companyLanguages WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: languages_management.php?msg=Language deleted!");
    exit;
}

// 保存支持语言
if (isset($_POST['save_included_languages'])) {
    $selectedLanguages = $_POST['included_languages'] ?? [];
    $codesString = implode(',', $selectedLanguages);

    $stmt = $pdo->prepare("UPDATE {$prefix}companyInfo SET googleincludedlanguages = ? WHERE id = ? AND language_id = ?");
    $stmt->execute([$codesString, $companyInfo['id'], 1]);
    header("Location: languages_management.php?msg=Google Languages Included!");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Translate & Languages</title>
    <link href="css/languages_management.css" rel="stylesheet">
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="main">
        <div class="form-wrapper">

            <?php if (!empty($_GET['msg'])): ?>
                <script>
                    alert("<?= htmlspecialchars($_GET['msg']) ?>");
                </script>
            <?php endif; ?>

            <!-- Google Translate Setting -->
            <div class="section-card">
                <h3>Google Translate Setting</h3>
                <form method="POST">
                    <label>Choose Translation Method:</label>
                    <select name="googletranslate" onchange="this.form.submit()">
                        <option value="" <?= $companyInfo['googletranslate'] === '' ? 'selected' : '' ?>>Google Translate</option>
                        <option value="no" <?= $companyInfo['googletranslate'] === 'no' ? 'selected' : '' ?>>Custom Input</option>
                    </select>
                    <input type="hidden" name="save_translate_setting" value="1">
                </form>
            </div>

            <?php if (!isset($companyInfo['googletranslate']) || $companyInfo['googletranslate'] === '' || $companyInfo['googletranslate'] === 'google'): ?>
                <!-- Google Translate Included Languages -->
                <div class="section-card">
                    <h3>Google Translate - Included Languages</h3>
                    <form method="POST">
                        <div class="checkbox-group">
                            <?php
                            $languageList = [
                                'en' => 'English',
                                'zh-CN' => 'Chinese (Simplified)',
                                'ms' => 'Malay',
                                'ja' => 'Japanese',
                                'ko' => 'Korean',
                                'th' => 'Thai',
                                'fr' => 'French',
                                'de' => 'German',
                                'es' => 'Spanish'
                            ];
                            $enabledCodes = explode(',', $companyInfo['googleincludedlanguages'] ?? '');
                            foreach ($languageList as $code => $name):
                                $checked = in_array($code, $enabledCodes) ? 'checked' : '';
                            ?>
                                <label>
                                    <input type="checkbox" name="included_languages[]" value="<?= htmlspecialchars($code) ?>" <?= $checked ?>>
                                    <span><?= htmlspecialchars($name) ?> (<?= htmlspecialchars($code) ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" name="save_included_languages">Save Included Languages</button>
                    </form>
                </div>
            <?php elseif ($companyInfo['googletranslate'] === 'no'): ?>
                <!-- Add New Language -->
                <div class="section-card">
                    <h3>Add New Language</h3>
                    <form method="POST">
                        <input type="text" name="language" placeholder="Language Name (e.g. English, Chinese)" required>
                        <button type="submit" name="add_language">Add</button>
                    </form>
                </div>

                <!-- Languages List -->
                <div class="section-card">
                    <h3>Languages List</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Language</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($languages as $lang): ?>
                                <tr>
                                    <td data-label="ID"><?= $lang['id'] ?></td>
                                    <td data-label="Language">
                                        <form method="POST">
                                            <input type="text" name="language" value="<?= strtoupper(htmlspecialchars($lang['language'])) ?>" required>
                                            <input type="hidden" name="lang_id" value="<?= $lang['id'] ?>">
                                            <div class="action-buttons">
                                                <button type="submit" name="edit_language" class="btn update-btn">Update</button>
                                                <?php if ($lang['id'] != 1): ?>
                                                    <a href="?delete_id=<?= $lang['id'] ?>" class="btn delete-btn" onclick="return confirm('Delete this language?')">Delete</a>
                                                <?php else: ?>
                                                    <button type="button" class="btn delete-btn" disabled style="opacity:0.5; cursor:not-allowed;">Cannot Delete</button>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>

    </div>
</body>

</html>