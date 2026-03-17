<?php
// Report all types of errors
error_reporting(E_ALL); 
// Display errors on the screen
ini_set('display_errors', 1); 
// Display startup errors
ini_set('display_startup_errors', 1); 
// Set the default timezone
date_default_timezone_set('Asia/Singapore');

// Load database credentials
$credentials_file = __DIR__ . '/db_credentials.php';
$credentials = require $credentials_file;

// Database connection details
$db_host = $credentials['db_host'];
$db_user = $credentials['db_user'];
$db_pass = $credentials['db_pass'];
$db_name = $credentials['db_name'];

// Create connection
$connection = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($connection->connect_error) {
    die('Database connection failed: ' . $connection->connect_error);
}

// Set charset
if (!$connection->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $connection->error);
}

// Get current domain
$current_domain_for_page = $_SERVER['HTTP_HOST'];

// Get Domain ID
$domain_id = null;
$sql_domain = "SELECT id FROM domains WHERE domain = ? LIMIT 1";
$stmt_domain = $connection->prepare($sql_domain);
$stmt_domain->bind_param("s", $current_domain_for_page);
$stmt_domain->execute();
$result_domain = $stmt_domain->get_result();

if ($result_domain->num_rows > 0) {
    $domain_row = $result_domain->fetch_assoc();
    $domain_id = (int)$domain_row['id'];
}

$stmt_domain->close();

$error_message = '';

// Game mapping (ID => [User-Friendly Name, Directory])
$game_map = [
    1 => ['Spin', 'spin/'],
    2 => ['Plinko', 'plinko/'],
    3 => ['Checkin', 'checkin/'],
    4 => ['VIP', 'VIP/'],
    5 => ['Telegram CRM', 'telegram_crm/'],
    6 => ['Lucky Chest', 'lucky_chest/'],
    7 => ['Shopping with Point', 'shopping_with_point/'],
    8 => ['Lucky Draw', 'lucky_draw/'],
    9 => ['Baccarat', 'baccarat/']
];

// Check if we have a valid database connection
if (!isset($connection) || !($connection instanceof mysqli) || $connection->connect_error) {
    $error_message = "Database connection is not available or closed.";
}

// Check if we have a valid domain_id
if (!isset($domain_id) || $domain_id === null) {
    $error_message = "Domain ID not available. Please check the configuration.";
}

// If there's no error so far, proceed with fetching game data
$display_games = [];

if (empty($error_message)) {
    // Use the domain_id from config.php to fetch games for this domain
    $sql_games = "SELECT game_type, status FROM mini_games WHERE domain_id = ? ORDER BY game_type ASC";
    if ($stmt_games = $connection->prepare($sql_games)) {
        $stmt_games->bind_param("i", $domain_id);
        if ($stmt_games->execute()) {
            $result_games = $stmt_games->get_result();
            while ($row = $result_games->fetch_assoc()) {
                $game_status = (int)$row['status'];
                $game_type_id = (int)$row['game_type'];
                if (isset($game_map[$game_type_id])) {
                    $display_games[$game_type_id] = [
                        'name' => $game_map[$game_type_id][0],
                        'dir' => $game_map[$game_type_id][1],
                        'status' => $game_status
                    ];
                }
            }
        } else {
            $error_message = "Error executing games query: " . $stmt_games->error;
        }
        $stmt_games->close();
    } else {
        $error_message = "Error preparing games query: " . $connection->error;
    }
}

// Handle errors silently for production
if (!empty($error_message)) {
    // Log error instead of displaying
    error_log("Bubbles.php error: " . $error_message);
}

// Default icon map based on game type
$icon_map = [
    1 => '<i class="fas fa-dharmachakra"></i>', // Spin
    2 => '<i class="fas fa-dice"></i>', // Plinko
    3 => '<i class="fas fa-check"></i>', // Checkin
    4 => '<i class="fas fa-crown"></i>', // VIP
    5 => '<i class="fas fa-comment-dots"></i>', // Telegram CRM
    6 => '<i class="fas fa-gift"></i>', // Lucky Chest
    7 => '<i class="fas fa-shopping-cart"></i>', // Shopping with Point
    8 => '<i class="fas fa-bullseye"></i>',  // Lucky Draw
    9 => '<i class="fas fa-dice-d20"></i>'  // Baccarat
];

// Define game icons to replace generic content
$game_icons = [
    'dice' => '<i class="fas fa-dice"></i>',
    'spinner' => '<i class="fas fa-sync-alt"></i>',
    'slots' => '<i class="fas fa-dharmachakra"></i>',
    'cards' => '<i class="fas fa-cards"></i>',
    'wheel' => '<i class="fas fa-circle-notch"></i>',
    'gift' => '<i class="fas fa-gift"></i>',
    'trophy' => '<i class="fas fa-trophy"></i>',
    'gamepad' => '<i class="fas fa-gamepad"></i>'
];

// Colors for game items
$game_colors = [
    'color-1' => 'linear-gradient(135deg, #7F5AF0, #6039e4)',
    'color-2' => 'linear-gradient(135deg, #FF8E3C, #FF5F00)',
    'color-3' => 'linear-gradient(135deg, #2CB67D, #209665)',
    'color-4' => 'linear-gradient(135deg, #7950F2, #4C1D95)',
    'color-5' => 'linear-gradient(135deg, #EC4899, #BE185D)',
];
?>

<!-- Styles -->
<link rel="stylesheet" href="../v3/main.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- Games Launcher -->
<div class="games-launcher">
    <div class="launcher-toggle" id="launcherToggle">
        <i class="fas fa-gamepad"></i>
        <span class="toggle-pulse"></span>
    </div>

    <div class="launcher-panel" id="launcherPanel">
        <div class="launcher-header">
            <h4>Available Games</h4>
            <button class="launcher-close" id="launcherClose">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="game-items">
            <?php
            if (!empty($display_games)) {
                // Counter for color assignment
                $color_index = 0;
                $color_count = count($game_colors);

                foreach ($display_games as $id => $game_details) {
                    $href = '';
                    $status_class = '';
                    $icon_html = isset($icon_map[$id]) ? $icon_map[$id] : '<i class="fas fa-gamepad"></i>';
                    $name = htmlspecialchars($game_details['name']);

                    switch ($game_details['status']) {
                        case 0: // Inactive
                            $href = '../is_inactive.php';
                            $status_class = 'game-inactive';
                            break;
                        case 2: // Maintenance
                            $href = '../is_maintenance.php';
                            $status_class = 'game-maintenance';
                            break;
                        case 1: // Active
                        default:
                            $href = '../' . htmlspecialchars($game_details['dir']);
                            break;
                    }

                    // Get a color for this game
                    $color_key = array_keys($game_colors)[$color_index % $color_count];
                    $color_value = $game_colors[$color_key];

                    // Choose an icon based on the game name or use the default
                    foreach ($game_icons as $key => $icon_code) {
                        if (stripos($name, $key) !== false) {
                            $icon_html = $icon_code;
                            break;
                        }
                    }

                    // Output the game item
                    echo '<a href="' . $href . '" class="game-item ' . $status_class . '" style="--game-gradient: ' . $color_value . '">';
                    echo '<div class="game-icon">' . $icon_html . '</div>';
                    echo '<div class="game-info">';
                    echo '<span class="game-title">' . $name . '</span>';

                    if ($status_class === 'game-inactive') {
                        echo '<span class="game-status inactive">Coming Soon</span>';
                    } elseif ($status_class === 'game-maintenance') {
                        echo '<span class="game-status maintenance">Maintenance</span>';
                    } else {
                        echo '<span class="game-status active">Play Now</span>';
                    }

                    echo '</div>';
                    echo '</a>';

                    // Increment color index
                    $color_index++;
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- Games Launcher Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const launcherToggle = document.getElementById('launcherToggle');
        const launcherPanel = document.getElementById('launcherPanel');
        const launcherClose = document.getElementById('launcherClose');

        if (launcherToggle && launcherPanel) {
            launcherToggle.addEventListener('click', function() {
                launcherPanel.classList.toggle('active');
                launcherToggle.classList.toggle('active');
            });
        }

        if (launcherClose) {
            launcherClose.addEventListener('click', function() {
                launcherPanel.classList.remove('active');
                launcherToggle.classList.remove('active');
            });
        }

        // Close launcher panel when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideLauncher =
                launcherPanel.contains(event.target) ||
                launcherToggle.contains(event.target);

            if (!isClickInsideLauncher && launcherPanel.classList.contains('active')) {
                launcherPanel.classList.remove('active');
                launcherToggle.classList.remove('active');
            }
        });
    });
</script> 