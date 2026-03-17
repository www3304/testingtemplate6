<?php
if (!isset($_SESSION)) session_start();
include '../config.php';

// 1. Check Login & Role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 2. Get Domain and Prefix (Standard Company Logic)
$host = $_SERVER['HTTP_HOST'];
$domain = str_replace('www.', '', strtolower(trim($host)));

$stmt = $pdo->prepare("SELECT company_name FROM domain_list WHERE domain_name = ?");
$stmt->execute([$domain]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("<div style='padding:2rem; color:red; text-align:center;'>❌ Domain not found in domain_list.</div>");
}

$prefix = $row['company_name'] . "_";

// 3. Handle POST Request (Saving the Data)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Convert 'Active/Inactive' from the dropdown back to 'on/off' for the database
    $ecommerce_status = ($_POST['show_ecommerce'] === 'Active') ? 'on' : 'off';
    
    try {
        $updateStmt = $pdo->prepare("UPDATE {$prefix}companyInfo SET show_ecommerce = ? WHERE domain = ?");
        $updateStmt->execute([$ecommerce_status, $domain]);
        
        echo "<script>alert('✅ Module settings saved successfully!'); window.location.href='module_control.php';</script>";
        exit;
    } catch (Exception $e) {
        echo "<script>alert('❌ Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// 4. Fetch Current Status
try {
    $infoStmt = $pdo->prepare("SELECT show_ecommerce FROM {$prefix}companyInfo WHERE domain = ? LIMIT 1");
    $infoStmt->execute([$domain]);
    $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
    $current_ecommerce = ($info['show_ecommerce'] ?? 'on') === 'on' ? 'Active' : 'Inactive';
} catch (Exception $e) {
    // If column doesn't exist yet, default to Active
    $current_ecommerce = 'Active'; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* Base Layout matching your admin panel */
        body { margin: 0; background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #1f2937; }
        .main { padding: 2rem; padding-left: 270px; min-height: 100vh; }
        
        .page-title { text-align: center; font-size: 1.5rem; font-weight: 600; margin-bottom: 2rem; color: #111827; }
        
        /* Card Design matching 'Section Management' */
        .card-container { max-width: 800px; margin: 0 auto; }
        .module-card { background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        
        .module-card label { display: block; font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; color: #374151; }
        
        /* The gray tag design for the Section Key */
        .section-key-tag { background-color: #f3f4f6; border: 1px dashed #d1d5db; color: #6b7280; padding: 0.3rem 0.8rem; border-radius: 4px; font-size: 0.85rem; display: inline-block; margin-bottom: 1.2rem; cursor: default; }
        
        /* The dropdown design */
        .status-select { width: 100%; padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 4px; background-color: #fff; font-size: 0.95rem; color: #1f2937; outline: none; }
        .status-select:focus { border-color: #3b82f6; }
        
        /* Save Button */
        .save-btn-wrapper { text-align: right; margin-top: 1rem; }
        .save-btn { background-color: #1f2937; color: #fff; border: none; padding: 0.7rem 1.5rem; border-radius: 4px; font-size: 0.95rem; font-weight: 500; cursor: pointer; transition: background-color 0.2s; }
        .save-btn:hover { background-color: #374151; }

        @media (max-width: 768px) {
            .main { padding-left: 1rem; padding-right: 1rem; padding-top: 5rem; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="main">
        <h2 class="page-title">Module Control</h2>
        
        <div class="card-container">
            <form method="POST">
                
                <div class="module-card">
                    <label>Module Key:</label>
                    <div class="section-key-tag">E-COMMERCE</div>
                    
                    <label>Status:</label>
                    <select name="show_ecommerce" class="status-select">
                        <option value="Active" <?= $current_ecommerce === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $current_ecommerce === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="save-btn-wrapper">
                    <button type="submit" class="save-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>