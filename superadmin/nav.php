<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['user'])) {
  header('Location: login.php');
  exit;
}
?>

<!-- Mobile 顶部按钮 -->
<div class="mobile-topbar">
  <div class="toggle-btn" onclick="toggleSidebar()">☰</div>
</div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <h2>Menu</h2>
  <ul>
    <li><a href="superAdmin.php">🏠 Dashboard</a></li>
    <li><a href="createAdmin.php">👑 Create Admin</a></li>
    <li><a href="logout.php" onclick="return confirm('Are you sure to logout?')">🚪 Logout</a></li>
  </ul>
  <div style="padding: 1rem; font-size: 0.85rem; color: #bbb;">
    Role: <?= htmlspecialchars($_SESSION['user']['role']) ?>
  </div>
</div>

<style>
  /* ===== RESET ===== */
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  /* ===== Sidebar (Desktop 默认显示) ===== */
  .sidebar {
    width: 250px;
    background-color: #1f2937;
    color: #fff;
    font-family: 'Inter', sans-serif;
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    z-index: 1000;
    transition: transform 0.3s ease-in-out;
    transform: translateX(0);
  }

  .sidebar h2 {
    font-size: 1.5rem;
    padding: 1.2rem 1rem;
    border-bottom: 1px solid #374151;
  }

  .sidebar ul {
    list-style: none;
  }

  .sidebar ul li {
    border-bottom: 1px solid #374151;
  }

  .sidebar ul li a {
    display: block;
    padding: 1rem;
    color: #fff;
    text-decoration: none;
    font-size: 1rem;
    font-weight: 500;
    transition: background-color 0.3s, padding-left 0.3s;
  }

  .sidebar ul li a:hover {
    background-color: #374151;
    padding-left: 1.5rem;
  }

  /* ===== Mobile 顶部按钮 ===== */
  .mobile-topbar {
    display: none;
  }

  .toggle-btn {
    font-size: 1.5rem;
    background-color: white;
    color: #1f2937;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    margin-left: 1rem;
  }

  .main {
    padding: 2rem;
    padding-left: 270px;
    background-color: #f8fafc;
    min-height: 100vh;
  }

  @media (max-width: 768px) {
    .main {
      padding-left: 1rem;
      padding-right: 1rem;
      padding-top: 5rem;
    }
  }

  /* ===== Responsive for Mobile ===== */
  /* === MOBILE 覆盖优化 === */
  @media (max-width: 768px) {
    .sidebar {
      transform: translateX(-100%);
      padding-top: 60px;
    }

    .sidebar.active {
      transform: translateX(0);
    }

    .sidebar ul li a {
      font-size: 1.1rem;
      padding: 1.2rem;
    }

    .sidebar h2 {
      font-size: 1.3rem;
      padding: 1.5rem 1rem;
    }

    .mobile-topbar {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      padding: 1rem;
      background-color: #1f2937;
      color: white;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1100;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .toggle-btn {
      font-size: 1.5rem;
      background-color: #3b82f6;
      color: white;
      padding: 0.5rem 0.9rem;
      border-radius: 6px;
      font-weight: bold;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      margin-left: 0.5rem;
    }

    .main {
      padding-left: 1rem;
      padding-right: 1rem;
      padding-top: 5rem;
    }
  }

  /* === 背景遮罩 (可选加) === */
  .overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.4);
    z-index: 900;
  }

  .sidebar.active~.overlay {
    display: block;
  }
</style>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
  }
</script>