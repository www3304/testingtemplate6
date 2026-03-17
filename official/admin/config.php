<?php
// config.php

// Error Reporting
error_reporting(E_ALL); // Report all types of errors
ini_set('display_errors', 1); // Display errors on the screen
ini_set('display_startup_errors', 1); // Display startup errors
// Set the default timezone
date_default_timezone_set('Asia/Singapore'); // Adjust this to your desired timezone
// Start session
session_start();

// Load database credentials
$credentials_file = __DIR__ . '/../../db_credentials.php';
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
    die('Superadmin database connection failed: ' . $connection->connect_error);
}

// Get the current domain
$current_domain = $_SERVER['HTTP_HOST'];

// --- Fetch domain ID --- 
$domain_id = null;

$sql_domain = "SELECT id FROM domains WHERE domain = ? LIMIT 1";
$stmt_domain = $connection->prepare($sql_domain);

if ($stmt_domain === false) {
    die('Prepare failed (domains query): (' . $connection->errno . ') ' . $connection->error);
}

$stmt_domain->bind_param("s", $current_domain);
$stmt_domain->execute();
$result_domain = $stmt_domain->get_result();

if ($result_domain->num_rows > 0) {
    $domain_row = $result_domain->fetch_assoc();
    $domain_id = (int)$domain_row['id'];
} else {
    $stmt_domain->close();
    $connection->close();
    die('Domain ' . htmlspecialchars($current_domain) . ' not found in configuration.');
}
$stmt_domain->close();

// --- If domain ID found, fetch game-specific DB details AND status --- 
$game_type_id = 10; // Specific game type for Official
$game_db_name = null;
$game_db_user = null;
$game_db_pass = null;
$game_status = null;

// Prepare SQL to fetch the specific game DB details and status from mini_games
$sql_game = "SELECT db_name, db_user, db_pass, status FROM mini_games WHERE domain_id = ? AND game_type = ? LIMIT 1";
$stmt_game = $connection->prepare($sql_game);

if ($stmt_game === false) {
    $connection->close(); 
    die('Prepare failed (mini_games query): (' . $connection->errno . ') ' . $connection->error);
}

$stmt_game->bind_param("ii", $domain_id, $game_type_id);
$stmt_game->execute();
$result_game = $stmt_game->get_result();

if ($result_game->num_rows > 0) {
    $game_row = $result_game->fetch_assoc();
    $shoppingwithpoint_db_host = 'localhost'; 
    $shoppingwithpoint_db_user = $game_row['db_user'];
    $shoppingwithpoint_db_pass = $game_row['db_pass'];
    $shoppingwithpoint_db_name = $game_row['db_name'];
    $game_status = (int)$game_row['status']; // Get game status

    $stmt_game->close();
    $connection->close();

    // --- Check the specific game's status --- 
    // Allow access regardless of status 0 or 2 for admin panel
    /* 
    if ($game_status === 0) {
        die('Access Denied: The Shopping game is currently inactive.'); 
    } elseif ($game_status === 2) {
        die('Access Denied: The Shopping game is currently under maintenance.');
    }
    */
    
    // Still check for *invalid* status (neither 0, 1, nor 2)
    if (!in_array($game_status, [0, 1, 2])) { 
        // Unknown status - treat as error? Or just log?
        // For now, let's log it but still allow access, as the admin might need to fix it.
        error_log('Warning: Shopping game has an invalid status (' . $game_status . ') for domain ' . htmlspecialchars($current_domain) . '. Allowing admin access.');
        // Optional: Set a session variable to display a warning in the UI?
        // $_SESSION['admin_warning'] = 'The game currently has an invalid status: ' . $game_status;
    }

    // --- Proceed to connect to game DB regardless of status 0, 1, or 2 --- 

    // Create connection to the specific game database
    $official_admin_connection = new mysqli($shoppingwithpoint_db_host, $shoppingwithpoint_db_user, $shoppingwithpoint_db_pass, $shoppingwithpoint_db_name);

    if ($official_admin_connection->connect_error) {
        die('Shopping game database connection failed: ' . $official_admin_connection->connect_error);
    }

    if (!$official_admin_connection->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4 for Shopping admin DB: " . $official_admin_connection->error);
    }

} else {
    $stmt_game->close();
    $connection->close();
    die('Shopping game (Type ' . $game_type_id . ') configuration for domain ' . htmlspecialchars($current_domain) . ' not found. Cannot access admin area.');
}

// Function to verify login credentials
function verifyLogin($username, $password) {
    global $official_admin_connection;
    
    // Prepare a query to fetch admin data from the 'admins' table
    $sql = "SELECT id, username, password FROM admins WHERE username = ?";
    $stmt = $official_admin_connection->prepare($sql);
    
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database query preparation failed: ' . $official_admin_connection->error];
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        
        // Verify password (assuming it's stored as a hash)
        if (password_verify($password, $admin['password'])) {
            // Password is correct, set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['official_admin_logged_in'] = true;
                
            return ['success' => true, 'admin' => $admin];
        } else {
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }
    } else {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }
    
    $stmt->close();
}
?>