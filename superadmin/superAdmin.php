<?php
session_start();
include('../config.php');
require_once '../auto_update_db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: login.php');
    exit;
}

// === Basic Config ===
$cpanel_user = 'pdadmin';
$cpanel_pass = 'D8tcv*J?mhSZ3vxthp';
$cpanel_ip   = '146.103.45.213';
$primary_domain = 'landingpanel.pixeldream.com.my';
$document_root   = 'landingpanel.pixeldream.com.my';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_domain'])) {
    $domain_type = $_POST['domain_type']; // subdomain or addondomain
    $domain_name = strtolower(trim($_POST['domain_name']));
    $company_name = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['company_name']);

    if (empty($domain_name) || empty($company_name)) {
        echo "<script>alert('❌ Domain name or company name cannot be empty.'); window.history.back();</script>";
        exit;
    }

    try {
        // 1. 检查db是否已存在
        $stmt = $pdo->prepare("SELECT id FROM domain_list WHERE company_name = ?");
        $stmt->execute([$company_name]);
        $exists = $stmt->fetch();

        if ($exists) {
            echo "<script>alert('❌ Company name is exist!'); window.history.back();</script>";
            exit;
        }

        // === 生成完整域名 & API参数 ===
        if ($domain_type === "subdomain") {
            $subdomain_prefix = preg_replace('/[^a-z0-9\-]/', '', $domain_name);

            $query = "https://$cpanel_ip:2083/json-api/cpanel" .
                "?cpanel_jsonapi_user=$cpanel_user" .
                "&cpanel_jsonapi_apiversion=2" .
                "&cpanel_jsonapi_module=SubDomain" .
                "&cpanel_jsonapi_func=addsubdomain" .
                "&domain=$subdomain_prefix" .
                "&rootdomain=$primary_domain" .
                "&dir=$document_root";

            $full_domain_name = $subdomain_prefix . '.' . $primary_domain;
        } elseif ($domain_type === "addondomain") {
            $newdomain = preg_replace('/[^a-z0-9\.\-]/', '', $domain_name);

            $query = "https://$cpanel_ip:2083/json-api/cpanel" .
                "?cpanel_jsonapi_user=$cpanel_user" .
                "&cpanel_jsonapi_apiversion=2" .
                "&cpanel_jsonapi_module=AddonDomain" .
                "&cpanel_jsonapi_func=addaddondomain" .
                "&newdomain=$newdomain" .
                "&subdomain=" . explode('.', $newdomain)[0] . "_" . $cpanel_user .
                "&dir=$document_root";

            $full_domain_name = $newdomain;
        } else {
            echo "<script>alert('❌ Domain type invalid.'); window.history.back();</script>";
            exit;
        }

        // === 执行 cPanel API ===
        $auth = base64_encode("$cpanel_user:$cpanel_pass");
        $headers = ["Authorization: Basic $auth"];

        $ch = curl_init($query);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER     => $headers
        ]);
        $raw_response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($raw_response, true);

        if (isset($response['cpanelresult']['data'][0]['result']) && $response['cpanelresult']['data'][0]['result'] == 1) {

            // === Insert into domain_list ===
            $stmt = $pdo->prepare("INSERT INTO domain_list (domain_name, company_name, status, created_at) 
                                VALUES (:domain_name, :company_name, 'active', NOW())");
            $stmt->execute([
                ':domain_name' => $full_domain_name,
                ':company_name' => $company_name
            ]);

            // $createTablesSQL = [
            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyInfo (
            //         id INT AUTO_INCREMENT PRIMARY KEY,
            //         domain VARCHAR(255) NOT NULL,
            //         name VARCHAR(255),
            //         email VARCHAR(255),
            //         phone VARCHAR(50),
            //         address TEXT,
            //         logo VARCHAR(255),
            //         header_logo VARCHAR(255),
            //         banner_image VARCHAR(255),
            //         banner_caption VARCHAR(255),
            //         about_title VARCHAR(255),
            //         about_image VARCHAR(255),
            //         about_description TEXT,
            //         features_title VARCHAR(255),
            //         provide_title VARCHAR(255),
            //         provide_text TEXT,
            //         meta_title VARCHAR(255),
            //         meta_description TEXT,
            //         googletranslate VARCHAR(255),
            //         language_id INT DEFAULT 1,
            //         gallery_title VARCHAR(255),
            //         video_title VARCHAR(255),
            //         blog_title VARCHAR(255),
            //         blog_sub_title VARCHAR(255),
            //         googleincludedlanguages TEXT,
            //         header_script TEXT,
            //         body_script TEXT,
            //         footer_script TEXT
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyFeatures (
            //         id INT AUTO_INCREMENT PRIMARY KEY,
            //         company_id INT,
            //         title VARCHAR(255),
            //         description TEXT,
            //         icon VARCHAR(255),
            //         language_id INT DEFAULT 1
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyProvides (
            //         id INT AUTO_INCREMENT PRIMARY KEY,
            //         company_id INT,
            //         title VARCHAR(255),
            //         description TEXT,
            //         icon VARCHAR(255),
            //         language_id INT DEFAULT 1
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyGallery (
            //         id INT AUTO_INCREMENT PRIMARY KEY,
            //         company_id INT,
            //         image_path VARCHAR(255),
            //         caption text DEFAULT NULL
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companySocials (
            //         id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            //         company_id INT NOT NULL,
            //         name VARCHAR(50),
            //         icon_path VARCHAR(255),
            //         link_url VARCHAR(255),
            //         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}blogs (
            //         id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            //         language_id INT(11) NOT NULL DEFAULT 1,
            //         category_id INT(11) DEFAULT NULL,
            //         title VARCHAR(255) NOT NULL,
            //         slug VARCHAR(255) NOT NULL,
            //         content LONGTEXT NOT NULL,
            //         image VARCHAR(255) DEFAULT NULL,
            //         author VARCHAR(100) DEFAULT NULL,
            //         status ENUM('draft','published') DEFAULT 'draft',
            //         created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            //         updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}blogCategories (
            //         id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            //         name VARCHAR(255) NOT NULL,
            //         language_id INT(11) NOT NULL DEFAULT 1
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companySections (
            //         id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            //         section_key VARCHAR(50) NOT NULL,
            //         status ENUM('active', 'inactive') DEFAULT 'active'
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyVideo (
            //         id INT AUTO_INCREMENT PRIMARY KEY,
            //         type ENUM('link', 'file') DEFAULT 'link',
            //         video_link VARCHAR(255) DEFAULT NULL,
            //         video_file VARCHAR(255) DEFAULT NULL,
            //         title VARCHAR(255) NOT NULL,
            //         date DATE NOT NULL DEFAULT CURRENT_DATE
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyBanner (
            //         id INT AUTO_INCREMENT PRIMARY KEY,
            //         image VARCHAR(255) NOT NULL
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyLanguages (
            //         id INT AUTO_INCREMENT PRIMARY KEY,
            //         language VARCHAR(50) NOT NULL
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyNavigation (
            //         id INT AUTO_INCREMENT PRIMARY KEY,
            //         nav_name VARCHAR(100) NOT NULL,
            //         nav_value VARCHAR(100) NOT NULL,
            //         language_id INT NOT NULL
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companySubnav (
            //         id int(11) AUTO_INCREMENT PRIMARY KEY,
            //         company_id INT,
            //         subnav_name varchar(100) NOT NULL,
            //         subnav_link varchar(100),
            //         language_id int(11) NOT NULL
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyCarousel (
            //         id int(11) AUTO_INCREMENT PRIMARY KEY,
            //         company_id INT,
            //         section varchar(255) DEFAULT NULL,
            //         title varchar(255) DEFAULT NULL,
            //         language_id int(11) NOT NULL DEFAULT 1
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

            //     "CREATE TABLE IF NOT EXISTS {$table_prefix}companyCarouselSlides (
            //         id int(11) AUTO_INCREMENT PRIMARY KEY,
            //         carousel_id int(11) DEFAULT NULL,
            //         slide_id int(11) DEFAULT NULL,
            //         title varchar(255) DEFAULT NULL,
            //         icon varchar(255) DEFAULT NULL,
            //         text text DEFAULT NULL
            //     ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            // ];

            $table_prefix = $company_name . "_";
            try {
                autoSyncCompanySchema($pdo, $table_prefix);
                echo "✅ Schema sync complete for prefix: $table_prefix<br>";
            } catch (Exception $e) {
                throw new Exception("Schema sync failed: " . $e->getMessage());
            }

            // === Insert default domain into company info ===
            $insertDefaultDomain = "INSERT INTO {$table_prefix}companyInfo (domain) VALUES ('$full_domain_name')";
            $pdo->exec($insertDefaultDomain);

            // // === Insert default sections ===
            // $insertDefaultSections = "INSERT INTO {$table_prefix}companySections (section_key, status) VALUES
            //     ('about', 'active'),
            //     ('features', 'active'),
            //     ('provide', 'active'),
            //     ('gallery', 'active'),
            //     ('video', 'active'),
            //     ('contact', 'active'),
            //     ('address', 'active'),
            //     ('subnav', 'inactive'),
            //     ('blog', 'inactive')";
            // $pdo->exec($insertDefaultSections);

            // // === Insert default language items ===
            // $insertDefaultLanguage = "INSERT INTO {$table_prefix}companyLanguages (language) VALUES ('english')";
            // $pdo->exec($insertDefaultLanguage);

            // // Insert default carousels
            // $insertDefaultCarousels = "INSERT INTO {$table_prefix}companyCarousel (id, company_id, section, title, language_id) VALUES
            //     (1, 1, 'about', 'About Carousel', 1),
            //     (2, 1, 'features', 'Features Carousel', 1),
            //     (3, 1, 'provide', 'Provides Carousel', 1),
            //     (4, 1, 'gallery', 'Gallery Carousel', 1),
            //     (5, 1, 'video', 'Videos Carousel', 1)";
            // $pdo->exec($insertDefaultCarousels);

            echo "<script>alert('✅ Domain and all company tables created successfully: {$full_domain_name}'); window.location.href='superAdmin.php';</script>";
        } else {
            echo "<script>alert('❌ Failed to create domain. It may already exist on server.'); window.history.back();</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('⚠️ Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Subdomain</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .main {
            padding: 2rem;
            padding-left: 270px;
            min-height: 100vh;
        }

        .form-container {
            background-color: #ffffff;
            max-width: 700px;
            margin: auto;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        @media(min-width: 769px) {
            .form-container {
                max-width: 100%;
            }
        }

        .form-container h2 {
            margin-bottom: 1.5rem;
            color: #1f2937;
            font-size: 1.5rem;
            border-left: 4px solid #3b82f6;
            padding-left: 0.75rem;
        }

        .form-container label {
            display: block;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            color: #374151;
        }

        .form-container select {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            /* 右侧 padding 调大，避免文字压到箭头 */
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background-color: #f9fafb;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            margin-top: 0.5rem;
            appearance: none;
            /* 移除浏览器默认箭头 */
            -webkit-appearance: none;
            -moz-appearance: none;

            /* 自定义下拉箭头 */
            background-image: url("data:image/svg+xml,%3Csvg fill='none' stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            /* 箭头位置 */
            background-size: 1.2rem;
            /* 箭头大小 */
            cursor: pointer;
        }


        .form-container select:focus {
            border-color: #3b82f6;
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }


        .form-container input[type="text"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background-color: #f9fafb;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            margin-top: 0.5rem;
        }

        .form-container input[type="text"]:focus {
            border-color: #3b82f6;
            background-color: #fff;
            outline: none;
        }

        .form-container small {
            display: block;
            color: #6b7280;
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }

        .form-container button {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .form-container button:hover {
            background-color: #2563eb;
        }

        @media (max-width: 768px) {
            .main {
                padding-left: 1rem;
                padding-right: 1rem;
                padding-top: 5rem;
            }

            .form-container {
                padding: 1.5rem;
                margin: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="main">
        <div class="form-container">
            <h2>Create New Domain</h2>
            <form method="post">
                <label>
                    <strong>Domain Type</strong>
                    <select name="domain_type" required>
                        <option value="subdomain">Subdomain (xxx.landingpanel.pixeldream.com.my)</option>
                        <option value="addondomain">Addon Domain (example.com)</option>
                    </select>
                </label>

                <label>
                    <strong>Domain Name</strong>
                    <small>
                        - If Subdomain: just enter prefix (e.g. <b>yourname</b>) → yourname.landingpanel.pixeldream.com.my<br>
                        - If Addon Domain: enter full domain (e.g. <b>abccompany.com</b>)
                    </small>
                    <input type="text" name="domain_name" required>
                </label>

                <label>
                    <strong>Company Name</strong>
                    <small>Only alphanumeric (spaces & special chars removed automatically)</small>
                    <input type="text" name="company_name" required>
                </label>

                <button type="submit" name="create_domain">Create Domain</button>
            </form>
        </div>
    </div>
</body>

</html>