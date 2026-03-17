<?php
session_start();
include '../config.php'; // 数据库连接

$message = '';
$editMode = false;
$editUser = null;

//fetch domain list
$domainList = $pdo->prepare("SELECT * FROM domain_list ORDER BY domain_name ASC");
$domainList->execute();
$domains = $domainList->fetchAll(PDO::FETCH_ASSOC);

//fetch domain names with existing domain users
$domainU = $pdo->prepare("SELECT domain_name FROM domain_users");
$domainU->execute();
$domainsWAdmin = $domainU->fetchAll(PDO::FETCH_COLUMN);

// 删除用户
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM domain_users WHERE id = ?");
    $stmt->execute([$deleteId]);
    $message = "🗑 User deleted successfully.";
}

// 编辑准备
if (isset($_GET['edit'])) {
    $editMode = true;
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM domain_users WHERE id = ?");
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$editUser) {
        $editMode = false;
        $message = "❌ User not found.";
    }
}

// 提交（新增或更新）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = trim($_POST['domain']);
    $user_id = trim($_POST['user_id']);
    $role = trim($_POST['role']);
    $password = $_POST['password'];
    $id = $_POST['id'] ?? '';

    if ($domain && $user_id && $role) {
        if ($id) {
            // 更新用户
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE domain_users SET domain_name=?, user_id=?, role=?, password=? WHERE id=?");
                $stmt->execute([$domain, $user_id, $role, $hashedPassword, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE domain_users SET domain_name=?, user_id=?, role=? WHERE id=?");
                $stmt->execute([$domain, $user_id, $role, $id]);
            }
            $message = "✏️ User updated successfully.";
        } else {
            $exists = false;
            foreach($domainsWAdmin as $domainWAdmin){
                if($domainWAdmin == $domain){
                  $message = "Admin creation abolished.<br>(Admin already exists for domain {$domain})";
                  $exists = true;
                  break;   
                }
            }
            if(!$exists){
              if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO domain_users (domain_name, user_id, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$domain, $user_id, $hashedPassword, $role]);
                $message = "✅ <strong>$role</strong> <strong>$user_id</strong> created for domain <strong>$domain</strong>.";
              } else {
                $message = "❌ Password is required for new users.";
              }   
            }
        }
    } else {
        $message = "❌ All fields are required.";
    }

    // 防止重复提交
    $_SESSION['message'] = $message;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Domain Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .main {
            padding: 2rem;
            padding-left: 270px;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .main {
                padding-left: 1rem;
                padding-right: 1rem;
                padding-top: 5rem;
            }
        }

        .form-box {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }
        @media(min-width: 769px){
            .form-box{max-width: 100%;}
        }
        
        .form-box h2 {
            margin-bottom: 1.5rem;
            color: #1f2937;
            border-left: 4px solid #3b82f6;
            padding-left: 0.75rem;
            font-size: 1.5rem;
        }

        .form-box input[type="text"],
        .form-box input[type="password"],
        .form-box select {
            width: 100%;
            padding: 0.75rem 1rem;
            margin-bottom: 1.2rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background-color: #f9fafb;
            font-size: 1rem;
        }

        .form-box button {
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

        .form-box button:hover {
            background-color: #2563eb;
        }

        .message {
            margin-top: 1rem;
            padding: 0.75rem 1rem;
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            font-size: 0.95rem;
        }

        .user-table th,
        .user-table td {
            border: 1px solid #e5e7eb;
            padding: 0.75rem 1rem;
            text-align: left;
            white-space: nowrap; 
        }

        .user-table th {
            background-color: #f1f5f9;
            color: #374151;
            font-weight: 600;
        }

        .user-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .action-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            margin: 0 0.25rem;
        }

        .action-link:hover {
            text-decoration: underline;
        }

        .table-wrapper {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 400px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-top: 1.5rem;
        }
    </style>
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="main">
        <div class="form-box">
            <h2><?= $editMode ? "Edit User" : "Create Admin User" ?></h2>
            <form method="post">
                <?php if ($editMode): ?>
                    <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                <?php endif; ?>
                <!--<input type="text" name="domain" placeholder="Domain (e.g. example.com)" value="<?= htmlspecialchars($editUser['domain_name'] ?? '') ?>" required>-->
                <select name="domain">
                  <?php if ($editMode): ?>
                    <option selected value= "<?= htmlspecialchars($editUser['domain_name']) ?>"><?= htmlspecialchars($editUser['domain_name'])?></option>
                  <?php else: ?>
                    <option selected value="">Select a domain</option>
                  <?php endif; ?>
                  <?php foreach ($domains as $domain) :
                    if($domain['domain_name'] != $editUser['domain_name']): ?>
                      <option value= "<?= htmlspecialchars($domain['domain_name']) ?>"><?= $domain['domain_name']?></option>
                    <?php endif;
                  endforeach ?>
                </select>
                <input type="text" name="user_id" placeholder="User ID (Login)" value="<?= htmlspecialchars($editUser['user_id'] ?? '') ?>" required>
                <select name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin" <?= (isset($editUser) && $editUser['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="superadmin" <?= (isset($editUser) && $editUser['role'] === 'superadmin') ? 'selected' : '' ?>>Super Admin</option>
                </select>
                <input type="password" name="password" placeholder="<?= $editMode ? 'Leave blank to keep current password' : 'Password' ?>" <?= $editMode ? '' : 'required' ?>>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit"><?= $editMode ? 'Update User' : 'Create Admin' ?></button>
                    <?php if ($editMode): ?>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" style="background-color: #f87171; color:white; text-decoration:none; padding:0.75rem 2rem; font-size:1rem; font-weight:600; border-radius:8px; display:inline-block;">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($message): ?>
                <div class="message"><?= $message ?></div>
            <?php elseif (!empty($_SESSION['message'])) : ?>
               <div class="message"><?= $_SESSION['message']?></div>
               <?php unset($_SESSION['message']);?>
            <?php endif; ?>

            <h2 style="margin-top:3rem;">Existing Users</h2>
            <div class="table-wrapper">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>User ID</th>
                            <th>Role</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM domain_users ORDER BY domain_name, user_id");
                        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['domain_name']) ?></td>
                                <td><?= htmlspecialchars($user['user_id']) ?></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td style="text-align:center;">
                                    <a href="?edit=<?= $user['id'] ?>" class="action-link">✏️ Edit</a> |
                                    <a href="?delete=<?= $user['id'] ?>" class="action-link" onclick="return confirm('Are you sure you want to delete this user?')">🗑 Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>