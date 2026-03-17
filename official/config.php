<?php
// Report all types of errors
error_reporting(E_ALL);
// Display errors on the screen
ini_set('display_errors', 1);
// Display startup errors
ini_set('display_startup_errors', 1);
// Set the default timezone
date_default_timezone_set('Asia/Singapore');
// Start session
session_start();

// Load database credentials
$credentials_file = __DIR__ . '/../db_credentials.php';
$credentials = require $credentials_file;

// Database connection details
$superadmin_db_host = $credentials['db_host'];
$superadmin_db_user = $credentials['db_user'];
$superadmin_db_pass = $credentials['db_pass'];
$superadmin_db_name = $credentials['db_name'];

// Create connection
$superadmin_connection = new mysqli($superadmin_db_host, $superadmin_db_user, $superadmin_db_pass, $superadmin_db_name);

// Check connection
if ($superadmin_connection->connect_error) {
    die('Superadmin database connection failed: ' . $superadmin_connection->connect_error);
}

// Get the current domain
$current_domain = $_SERVER['HTTP_HOST'];

// Fetch domain ID
$sql_domain = "SELECT id FROM domains WHERE domain = ? LIMIT 1";
$stmt_domain = $superadmin_connection->prepare($sql_domain);

// Check if prepare failed
if ($stmt_domain === false) {
    die('Prepare failed (domains query): (' . $superadmin_connection->errno . ') ' . $superadmin_connection->error);
}

// Bind parameters
$stmt_domain->bind_param("s", $current_domain);

// Execute query
$stmt_domain->execute();

// Get result
$result_domain = $stmt_domain->get_result();

// Check if there are rows
if ($result_domain->num_rows > 0) {
    $domain_row = $result_domain->fetch_assoc();
    $domain_id = (int)$domain_row['id'];
} else {
    $stmt_domain->close();
    $superadmin_connection->close();
    die('Domain ' . htmlspecialchars($current_domain) . ' not found in configuration.');
}
$stmt_domain->close();

// Game type ID for official game
$game_type_id = 10;

// Prepare SQL to fetch the specific game DB details and status from mini_games
$sql_game = "SELECT db_name, db_user, db_pass, status FROM mini_games WHERE domain_id = ? AND game_type = ? LIMIT 1";
$stmt_game = $superadmin_connection->prepare($sql_game);

if ($stmt_game === false) {
    $superadmin_connection->close();
    die('Prepare failed (mini_games query): (' . $superadmin_connection->errno . ') ' . $superadmin_connection->error);
}

// Bind parameters
$stmt_game->bind_param("ii", $domain_id, $game_type_id);

// Execute query
$stmt_game->execute();

// Get result
$result_game = $stmt_game->get_result();

// Check if there are rows
if ($result_game->num_rows > 0) {
    // Get game row
    $game_row = $result_game->fetch_assoc();

    // Get game DB details
    $official_user_db_host = 'localhost';
    $official_user_db_user = $game_row['db_user'];
    $official_user_db_pass = $game_row['db_pass'];
    $official_user_db_name = $game_row['db_name'];
    $game_status = (int)$game_row['status'];

    // Close statement
    $stmt_game->close();

    // Close superadmin connection
    $superadmin_connection->close();

    // Check the specific game's status
    if ($game_status === 0) {
        header('Location: ../is_inactive.php');
        exit;
    } elseif ($game_status === 2) {
        header('Location: ../is_maintenance.php');
        exit;
    } else {
        // Create connection to the specific game database
        $official_user_connection = new mysqli($official_user_db_host, $official_user_db_user, $official_user_db_pass, $official_user_db_name);

        // Check connection
        if ($official_user_connection->connect_error) {
            die('Official game database connection failed: ' . $official_user_connection->connect_error);
        }
    }

    // Set character set
    if (!$official_user_connection->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4 for official DB: " . $official_user_connection->error);
    }
} else {
    // Close statement
    $stmt_game->close();

    // Close superadmin connection
    $superadmin_connection->close();

    // Error message
    die('Official game (Type ' . $game_type_id . ') configuration for domain ' . htmlspecialchars($current_domain) . ' not found.');
}