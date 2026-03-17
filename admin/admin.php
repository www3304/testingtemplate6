<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header('Location: login.php');
  exit;
}

function saveImage($fieldName, $domain, $folder = '../uploads/')
{
  if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
    return '';
  }

  $safeDomain = preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $domain);
  $targetDir = rtrim($folder, '/') . '/' . $safeDomain . '/';

  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }

  $originalName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES[$fieldName]['name']));
  $filename = time() . '_' . uniqid() . '_' . $originalName;
  $targetPath = $targetDir . $filename;

  if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
    return ltrim(str_replace('../', '', $targetPath), '/');
  } else {
    return '';
  }
}

// 获取域名 & 公司前缀
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

// 读取语言选项
$languages = $pdo->query("SELECT * FROM {$prefix}companyLanguages ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 当前语言 ID
$currentLangId = isset($_GET['lang_id']) ? intval($_GET['lang_id']) : ($languages[0]['id'] ?? 1);

// echo json_encode($currentLangId);
// exit();

if (!$currentLangId) {
  echo "<p style='color:red'>❌ No language found for this company.</p>";
  exit;
}

$carouselSectionSelected = $_GET['carousel_section'] ?? 'about';

// 获取数据
$stmt = $pdo->prepare("SELECT * FROM {$prefix}companyInfo WHERE domain = ? AND language_id = ? LIMIT 1");
$stmt->execute([$domain, $currentLangId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

$company_id = $existing['id'] ?? 0;
$features = $pdo->prepare("SELECT * FROM {$prefix}companyFeatures WHERE company_id = ? AND language_id = ?");
$features->execute([$company_id, $currentLangId]);
$features = $features->fetchAll(PDO::FETCH_ASSOC);

$provides = $pdo->prepare("SELECT * FROM {$prefix}companyProvides WHERE company_id = ? AND language_id = ?");
$provides->execute([$company_id, $currentLangId]);
$provides = $provides->fetchAll(PDO::FETCH_ASSOC);

$gallery = $pdo->prepare("SELECT * FROM {$prefix}companyGallery");
$gallery->execute();
$gallery = $gallery->fetchAll(PDO::FETCH_ASSOC);

$socials = $pdo->prepare("SELECT * FROM {$prefix}companySocials");
$socials->execute();
$socials = $socials->fetchAll(PDO::FETCH_ASSOC);

$bannerImg = $pdo->query("SELECT * FROM {$prefix}companyBanner")->fetchAll(PDO::FETCH_ASSOC);
$videos = $pdo->query("SELECT * FROM {$prefix}companyVideo")->fetchAll(PDO::FETCH_ASSOC);

$subnavs = $pdo->prepare("SELECT * FROM {$prefix}companySubnav WHERE company_id = ? AND language_id = ?");
$subnavs->execute([$company_id, $currentLangId]);
$subnavs = $subnavs->fetchAll(PDO::FETCH_ASSOC);

// 获取PDF数据
$pdfs = $pdo->prepare("SELECT * FROM {$prefix}companyPDFs WHERE company_id = ? AND language_id = ?");
$pdfs->execute([$company_id, $currentLangId]);
$pdfs = $pdfs->fetchAll(PDO::FETCH_ASSOC);

// 获取导航数据
$navs = $pdo->prepare("SELECT * FROM {$prefix}companyNavigation WHERE language_id = ? ORDER BY id ASC");
$navs->execute([$currentLangId]);
$navs = $navs->fetchAll(PDO::FETCH_ASSOC);

// 固定顺序的 nav_value
$navValues = ['home', 'about', 'features', 'provide', 'gallery', 'video', 'contact', 'blog', 'subnav'];

// Carousel
$carousel = null;
$carouselSlides = [];

$carouselStmt = $pdo->prepare("SELECT * FROM {$prefix}companyCarousel WHERE company_id = ? AND language_id = ? AND section = ?");
$carouselStmt->execute([$company_id, $currentLangId, $carouselSectionSelected]);
$carousel = $carouselStmt->fetch(PDO::FETCH_ASSOC);

if ($carousel) {
  $slidesStmt = $pdo->prepare("SELECT * FROM {$prefix}companyCarouselSlides WHERE carousel_id = ? ORDER BY slide_id ASC, id ASC");
  $slidesStmt->execute([$carousel['id']]);
  $carouselSlides = $slidesStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 保存数据
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $selectedLangId = isset($_POST['language_id']) ? (int) $_POST['language_id'] : (int) $currentLangId;

  // ======= 新增 / 更新全部导航 START =======
  if (!empty($_POST['nav_name']) && is_array($_POST['nav_name'])) {
    foreach ($navValues as $navValue) {
      // 如果用户有输入该导航的名字
      if (!empty($_POST['nav_name'][$navValue])) {
        $name = trim($_POST['nav_name'][$navValue]);

        // 检查是否已存在该 nav_value（当前语言）
        $stmt = $pdo->prepare("SELECT id FROM {$prefix}companyNavigation WHERE nav_value = ? AND language_id = ?");
        $stmt->execute([$navValue, $selectedLangId]);
        $navId = $stmt->fetchColumn();

        if ($navId) {
          // 已存在 -> 更新
          $stmt = $pdo->prepare("UPDATE {$prefix}companyNavigation SET nav_name = ? WHERE id = ?");
          $stmt->execute([$name, $navId]);
        } else {
          // 不存在 -> 新增
          $stmt = $pdo->prepare("INSERT INTO {$prefix}companyNavigation (nav_name, nav_value, language_id) VALUES (?, ?, ?)");
          $stmt->execute([$name, $navValue, $selectedLangId]);
        }
      }
    }

    // echo "<script>alert('✅ Navigation Links Saved successfully!'); window.location.href = '?lang_id={$selectedLangId}';</script>";
    // exit;
  }

  // ======= 新增导航处理逻辑 END =======
  
  // 处理About Image删除
  if (!empty($_POST['about_image_remove'])) {
    $about_img = '';
  } else {
    $about_img = saveImage('about_image', $domain) ?: ($existing['about_image'] ?? '');
  }
  
  $logo = saveImage('logo', $domain) ?: ($existing['logo'] ?? '');
  $banner = saveImage('banner_image', $domain) ?: ($existing['banner_image'] ?? '');

  if ($existing) {
    $stmt = $pdo->prepare("UPDATE {$prefix}companyInfo 
    SET name=?, logo=?, banner_image=?, about_title=?, about_image=?, about_description=?, 
        features_title=?, provide_title=?, provide_text=?, meta_title=?, meta_description=?, 
        gallery_title=?, video_title=?, blog_title=?, blog_sub_title=?, pdf_title=?,
        email=?, phone=?, address=?, header_script=?, body_script=?, footer_script=?
    WHERE domain = ? AND language_id = ?");
    $stmt->execute([
      $_POST['name'],
      $logo,
      $banner,
      $_POST['about_title'],
      $about_img,
      $_POST['about_description'],
      $_POST['features_title'],
      $_POST['provide_title'],
      $_POST['provide_text'],
      $_POST['meta_title'],
      $_POST['meta_description'],
      $_POST['gallery_title'],
      $_POST['video_title'],
      $_POST['blog_title'],
      $_POST['blog_sub_title'],
      $_POST['pdfsectiontitle'],
      $_POST['email'],
      $_POST['phone'],
      $_POST['address'],
      $_POST['header_script'],
      $_POST['body_script'],
      $_POST['footer_script'],
      $domain,
      $selectedLangId
    ]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO {$prefix}companyInfo 
    (domain, language_id, name, logo, banner_image, about_title, about_image, about_description, 
    features_title, provide_title, provide_text, meta_title, meta_description, googletranslate, 
    gallery_title, video_title, blog_title, blog_sub_title, pdf_title, email, phone, address, 
    header_script, body_script, footer_script) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
      $domain,
      $selectedLangId,
      $_POST['name'],
      $logo,
      $banner,
      $_POST['about_title'],
      $about_img,
      $_POST['about_description'],
      $_POST['features_title'],
      $_POST['provide_title'],
      $_POST['provide_text'],
      $_POST['meta_title'],
      $_POST['meta_description'],
      "no",
      $_POST['gallery_title'],
      $_POST['video_title'],
      $_POST['blog_title'],
      $_POST['blog_sub_title'],
      $_POST['pdfsectiontitle'],
      $_POST['email'],
      $_POST['phone'],
      $_POST['address'],
      $_POST['header_script'],
      $_POST['body_script'],
      $_POST['footer_script']
    ]);

    $company_id = $pdo->lastInsertId();
  }

  // 更新 Features
  if (!empty($_POST['feature_title'])) {
    foreach ($_POST['feature_title'] as $i => $title) {
      // if (empty($title))
      //   continue;
      $id = $_POST['feature_id'][$i] ?? null;
      $description = $_POST['feature_description'][$i];
      $existingIcon = $_POST['feature_existing_icon'][$i] ?? '';
      
      // 如果标记为删除，则设为空；否则上传新图片或保留旧图片
      if ($existingIcon === '__DELETE__') {
        $icon = '';
      } else {
        $newIcon = saveImage("feature_icon_$i", $domain);
        $icon = $newIcon ?: $existingIcon;
      }

      if ($id) {
        $stmt = $pdo->prepare("UPDATE {$prefix}companyFeatures 
                    SET title=?, description=?, icon=? 
                    WHERE id=? AND company_id=? AND language_id=?");
        $stmt->execute([$title, $description, $icon, $id, $company_id, $selectedLangId]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO {$prefix}companyFeatures 
                    (company_id, language_id, title, description, icon) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $selectedLangId, $title, $description, $icon]);
      }
    }
  }

  // 更新 Provides
  if (!empty($_POST['provide_title_item'])) {
    $submittedProvideIds = $_POST['provide_id'] ?? [];

    // Delete removed provides
    if (!empty($provides)) {
      $existingIds = array_column($provides, 'id');
      $toDelete = array_diff($existingIds, $submittedProvideIds);
      if ($toDelete) {
        $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
        $stmt = $pdo->prepare("DELETE FROM {$prefix}companyProvides WHERE id IN ($placeholders) AND company_id = ? AND language_id = ?");
        $stmt->execute(array_merge($toDelete, [$company_id, $selectedLangId]));
      }
    }

    // Insert/update submitted provides
    foreach ($_POST['provide_title_item'] as $i => $title) {
      if (empty(trim($title)))
        continue;

      $id = $_POST['provide_id'][$i] ?? null;
      $description = $_POST['provide_description'][$i];
      $existingIcon = $_POST['provide_existing_icon'][$i] ?? '';
      
      // 如果标记为删除，则设为空；否则上传新图片或保留旧图片
      if ($existingIcon === '__DELETE__') {
        $icon = '';
      } else {
        $newIcon = saveImage("provide_icon_$i", $domain);
        $icon = $newIcon ?: $existingIcon;
      }

      if ($id) {
        $stmt = $pdo->prepare("UPDATE {$prefix}companyProvides SET title=?, description=?, icon=? WHERE id=? AND company_id=? AND language_id=?");
        $stmt->execute([$title, $description, $icon, $id, $company_id, $selectedLangId]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO {$prefix}companyProvides (company_id, language_id, title, description, icon) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $selectedLangId, $title, $description, $icon]);
      }
    }
  }

  // Gallery
  if (!empty($_FILES['gallery']['name']) || !empty($gallery)) {
    $submittedGalleryIds = $_POST['gallery_id'] ?? [];
    $submittedCaptions = $_POST['gallery_caption'] ?? [];

    if (!empty($gallery)) {
      $existingIds = array_column($gallery, 'id');
      $toDelete = array_diff($existingIds, $submittedGalleryIds);
      if ($toDelete) {
        $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
        $stmt = $pdo->prepare("DELETE FROM {$prefix}companyGallery WHERE id IN ($placeholders) AND company_id = ?");
        $stmt->execute(array_merge($toDelete, [$company_id]));
      }
    }

    foreach ($_FILES['gallery']['name'] as $i => $name) {
      if (empty($name) && empty($submittedCaptions[$i]))
        continue;

      $_FILES['gallery_temp'] = [
        'name' => $_FILES['gallery']['name'][$i],
        'type' => $_FILES['gallery']['type'][$i],
        'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
        'error' => $_FILES['gallery']['error'][$i],
        'size' => $_FILES['gallery']['size'][$i]
      ];
      $newPath = !empty($name) ? saveImage('gallery_temp', $domain) : null;

      $galleryId = $_POST['gallery_id'][$i] ?? null;
      $caption = $submittedCaptions[$i] ?? '';
      if ($galleryId) {
        $sql = "UPDATE {$prefix}companyGallery SET caption = ?";
        $params = [$caption];

        if ($newPath) {
          $sql .= ", image_path = ?";
          $params[] = $newPath;
        }

        $sql .= " WHERE id = ? AND company_id = ?";
        $params[] = $galleryId;
        $params[] = $company_id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
      } else {
        if ($newPath) {
          $stmt = $pdo->prepare("INSERT INTO {$prefix}companyGallery (company_id, image_path, caption) VALUES (?, ?, ?)");
          $stmt->execute([$company_id, $newPath, $caption]);
        }
      }
    }
  }

  // Socials
  if (!empty($_POST['social_name'])) {
    $submittedSocialIds = $_POST['social_id'] ?? [];

    // Delete removed socials
    if (!empty($socials)) {
      $existingIds = array_column($socials, 'id');
      $toDelete = array_diff($existingIds, $submittedSocialIds);
      if ($toDelete) {
        $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
        $stmt = $pdo->prepare("DELETE FROM {$prefix}companySocials WHERE id IN ($placeholders) AND company_id = ?");
        $stmt->execute(array_merge($toDelete, [$company_id]));
      }
    }

    // Insert/update submitted socials
    foreach ($_POST['social_name'] as $i => $name) {
      if (empty($name))
        continue;
      $id = $_POST['social_id'][$i] ?? null;
      $link = $_POST['social_link'][$i];
      $existingIcon = $_POST['social_existing_icon'][$i] ?? '';
      
      // 如果标记为删除，则设为空；否则上传新图片或保留旧图片
      if ($existingIcon === '__DELETE__') {
        $icon = '';
      } else {
        $newIcon = saveImage("social_icon_$i", $domain);
        $icon = $newIcon ?: $existingIcon;
      }

      if ($id) {
        $stmt = $pdo->prepare("UPDATE {$prefix}companySocials SET name=?, icon_path=?, link_url=? WHERE id=? AND company_id=?");
        $stmt->execute([$name, $icon, $link, $id, $company_id]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO {$prefix}companySocials (company_id, name, icon_path, link_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$company_id, $name, $icon, $link]);
      }
    }
  }

  // Banner Images
  for ($i = 0; $i < 3; $i++) {

    // 如果用户按了 remove
    if (!empty($_POST["banner_remove_$i"])) {
      if (isset($bannerImg[$i])) {
        $bannerId = $bannerImg[$i]['id'];
        $stmt = $pdo->prepare("DELETE from {$prefix}companyBanner WHERE id = ?");
        $stmt->execute([$bannerId]);
      }
      continue; // 跳过下面的 upload
    }

    // 如果上传新图片
    $field = "banner_img_$i";
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
      $path = saveImage($field, $domain);
      if ($path) {
        if (isset($bannerImg[$i])) {
          $bannerId = $bannerImg[$i]['id'];
          $stmt = $pdo->prepare("UPDATE {$prefix}companyBanner SET image = ? WHERE id = ?");
          $stmt->execute([$path, $bannerId]);
        } else {
          $stmt = $pdo->prepare("INSERT INTO {$prefix}companyBanner (image) VALUES (?)");
          $stmt->execute([$path]);
        }
      }
    }
  }
  $bannerCaption = $_POST['banner_caption'] ?? '';
  $stmt = $pdo->prepare("UPDATE {$prefix}companyInfo SET banner_caption = ? WHERE id = ?");
  $stmt->execute([$bannerCaption, $company_id]);

  // Videos
  $videos = $pdo->query("SELECT * FROM {$prefix}companyVideo")->fetchAll(PDO::FETCH_ASSOC);
  $existingVideoIds = array_column($videos, 'id');

  $submittedIds = array_map('intval', $_POST['video_id'] ?? []);
  $submittedTypes = $_POST['video_type'] ?? [];
  $submittedTitles = $_POST['title'] ?? [];
  $submittedLinks = $_POST['video_link'] ?? [];

  $toDelete = array_values(array_diff($existingVideoIds, $submittedIds)); // ✅ reset keys

  if ($toDelete) {
    $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
    $stmt = $pdo->prepare("DELETE FROM {$prefix}companyVideo WHERE id IN ($placeholders)");
    $stmt->execute($toDelete);
  }

  foreach ($submittedTypes as $i => $type) {
    $id = $submittedIds[$i] ?? null;
    $title = $submittedTitles[$i] ?? '';
    $date = date('Y-m-d');
    $link = $type === 'link' ? ($submittedLinks[$i] ?? '') : null;
    $filePath = null;

    if ($type === 'file' && !empty($_FILES['video_file']['name'][$i]) && $_FILES['video_file']['error'][$i] === UPLOAD_ERR_OK) {
      $_FILES['video_temp'] = [
        'name' => $_FILES['video_file']['name'][$i],
        'type' => $_FILES['video_file']['type'][$i],
        'tmp_name' => $_FILES['video_file']['tmp_name'][$i],
        'error' => $_FILES['video_file']['error'][$i],
        'size' => $_FILES['video_file']['size'][$i],
      ];
      $filePath = saveImage('video_temp', 'videos', '../uploads/videos/');
    }


    if ($id) {
      // UPDATE
      $sql = "UPDATE {$prefix}companyVideo SET title = ?, type = ?, video_link = ?, date = ?";
      $params = [$title, $type, $link, $date];

      if ($filePath) {
        $sql .= ", video_file = ?";
        $params[] = $filePath;
      }

      $sql .= " WHERE id = ?";
      $params[] = $id;

      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
    } else {
      // INSERT
      $stmt = $pdo->prepare("INSERT INTO {$prefix}companyVideo (title, type, video_link, video_file, date) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$title, $type, $link, $filePath, $date]);
    }
  }

  // Subnav
  $submittedSubnavs = $_POST['subnav_name'] ?? [];

  $existingSubnavs = $pdo->prepare("SELECT id FROM {$prefix}companySubnav WHERE company_id = ? AND language_id = ?");
  $existingSubnavs->execute([$company_id, $selectedLangId]);
  $existingSubnavs = $existingSubnavs->fetchAll(PDO::FETCH_COLUMN); // array of existing IDs

  $submittedIds = []; // keep track of submitted IDs

  foreach ($submittedSubnavs as $i => $subnavName) {
    $subnavName = trim($subnavName);
    if ($subnavName === '')
      continue;

    $id = $_POST['subnav_id'][$i] ?? null;
    $subnavLink = $_POST['subnav_link'][$i] ?? '';

    if ($id) {
      $stmt = $pdo->prepare("UPDATE {$prefix}companySubnav 
                               SET subnav_name = ?, subnav_link = ? 
                               WHERE id = ? AND company_id = ? AND language_id = ?");
      $stmt->execute([$subnavName, $subnavLink, $id, $company_id, $selectedLangId]);
      $submittedIds[] = $id;
    } else {
      $stmt = $pdo->prepare("INSERT INTO {$prefix}companySubnav 
                               (company_id, language_id, subnav_name, subnav_link) 
                               VALUES (?, ?, ?, ?)");
      $stmt->execute([$company_id, $selectedLangId, $subnavName, $subnavLink]);
      $submittedIds[] = $pdo->lastInsertId();
    }
  }

  $idsToDelete = array_diff($existingSubnavs, $submittedIds);
  if (!empty($idsToDelete)) {
    $in = str_repeat('?,', count($idsToDelete) - 1) . '?';
    $stmt = $pdo->prepare("DELETE FROM {$prefix}companySubnav 
                           WHERE id IN ($in) AND company_id = ? AND language_id = ?");
    $params = array_merge($idsToDelete, [$company_id, $selectedLangId]);
    $stmt->execute($params);
  }

  // PDFs
  if (!empty($_POST['pdf_title'])) {
    $submittedPdfIds = $_POST['pdf_id'] ?? [];
    $validPdfIds = []; // Track which IDs have valid titles

    // First pass: identify which PDFs have valid titles
    foreach ($_POST['pdf_title'] as $i => $title) {
      if (!empty(trim($title))) {
        $id = $_POST['pdf_id'][$i] ?? null;
        if ($id) {
          $validPdfIds[] = $id;
        }
      }
    }

    // Delete removed PDFs (those not in validPdfIds OR those with empty titles)
    if (!empty($pdfs)) {
      $existingIds = array_column($pdfs, 'id');
      // Delete anything that's either removed from form or has empty title
      $toDelete = array_diff($existingIds, $validPdfIds);
      if ($toDelete) {
        $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
        $stmt = $pdo->prepare("DELETE FROM {$prefix}companyPDFs WHERE id IN ($placeholders) AND company_id = ? AND language_id = ?");
        $stmt->execute(array_merge($toDelete, [$company_id, $selectedLangId]));
      }
    }

    // Insert/update submitted PDFs
    foreach ($_POST['pdf_title'] as $i => $title) {
      $title = trim($title);
      if (empty($title))
        continue;

      $id = $_POST['pdf_id'][$i] ?? null;
      $newFile = saveImage("pdf_file_$i", $domain, '../uploads/pdfs/');
      $file = $newFile ?: ($_POST['pdf_existing_file'][$i] ?? '');

      if ($id) {
        $sql = "UPDATE {$prefix}companyPDFs SET title=?, pdf_file=? WHERE id=? AND company_id=? AND language_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $file, $id, $company_id, $selectedLangId]);
      } else {
        if (!empty($file)) {
          $sql = "INSERT INTO {$prefix}companyPDFs (company_id, language_id, title, pdf_file) VALUES (?, ?, ?, ?)";
          $stmt = $pdo->prepare($sql);
          $stmt->execute([$company_id, $selectedLangId, $title, $file]);
        }
      }
    }
  }

  //Carousel
  $postedCarouselSection = $_POST['carousel_section'] ?? $carouselSectionSelected;
  if ($postedCarouselSection) {
    $carouselTitle = $_POST['carousel_title'] ?? '';

    $checkStmt = $pdo->prepare("SELECT * FROM {$prefix}companyCarousel WHERE company_id = ? AND section = ? AND language_id = ? LIMIT 1");
    $checkStmt->execute([$company_id, $postedCarouselSection, $selectedLangId]);
    $existingCarousel = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingCarousel) {
      // update title
      $updateC = $pdo->prepare("UPDATE {$prefix}companyCarousel SET title = ? WHERE id = ?");
      $updateC->execute([$carouselTitle, $existingCarousel['id']]);
      $carouselId = $existingCarousel['id'];
    } else {
      // insert
      $insertC = $pdo->prepare("INSERT INTO {$prefix}companyCarousel (company_id, section, title, language_id) VALUES (?, ?, ?, ?)");
      $insertC->execute([$company_id, $postedCarouselSection, $carouselTitle, $selectedLangId]);
      $carouselId = $pdo->lastInsertId();
    }

    $existingSlidesStmt = $pdo->prepare("SELECT id FROM {$prefix}companyCarouselSlides WHERE carousel_id = ?");
    $existingSlidesStmt->execute([$carouselId]);
    $existingSlidesArr = $existingSlidesStmt->fetchAll(PDO::FETCH_COLUMN);
    $submittedSlideIds = [];

    $slideTitles = $_POST['slide_title'] ?? [];
    $slideTexts = $_POST['slide_text'] ?? [];
    $slideExistingIcons = $_POST['slide_existing_icon'] ?? [];

    foreach ($slideTitles as $i => $stitle) {
      $stitle = trim($stitle);
      $stext = trim($slideTexts[$i] ?? '');
      $id = $_POST['slide_id'][$i] ?? null;

      // handle uploaded icon
      $newIconPath = '';
      if (isset($_FILES['slide_icon']) && isset($_FILES['slide_icon']['error'][$i]) && $_FILES['slide_icon']['error'][$i] === UPLOAD_ERR_OK) {
        $_FILES['slide_icon_temp'] = [
          'name' => $_FILES['slide_icon']['name'][$i],
          'type' => $_FILES['slide_icon']['type'][$i],
          'tmp_name' => $_FILES['slide_icon']['tmp_name'][$i],
          'error' => $_FILES['slide_icon']['error'][$i],
          'size' => $_FILES['slide_icon']['size'][$i]
        ];
        $newIconPath = saveImage('slide_icon_temp', $domain);
      }
      $iconToSave = $newIconPath ?: ($slideExistingIcons[$i] ?? '');
      // Skip slide if all fields are empty, else update accordingly
      if ($stitle === '' && $stext === '' && $iconToSave === '')
        continue;
      if ($id) {
        $stmt = $pdo->prepare("UPDATE {$prefix}companyCarouselSlides 
                               SET slide_id = ?, title = ?, text = ?, icon = ? 
                               WHERE id = ? AND carousel_id = ?");
        $stmt->execute([$i + 1, $stitle, $stext, $iconToSave, $id, $carouselId]);
        $submittedSlideIds[] = $id;
      } else {
        $stmt = $pdo->prepare("INSERT INTO {$prefix}companyCarouselSlides (carousel_id, slide_id, title, icon, text) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$carouselId, $i + 1, $stitle, $iconToSave, $stext]);
        $submittedSlideIds[] = $pdo->lastInsertId();
      }
    }


    $toDeleteSlides = array_diff($existingSlidesArr, $submittedSlideIds);
    if (!empty($toDeleteSlides)) {
      $placeholders = implode(',', array_fill(0, count($toDeleteSlides), '?'));
      $stmt = $pdo->prepare("DELETE FROM {$prefix}companyCarouselSlides WHERE id IN ($placeholders) AND carousel_id = ?");
      $stmt->execute([...$toDeleteSlides, $carouselId]);
    }
  }
  echo "<script>alert('✅ Saved successfully for selected language!'); window.location.href = '?lang_id=1';</script>";
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Company Info</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link href="css/admin.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.0/tinymce.min.js"></script>
</head>

<body>
  <?php include 'nav.php'; ?>
  <?php $uploadsPath = '../'; ?>

  <div class="main">
    <div class="form-wrapper">

      <!-- HTML 语言选择器 -->
      <form method="get" id="langForm" class="lang-selector">
        <select name="lang_id" id="langSelect" onchange="document.getElementById('langForm').submit();">
          <?php foreach ($languages as $lang): ?>
            <option value="<?= $lang['id'] ?>" <?= ($lang['id'] == $currentLangId ? 'selected' : '') ?>>
              <?= strtoupper(htmlspecialchars($lang['language'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="language_id" value="<?= htmlspecialchars($currentLangId ?? 1) ?>">
        <input type="hidden" name="carousel_section" id="carousel_section_input"
          value="<?= htmlspecialchars($carouselSectionSelected) ?>">

        <div class="section-card">
          <h2>Navigation Links</h2>
          <?php
          // 把数据库结果转换成 nav_value => nav_name 的键值对
          $dbNavsAssoc = [];
          foreach ($navs as $nav) {
            $dbNavsAssoc[$nav['nav_value']] = $nav['nav_name'];
          }

          // 循环固定顺序，保证 7 个都显示
          foreach ($navValues as $navValue):
            $value = $dbNavsAssoc[$navValue] ?? ''; // 数据库有就显示，没有就空
          ?>
            <div class="input-card">
              <label><?= ucfirst($navValue) ?> Name</label>
              <input type="text" name="nav_name[<?= $navValue ?>]" placeholder="Enter name for <?= ucfirst($navValue) ?>"
                value="<?= htmlspecialchars($value) ?>">
            </div>
          <?php endforeach; ?>
        </div>

        <div class="section-card">
          <h2>SEO</h2>
          <div class="input-card">
            <label>SEO Title</label>
            <input type="text" name="meta_title" placeholder="SEO Title"
              value="<?= htmlspecialchars($existing['meta_title'] ?? '') ?>">
          </div>
          <div class="input-card">
            <label>SEO Description</label>
            <textarea name="meta_description"
              placeholder="SEO Description"><?= htmlspecialchars($existing['meta_description'] ?? '') ?></textarea>
          </div>
          <div class="input-card">
            <label>Header Script</label>
            <textarea name="header_script"
              placeholder="Header Script"><?= htmlspecialchars($existing['header_script'] ?? '') ?></textarea>
          </div>
          <div class="input-card">
            <label>Body Script</label>
            <textarea name="body_script" placeholder="Body Script"
              rows="10"><?= htmlspecialchars($existing['body_script'] ?? '') ?></textarea>
          </div>
          <div class="input-card">
            <label>Footer Script</label>
            <textarea name="footer_script"
              placeholder="Footer Script"><?= htmlspecialchars($existing['footer_script'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="section-card">
          <h2>Company Info</h2>
          <div class="input-card">
            <label>Company Name</label>
            <input type="text" name="name" placeholder="Company Name"
              value="<?= htmlspecialchars($existing['name'] ?? '') ?>">
          </div>
          <div class="input-card">
            <label>Email</label>
            <input type="text" name="email" placeholder="Email"
              value="<?= htmlspecialchars($existing['email'] ?? '') ?>">
          </div>
          <div class="input-card">
            <label>Phone</label>
            <input type="text" name="phone" placeholder="Phone"
              value="<?= htmlspecialchars($existing['phone'] ?? '') ?>">
          </div>
          <div class="input-card">
            <label>Address</label>
            <input type="text" name="address" placeholder="Address"
              value="<?= htmlspecialchars($existing['address'] ?? '') ?>">
          </div>
          <div class="form-grid">
            <div class="input-card">
              <label>Logo</label>
              <?php if (!empty($existing['logo'])): ?>
                <img src="<?= $uploadsPath . $existing['logo'] ?>" class="image-preview">
              <?php endif; ?>
              <input type="file" name="logo" accept="image/*">
              <!-- <button type="button" onclick="removeImagePreview(this)" style="margin-top:8px;">Remove Image</button> -->
            </div>
          </div>
        </div>

        <div class="section-card">
          <h3>Banner Images</h3>
          <div class="form-grid">
            <?php for ($i = 0; $i < 3; $i++): ?>
              <div class="input-card">
                <label>Banner Image <?= $i + 1 ?></label>
                <?php if (!empty($bannerImg[$i]['image'])): ?>
                  <img src="<?= $uploadsPath . $bannerImg[$i]['image'] ?>" class="image-preview">
                <?php endif; ?>
                <input type="file" name="banner_img_<?= $i ?>" accept="image/*">
                <input type="hidden" name="banner_remove_<?= $i ?>" value="0">
                <button type="button"
                  onclick="removeImagePreview(this, null, <?= $i ?>)">
                  Remove Image
                </button>
              </div>
            <?php endfor; ?>
            <div class="input-card">
              <label>Banner Caption</label>
              <input class="wysiwyg" type="text" name="banner_caption"
                value="<?= htmlspecialchars($existing['banner_caption'] ?? '') ?>" placeholder="Banner Caption">
            </div>
          </div>
        </div>

        <div class="section-card">
          <h3>About</h3>
          <div class="input-card">
            <label>About title</label>
            <input class="wysiwyg" type="text" name="about_title"
              value="<?= htmlspecialchars($existing['about_title'] ?? '') ?>" placeholder="About Title">
          </div>
          <div class="input-card">
            <label>About Description</label>
            <textarea class="wysiwyg" name="about_description"
              placeholder="About Description"><?= htmlspecialchars($existing['about_description'] ?? '') ?></textarea>
          </div>
          <div class="form-grid">
            <div class="input-card">
              <label>About Image</label>
              <?php if (!empty($existing['about_image'])): ?>
                <img src="<?= $uploadsPath . $existing['about_image'] ?>" class="image-preview">
              <?php endif; ?>
              <input type="file" name="about_image" accept="image/*">
              <input type="hidden" name="about_image_remove" value="0">
              <button type="button" onclick="removeImagePreview(this, 'about_image_remove')" style="margin-top:8px;">Remove Image</button>
            </div>
          </div>
        </div>

        <div class="section-card">
          <h3>Features</h3>
          <div class="input-card">
            <label>Features Title</label>
            <input class="wysiwyg" type="text" name="features_title"
              value="<?= htmlspecialchars($existing['features_title'] ?? '') ?>" placeholder="Section Title">
          </div>
          <div class="form-grid">
            <?php for ($i = 0; $i < 4; $i++):
              $f = $features[$i] ?? ['id' => '', 'title' => '', 'description' => '', 'icon' => ''];
            ?>
              <div class="input-card">
                <label>Title<?= " " . $i + 1; ?></label>
                <input class="wysiwyg" type="text" name="feature_title[]" value="<?= htmlspecialchars($f['title']) ?>">
                <input type="hidden" name="feature_id[]" value="<?= $f['id'] ?>">
                <input type="hidden" name="feature_existing_icon[]" value="<?= $f['icon'] ?>"><br>

                <label>Description<?= " " . $i + 1; ?></label>
                <input class="wysiwyg" type="text" name="feature_description[]"
                  value="<?= htmlspecialchars($f['description']) ?>"><br>

                <label>Icon<?= " " . $i + 1; ?></label>
                <?php if (!empty($f['icon'])): ?>
                  <img src="<?= $uploadsPath . $f['icon'] ?>" class="image-preview">
                <?php endif; ?>
                <input type="file" name="feature_icon_<?= $i ?>" accept="image/*">
                <button type="button" class="remove-feature-icon-btn" onclick="removeImagePreview(this, 'feature_existing_icon')">Remove Image</button>
              </div>
            <?php endfor; ?>
          </div>
        </div>

        <div class="section-card">
          <h3>Provide</h3>
          <div class="input-card">
            <label>Provide Title</label>
            <input class="wysiwyg" type="text" name="provide_title"
              value="<?= htmlspecialchars($existing['provide_title'] ?? '') ?>" placeholder="Section Title">
          </div>
          <div class="input-card">
            <label>Provide Description</label>
            <textarea class="wysiwyg" name="provide_text"
              placeholder="Section Description"><?= htmlspecialchars($existing['provide_text'] ?? '') ?></textarea>
          </div>

          <div id="provide-container" class="form-grid">
            <?php if (!empty($provides)):
              foreach ($provides as $i => $p): ?>
                <div class="input-card">
                  <label>Title <?= $i + 1 ?></label>
                  <input type="hidden" name="provide_id[]" value="<?= $p['id'] ?? '' ?>">
                  <input class="wysiwyg" type="text" name="provide_title_item[]"
                    value="<?= htmlspecialchars($p['title']) ?>">
                  <input type="hidden" name="provide_existing_icon[]" value="<?= $p['icon'] ?>"><br>

                  <label>Description <?= $i + 1 ?></label>
                  <input class="wysiwyg" type="text" name="provide_description[]"
                    value="<?= htmlspecialchars($p['description']) ?>"><br>

                  <label>Icon <?= $i + 1 ?></label>
                  <?php if (!empty($p['icon'])): ?>
                    <img src="<?= $uploadsPath . $p['icon'] ?>" class="image-preview">
                  <?php endif; ?>
                  <input type="file" name="provide_icon_<?= $i ?>" accept="image/*">
                  <button type="button" class="remove-provide-icon-btn" onclick="removeImagePreview(this, 'provide_existing_icon')">Remove Image</button>
                  <button type="button" class="remove-provide-btn">Remove Provide</button>
                </div>
              <?php endforeach;
            else: ?>
              <!-- If no existing provides -->
              <div class="input-card">
                <label>Title 1</label>
                <input class="wysiwyg" type="text" name="provide_title_item[]" value="">
                <input type="hidden" name="provide_id[]" value="">
                <input type="hidden" name="provide_existing_icon[]" value=""><br>

                <label>Description 1</label>
                <input class="wysiwyg" type="text" name="provide_description[]" value=""><br>

                <label>Icon 1</label>
                <input type="file" name="provide_icon_0" accept="image/*">
                <button type="button" class="remove-provide-icon-btn" onclick="removeImagePreview(this, 'input[name=&quot;provide_existing_icon[]&quot;]')">Remove Image</button>
                <button type="button" class="remove-provide-btn" style="display:none;">Remove Provide</button>
              </div>
            <?php endif; ?>
          </div>
          <button type="button" id="add-provide-btn">Add Provide</button>
        </div>

        <div class="section-card">
          <h3>Gallery Images</h3>
          <div class="input-card">
            <label>Gallery Title</label>
            <input class="wysiwyg" type="text" name="gallery_title"
              value="<?= htmlspecialchars($existing['gallery_title'] ?? '') ?>" placeholder="Gallery Title">
          </div>
          <div id="gallery-container" class="form-grid">
            <?php if (!empty($gallery)): ?>
              <?php foreach ($gallery as $i => $g): ?>
                <div class="input-card">
                  <label>Caption</label>
                  <input class="wysiwyg" type="text" name="gallery_caption[]" value="<?= htmlspecialchars($g['caption'] ?? '') ?>" placeholder="Enter caption">
                  <label>Image <?= $i + 1 ?></label>
                  <?php if (!empty($g['image_path'])): ?>
                    <img src="<?= $uploadsPath . $g['image_path'] ?>" class="image-preview">
                  <?php endif; ?>
                  <input type="hidden" name="gallery_id[]" value="<?= $g['id'] ?? '' ?>">
                  <input type="file" name="gallery[]" accept="image/*">
                  <button type="button" class="remove-img-btn" style="<?= (count($gallery) > 1) ? 'display:inline-block' : 'display:none' ?>">Remove Image</button>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <!-- If no images yet, show at least one box -->
              <div class="input-card">
                <label>Caption</label>
                <input class="wysiwyg" type="text" name="gallery_caption[]" placeholder="Enter caption">
                <label>Image 1</label>
                <input type="file" name="gallery[]" accept="image/*">
                <button type="button" class="remove-img-btn" style="display:none;">Remove Image</button>
              </div>
            <?php endif; ?>
          </div>
          <button type="button" id="add-img-btn">Add Gallery</button>
        </div>

        <div class="section-card">
          <h3>Blogs</h3>

          <div class="input-card">
            <label>Blog Title</label>
            <input class="wysiwyg" type="text" name="blog_title"
              value="<?= htmlspecialchars($existing['blog_title'] ?? '') ?>" placeholder="Enter Blog Title">
          </div>

          <div class="input-card">
            <label>Blog Sub Title</label>
            <input class="wysiwyg" type="text" name="blog_sub_title"
              value="<?= htmlspecialchars($existing['blog_sub_title'] ?? '') ?>" placeholder="Enter Blog Sub Title">
          </div>
        </div>

        <!--<div class="section-card">-->
        <!--  <h3>Video Links</h3>-->
        <!--  <div class="input-card">-->
        <!--    <label>Video Section Title</label>-->
        <!--    <input class="wysiwyg" type="text" name="video_title" value="<?= htmlspecialchars($existing['video_title'] ?? '') ?>" placeholder="Video Title">-->
        <!--  </div>-->
        <!--  <div class="form-grid">-->
        <!--    <?php for ($i = 0; $i < 3; $i++): ?>-->
        <!--      <div class="input-card">-->
        <!--        <label>Video Title <?= $i + 1 ?></label>-->
        <!--        <input type="text" name="title[<?= $i ?>]" value="<?= htmlspecialchars($videos[$i]['title'] ?? '') ?>">-->
        <!--      </div>-->
        <!--      <div class="input-card">-->
        <!--        <label>Video Link <?= $i + 1 ?></label>-->
        <!--        <input type="hidden" name="video_id[<?= $i ?>]" value="<?= $videos[$i]['id'] ?? '' ?>">-->
        <!--        <input type="text" name="video_link[<?= $i ?>]" value="<?= htmlspecialchars($videos[$i]['video_link'] ?? '') ?>">-->
        <!--      </div>-->
        <!--    <?php endfor; ?>-->
        <!--  </div>-->
        <!--</div>-->
        <div class="section-card">
          <h3>Video Links</h3>
          <div class="input-card">
            <label>Video Section Title</label>
            <input class="wysiwyg" type="text" name="video_title"
              value="<?= htmlspecialchars($existing['video_title'] ?? '') ?>" placeholder="Video Title">
          </div>

          <div id="video-list">
            <?php foreach ($videos as $i => $vid): ?>
              <div class="video-entry">
                <input type="hidden" name="video_id[<?= $i ?>]" value="<?= $vid['id'] ?>">
                <div class="input-card">
                  <label>Video Title <?= $i + 1 ?></label>
                  <input type="text" name="title[<?= $i ?>]" value="<?= htmlspecialchars($vid['title'] ?? '') ?>">
                  <label>Video Type</label>
                  <select class="video-type" name="video_type[<?= $i ?>]">
                    <option value="link" <?= ($vid['type'] ?? 'link') === 'link' ? 'selected' : '' ?>>Link</option>
                    <option value="file" <?= ($vid['type'] ?? '') === 'file' ? 'selected' : '' ?>>Upload File (Max 15MB)
                    </option>
                  </select>
                  <div class="input-card video-link-field"
                    style="<?= ($vid['type'] ?? 'link') === 'file' ? 'display:none;' : '' ?>">
                    <label>Video Link</label>
                    <input type="text" name="video_link[<?= $i ?>]"
                      value="<?= htmlspecialchars($vid['video_link'] ?? '') ?>">
                  </div>
                  <div class="video-file-field" style="<?= ($vid['type'] ?? '') === 'file' ? '' : 'display:none;' ?>">
                    <input type="file" accept="video/*" name="video_file[<?= $i ?>]">
                    <?php if (!empty($vid['video_file'])): ?>
                      <p>Current file: <a href="<?= "https://{$domain}/" . htmlspecialchars($vid['video_file']) ?>"
                          target="_blank">View</a></p>
                    <?php endif; ?>
                  </div>
                  <button type="button" class="remove-vid-btn">Remove</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" id="add-vid-btn">Add Video</button>
        </div>


        <div class="section-card">
          <h3>Social Links</h3>
          <div id="social-container" class="form-grid">
            <?php if (!empty($socials)):
              foreach ($socials as $i => $s): ?>
                <div class="input-card">
                  <label>Name</label>
                  <input type="text" name="social_name[]" value="<?= htmlspecialchars($s['name']) ?>">
                  <input type="hidden" name="social_id[]" value="<?= $s['id'] ?>">
                  <input type="hidden" name="social_existing_icon[]" value="<?= $s['icon_path'] ?>">

                  <label>Link</label>
                  <input type="text" name="social_link[]" value="<?= htmlspecialchars($s['link_url']) ?>">

                  <label>Icon</label>
                  <?php if (!empty($s['icon_path'])): ?>
                    <img src="<?= $uploadsPath . $s['icon_path'] ?>" class="image-preview">
                  <?php endif; ?>
                  <input type="file" name="social_icon_<?= $i ?>" accept="image/*">
                  <button type="button" class="remove-social-icon-btn" onclick="removeImagePreview(this, 'social_existing_icon')">Remove Image</button>
                  <button type="button" class="remove-social-btn">Remove Social Link</button>
                </div>
              <?php endforeach;
            else: ?>
              <!-- If no existing socials -->
              <div class="input-card">
                <label>Name</label>
                <input type="text" name="social_name[]" value="">
                <input type="hidden" name="social_id[]" value="">
                <input type="hidden" name="social_existing_icon[]" value="">

                <label>Link</label>
                <input type="text" name="social_link[]" value="">

                <label>Icon</label>
                <input type="file" name="social_icon_0" accept="image/*">
                <button type="button" class="remove-social-icon-btn" onclick="removeImagePreview(this, 'social_existing_icon')">Remove Image</button>
                <button type="button" class="remove-social-btn" style="display:none;">Remove Social Link</button>
              </div>
            <?php endif; ?>
          </div>
          <button type="button" id="add-social-btn">Add Social Link</button>
        </div>

        <div class="section-card">
          <h3>Subnavigation</h3>
          <div class="form-grid" id="subnav-container">
            <?php foreach ($subnavs as $i => $subnav): ?>
              <div class="subnav-row">
                <div class="input-card">
                  <label>Subnav Option <?= $i + 1 ?></label>
                  <input type="text" name="subnav_name[]" value="<?= htmlspecialchars($subnav['subnav_name']) ?>">
                  <label>Subnav Link <?= $i + 1 ?></label>
                  <input type="hidden" name="subnav_id[]" value="<?= $subnav['id'] ?>">
                  <input type="text" name="subnav_link[]" value="<?= htmlspecialchars($subnav['subnav_link']) ?>">
                  <button type="button" class="remove-subnav">Remove</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" id="add-subnav">Add Subnavigation Option</button>
        </div>

        <div class="section-card">
          <h3>PDF Files</h3>
          <div class="input-card">
            <label>PDF Section Title</label>
            <input class="wysiwyg" type="text" name="pdfsectiontitle"
              value="<?= htmlspecialchars($existing['pdf_title'] ?? '') ?>" placeholder="Enter PDF Section Title">
          </div>
          <div id="pdf-container" class="form-grid">
            <?php if (!empty($pdfs)): ?>
              <?php foreach ($pdfs as $i => $pdf): ?>
                <div class="input-card">
                  <label>PDF Title <?= $i + 1 ?></label>
                  <input type="text" name="pdf_title[]" value="<?= htmlspecialchars($pdf['title']) ?>" placeholder="PDF Title">
                  <input type="hidden" name="pdf_id[]" value="<?= htmlspecialchars($pdf['id']) ?>">
                  <input type="hidden" name="pdf_existing_file[]" value="<?= htmlspecialchars($pdf['pdf_file']) ?>">
                  
                  <label>PDF File <?= $i + 1 ?></label>
                  <?php if (!empty($pdf['pdf_file'])): ?>
                    <p><a href="<?= htmlspecialchars($uploadsPath . $pdf['pdf_file']) ?>" target="_blank">View Current PDF</a></p>
                  <?php endif; ?>
                  <input type="file" name="pdf_file_<?= $i ?>" accept=".pdf">
                  <button type="button" class="remove-pdf-btn" onclick="removePdf(this, <?= $i ?>)">Remove PDF</button>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="input-card">
                <label>PDF Title 1</label>
                <input type="text" name="pdf_title[]" value="" placeholder="PDF Title">
                <input type="hidden" name="pdf_id[]" value="">
                <input type="hidden" name="pdf_existing_file[]" value="">
                
                <label>PDF File 1</label>
                <input type="file" name="pdf_file_0" accept=".pdf">
                <button type="button" class="remove-pdf-btn" onclick="removePdf(this, 0)" style="display:none;">Remove PDF</button>
              </div>
            <?php endif; ?>
          </div>
          <button type="button" id="add-pdf-btn">Add PDF</button>
        </div>

        <div class="section-card carousel-section">
          <h3>Carousel</h3>
          <div class="input-card">
            <label>Select Section</label>
            <?php $carouselSections = ['about', 'features', 'provide', 'gallery', 'video']; ?>
            <select id="carouselSectionSelect"
              onchange="location.href='?lang_id=<?= $currentLangId ?>&carousel_section='+this.value">
              <?php foreach ($carouselSections as $sec): ?>
                <option value="<?= htmlspecialchars($sec) ?>" <?= ($sec === $carouselSectionSelected ? 'selected' : '') ?>>
                  <?= ucfirst(htmlspecialchars($sec)) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small style="display:block;margin-top:6px;color:#666;">Select section to load the carousel for that
              section.</small>
          </div>
          <div class="input-card">
            <label>Carousel Title</label>
            <input class="wysiwyg" type="text" name="carousel_title"
              value="<?= htmlspecialchars($carousel['title'] ?? '') ?>" placeholder="Carousel Title">
          </div>
          <div id="slides-container" class="form-grid">
            <?php if (!empty($carouselSlides)): ?>
              <?php foreach ($carouselSlides as $i => $s): ?>
                <div class="slide-card input-card">
                  <input type="hidden" name="slide_id[]" value="<?= htmlspecialchars($s['id']) ?>">
                  <label>Slide <?= $i + 1 ?> Title</label>
                  <input class="wysiwyg" type="text" name="slide_title[]" value="<?= htmlspecialchars($s['title']) ?>">
                  <label>Slide <?= $i + 1 ?> Text</label>
                  <textarea class="wysiwyg" name="slide_text[]"><?= htmlspecialchars($s['text']) ?></textarea>
                  <label>Icon</label>
                  <?php if (!empty($s['icon'])): ?>
                    <img src="<?= $uploadsPath . htmlspecialchars($s['icon']) ?>" class="image-preview">
                  <?php endif; ?>
                  
                  <input type="hidden" name="slide_existing_icon[]" value="<?= htmlspecialchars($s['icon']) ?>">
                  <input type="file" name="slide_icon[]" accept="image/*">
                  <button type="button" class="delete-icon-btn" onclick="removeImagePreview(this, 'input[name=&quot;slide_existing_icon[]&quot;]')">Remove Image</button>
                  <button type="button" class="remove-slide" onclick="this.closest('.slide-card').remove()">Remove Slides</button>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <!-- Show one empty slide card if none exist yet -->
              <div class="slide-card input-card">
                <input type="hidden" name="slide_id[]" value="">
                <label>Slide 1 Title</label>
                <input class="wysiwyg" type="text" name="slide_title[]" value="">
                <label>Slide 1 Text</label>
                <textarea class="wysiwyg" name="slide_text[]"></textarea>
                <label>Icon</label>
                <input type="hidden" name="slide_existing_icon[]" value="">
                <input type="file" name="slide_icon[]" accept="image/*">
                <button type="button" class="delete-icon-btn" onclick="removeImagePreview(this, 'input[name=&quot;slide_existing_icon[]&quot;]')">Remove Image</button>
                <button type="button" class="remove-slide" style="display:none;" onclick="this.closest('.slide-card').remove()">Remove Slides</button>
              </div>
            <?php endif; ?>
          </div>
          <button type="button" id="add-slide">Add Slide</button>
        </div>

        <button type="submit">Save Company</button>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      tinymce.init({
        selector: '.wysiwyg',
        height: 200,
        menubar: false,
        plugins: 'lists link image code wordcount textcolor colorpicker',
        toolbar: 'undo redo | customfontsize | fontsizeselect | forecolor | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link | removeformat | code',
        setup: function(editor) {
          editor.ui.registry.addButton('customfontsize', {
            text: 'Custom Font Size',
            onAction: function() {
              const size = prompt('Enter font size (e.g. 15px, 1.2em, 120%)');
              if (size) {
                editor.execCommand('FontSize', false, size);
              }
            }
          });
        },
        branding: false,
        fontsize_formats: '10px 12px 14px 16px 18px 20px 22px 24px 26px 28px 30px 32px 34px 36px'
      });
    });
  </script>

  <script>
    const container = document.getElementById('gallery-container');
    const addBtn = document.getElementById('add-img-btn');

    function renumberGallery() {
      Array.from(container.children).forEach((card, index) => {
        const label = card.querySelector('label');
        label.textContent = 'Image ' + (index + 1);
        const removeBtn = card.querySelector('.remove-img-btn');
        removeBtn.style.display = (container.children.length > 1) ? 'inline-block' : 'none';
      });
    }

    addBtn.addEventListener('click', () => {
      const div = document.createElement('div');
      div.className = 'input-card';
      div.innerHTML = `
            <label>Image ${container.children.length + 1}</label>
            <input type="file" name="gallery[]" accept="image/*">
            <button type="button" class="remove-img-btn">Remove Image</button>
        `;
      container.appendChild(div);
      renumberGallery();
    });

    container.addEventListener('click', function(e) {
      if (e.target.classList.contains('remove-img-btn')) {
        e.target.closest('.input-card').remove();
        renumberGallery();
      }
    });
  </script>

  <script>
    const provideContainer = document.getElementById('provide-container');
    const addProvideBtn = document.getElementById('add-provide-btn');

    function renumberProvide() {
      Array.from(provideContainer.children).forEach((card, index) => {
        const labels = card.querySelectorAll('label');
        labels[0].textContent = 'Title ' + (index + 1);
        labels[1].textContent = 'Description ' + (index + 1);

        const removeBtn = card.querySelector('.remove-provide-btn');
        removeBtn.style.display = (provideContainer.children.length > 1) ? 'inline-block' : 'none';

        // Update file input name dynamically
        const fileInput = card.querySelector('input[type="file"]');
        fileInput.name = 'provide_icon_' + index;
      });
    }

    addProvideBtn.addEventListener('click', () => {
      const div = document.createElement('div');
      div.className = 'input-card';
      const provideIndex = provideContainer.children.length;
      div.innerHTML = `
            <label>Title</label>
            <input class="wysiwyg" type="text" name="provide_title_item[]" value="">
            <input type="hidden" name="provide_id[]" value="">
            <input type="hidden" name="provide_existing_icon[]" value=""><br>

            <label>Description</label>
            <input class="wysiwyg" type="text" name="provide_description[]" value=""><br>

            <label>Icon</label>
            <input type="file" name="provide_icon_${provideIndex}" accept="image/*">
            <button type="button" class="remove-provide-icon-btn" onclick="removeImagePreview(this, 'input[name=&quot;provide_existing_icon[]&quot;]')">Remove Image</button>
            <button type="button" class="remove-provide-btn">Remove Provide</button>
        `;
      provideContainer.appendChild(div);
      renumberProvide();
    });

    provideContainer.addEventListener('click', (e) => {
      if (e.target.classList.contains('remove-provide-btn')) {
        e.target.closest('.input-card').remove();
        renumberProvide();
      }
    });
  </script>

  <script>
    const socialContainer = document.getElementById('social-container');
    const addSocialBtn = document.getElementById('add-social-btn');

    function renumberSocial() {
      Array.from(socialContainer.children).forEach((card, index) => {
        const removeBtn = card.querySelector('.remove-social-btn');
        removeBtn.style.display = (socialContainer.children.length > 1) ? 'inline-block' : 'none';

        // Update file input name dynamically
        const fileInput = card.querySelector('input[type="file"]');
        fileInput.name = 'social_icon_' + index;
      });
    }

    addSocialBtn.addEventListener('click', () => {
      const div = document.createElement('div');
      div.className = 'input-card';
      const socialIndex = socialContainer.children.length;
      div.innerHTML = `
            <label>Name</label>
            <input type="text" name="social_name[]" value="">
            <input type="hidden" name="social_id[]" value="">
            <input type="hidden" name="social_existing_icon[]" value="">

            <label>Link</label>
            <input type="text" name="social_link[]" value="">

            <label>Icon</label>
            <input type="file" name="social_icon_${socialIndex}" accept="image/*">
            <button type="button" class="remove-social-icon-btn" onclick="removeImagePreview(this, 'social_existing_icon')">Remove Image</button>
            <button type="button" class="remove-social-btn">Remove Social Link</button>
        `;
      socialContainer.appendChild(div);
      renumberSocial();
    });

    socialContainer.addEventListener('click', (e) => {
      if (e.target.classList.contains('remove-social-btn')) {
        e.target.closest('.input-card').remove();
        renumberSocial();
      }
    });
  </script>

  <script>
    document.getElementById('add-subnav').addEventListener('click', function() {
      const container = document.getElementById('subnav-container');
      const div = document.createElement('div');
      div.classList.add('subnav-row');
      div.innerHTML = `
      <div class="input-card">
        <label>Subnav Option</label>
        <input type="text" name="subnav_name[]" value="">
      </div>
      <div class="input-card">
        <label>Subnav Link</label>
        <input type="hidden" name="subnav_id[]" value="">
        <input type="text" name="subnav_link[]" value="">
      </div>
      <button type="button" class="remove-subnav">Remove</button>
  `;
      container.appendChild(div);
    });

    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('remove-subnav')) {
        e.target.closest('.subnav-row').remove();
      }
    });
  </script>

  <script>
    (function() {
      const slidesContainer = document.getElementById('slides-container');
      const addSlideBtn = document.getElementById('add-slide');

      function renumberSlides() {
        const cards = slidesContainer.querySelectorAll('.slide-card');
        cards.forEach((card, idx) => {
          const labels = card.querySelectorAll('label');
          if (labels[0]) labels[0].textContent = 'Slide ' + (idx + 1) + ' Title';
          if (labels[1]) labels[1].textContent = 'Slide ' + (idx + 1) + ' Text';
          const btn = card.querySelector('.remove-slide');
          if (!btn) return;
          btn.style.display = cards.length > 1 ? 'inline-block' : 'none';
        });
      }

      // function initWysiwyg(selector = '.wysiwyg') {
      //   tinymce.remove('.wysiwyg');
      //   tinymce.init({
      //     selector: '.wysiwyg',
      //     height: 200,
      //     menubar: false,
      //     plugins: 'lists link image code wordcount textcolor colorpicker',
      //     toolbar: 'undo redo | fontsizeinput | fontsizeselect | forecolor | bold italic underline | bullist numlist | link | removeformat | code',
      //     branding: false,
      //     fontsize_formats: '10px 12px 14px 16px 18px 20px 22px 24px 26px 28px 30px 32px 34px 36px'
      //   });
      // }

      addSlideBtn.addEventListener('click', function() {
        const index = slidesContainer.children.length + 1;
        const div = document.createElement('div');
        div.className = 'slide-card';
        div.innerHTML = `
          <input type="hidden" name="slide_id[]" value="">
          <label>Slide ${index} Title</label>
          <input class="wysiwyg" type="text" name="slide_title[]" value="">
          <label>Slide ${index} Text</label>
          <textarea class="wysiwyg" name="slide_text[]"></textarea>
          <label>Icon</label>
          <input type="hidden" name="slide_existing_icon[]" value="">
          <input type="file" name="slide_icon[]" accept="image/*">
          <button type="button" class="delete-icon-btn" onclick="removeImagePreview(this, 'input[name=&quot;slide_existing_icon[]&quot;]')">Remove Image</button>
          <button type="button" class="remove-slide">Remove Slides</button>
        `;
        slidesContainer.appendChild(div);
        renumberSlides();
        initWysiwyg('#slides-container .slide-card:last-child .wysiwyg');
      });

      document.addEventListener('click', function(e) {
        if (e.target.matches('.remove-slide')) {
          const card = e.target.closest('.slide-card');
          if (card) card.remove();
          renumberSlides();
        }
      });

      // Initial renumber & init all existing WYSIWYG fields
      renumberSlides();
      initWysiwyg();
    })();
  </script>


  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const videoList = document.getElementById("video-list");
      const addBtn = document.getElementById("add-vid-btn");

      // Update all name attributes based on current index
      function updateIndices() {
        videoList.querySelectorAll(".video-entry").forEach((entry, i) => {
          entry.querySelector('input[name^="video_id"]').name = `video_id[${i}]`;
          entry.querySelector('input[name^="title"]').name = `title[${i}]`;
          entry.querySelector('select[name^="video_type"]').name = `video_type[${i}]`;
          entry.querySelector('input[name^="video_link"]').name = `video_link[${i}]`;
          const fileInput = entry.querySelector('input[type="file"]');
          if (fileInput) fileInput.name = `video_file[${i}]`;
        });
      }

      // Toggle link/file input on type change
      videoList.addEventListener("change", function(e) {
        if (e.target.classList.contains("video-type")) {
          const entry = e.target.closest(".video-entry");
          entry.querySelector(".video-link-field").style.display = e.target.value === "link" ? "" : "none";
          entry.querySelector(".video-file-field").style.display = e.target.value === "file" ? "" : "none";
        }
      });

      // Remove entry
      videoList.addEventListener("click", function(e) {
        if (e.target.classList.contains("remove-vid-btn")) {
          e.target.closest(".video-entry").remove();
          updateIndices();
        }
      });

      // Add new entry
      addBtn.addEventListener("click", function() {
        const newEntry = document.createElement("div");
        newEntry.classList.add("video-entry");
        newEntry.innerHTML = `
      <input type="hidden" name="video_id[]" value="">
      <div class="input-card">
        <label>Video Title</label>
        <input type="text" name="title[]">
        <label>Video Type</label>
        <select name="video_type[]" class="video-type">
          <option value="link" selected>Link</option>
          <option value="file">Upload File</option>
        </select>
        <div class="input-card video-link-field">
          <label>Video Link</label>
          <input type="text" name="video_link[]">
        </div>
        <div class="video-file-field" style="display:none;">
          <input type="file" accept="video/*" name="video_file[]">
        </div>
        <button type="button" class="remove-vid-btn">Remove</button>
      </div>
    `;
        videoList.appendChild(newEntry);
        updateIndices();
      });

      // Initialize indices on page load
      updateIndices();
    });

    // 通用的remove image函数
    function removeImagePreview(btn, hiddenInputSelector = null, bannerIndex = null) {
      const container = btn.closest('.input-card, .slide-card');

      // 对于数组型hidden inputs (feature_existing_icon等)，标记为删除
      if (hiddenInputSelector && (hiddenInputSelector === 'feature_existing_icon' || hiddenInputSelector === 'provide_existing_icon' || hiddenInputSelector === 'social_existing_icon')) {
        const hiddenInput = container.querySelector(`input[name="${hiddenInputSelector}[]"]`);
        if (hiddenInput) hiddenInput.value = '__DELETE__';
      }
      
      // 如果传入了hidden input name (用于About Image等)，设置其值为1
      else if (hiddenInputSelector && typeof hiddenInputSelector === 'string' && hiddenInputSelector !== 'null') {
        const hiddenInput = container.querySelector(`input[name="${hiddenInputSelector}"]`);
        if (hiddenInput) hiddenInput.value = '1';
      }

      // 对于banner，设置remove flag
      if (bannerIndex !== null) {
        const removeFlag = document.querySelector(`input[name="banner_remove_${bannerIndex}"]`);
        if (removeFlag) removeFlag.value = "1";
      }

      // 清空 file input
      const fileInput = container.querySelector('input[type="file"]');
      if (fileInput) fileInput.value = '';

      // 删除 preview
      const img = container.querySelector('.image-preview');
      if (img) img.remove();
    }

    // PDF管理脚本
    document.getElementById('add-pdf-btn').addEventListener('click', function() {
      const container = document.getElementById('pdf-container');
      const div = document.createElement('div');
      div.className = 'input-card';
      const pdfIndex = container.children.length;
      div.innerHTML = `
        <label>PDF Title ${pdfIndex + 1}</label>
        <input type="text" name="pdf_title[]" value="" placeholder="PDF Title">
        <input type="hidden" name="pdf_id[]" value="">
        <input type="hidden" name="pdf_existing_file[]" value="">
        
        <label>PDF File ${pdfIndex + 1}</label>
        <input type="file" name="pdf_file_${pdfIndex}" accept=".pdf">
        <button type="button" class="remove-pdf-btn">Remove PDF</button>
      `;
      container.appendChild(div);
      renumberPDFs();
    });

    function renumberPDFs() {
      const container = document.getElementById('pdf-container');
      Array.from(container.children).forEach((card, index) => {
        const labels = card.querySelectorAll('label');
        labels[0].textContent = 'PDF Title ' + (index + 1);
        if (labels[1]) labels[1].textContent = 'PDF File ' + (index + 1);
        
        const removeBtn = card.querySelector('.remove-pdf-btn');
        removeBtn.style.display = (container.children.length > 1) ? 'inline-block' : 'none';
        
        // Update file input name dynamically
        const fileInput = card.querySelector('input[type="file"]');
        if (fileInput) fileInput.name = 'pdf_file_' + index;
      });
    }

    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('remove-pdf-btn')) {
        e.target.closest('.input-card').remove();
        renumberPDFs();
      }
    });

    function removePdf(btn, index) {
      btn.closest('.input-card').remove();
      renumberPDFs();
    }
  </script>


</body>

</html>