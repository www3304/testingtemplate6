<?php
// Include the configuration file
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    // Redirect to login page if not logged in or not admin
    $_SESSION['error_message'] = 'You do not have permission to access this page.';
    header('Location: index.php');
    exit;
}

// Check if ID is provided via GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'No user ID specified for deletion.';
    header('Location: users.php'); // Redirect back to the user list
    exit;
}

// Sanitize the ID
$user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if ($user_id === false || $user_id <= 0) {
    $_SESSION['error_message'] = 'Invalid user ID provided.';
    header('Location: users.php');
    exit;
}

// Prepare the DELETE statement
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $official_admin_connection->prepare($sql);

if ($stmt === false) {
    // Handle prepare error
    $_SESSION['error_message'] = 'Failed to prepare delete statement: ' . $official_admin_connection->error;
    header('Location: users.php');
    exit;
}

// Bind the user ID parameter
$stmt->bind_param('i', $user_id);

// Execute the statement
if ($stmt->execute()) {
    // Check if any row was actually deleted
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = 'User deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'User not found or could not be deleted.';
    }
} else {
    // Handle execution error
    $_SESSION['error_message'] = 'Error deleting user: ' . $stmt->error;
}

// Close the statement
$stmt->close();

// Redirect back to the user list page
header('Location: users.php');
exit;

?>
