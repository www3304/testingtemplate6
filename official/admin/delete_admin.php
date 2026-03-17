<?php
// Include the configuration file
require_once 'config.php';

// Check if user is logged in and is admin (adjust permission if needed)
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    // Redirect to login page if not logged in or not admin
    $_SESSION['error_message'] = 'You do not have permission to access this page.';
    header('Location: index.php');
    exit;
}

// Check if ID is provided via GET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'No admin ID specified for deletion.';
    header('Location: admins.php'); // Redirect back to the admin list
    exit;
}

// Sanitize the ID
$admin_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if ($admin_id === false || $admin_id <= 0) {
    $_SESSION['error_message'] = 'Invalid admin ID provided.';
    header('Location: admins.php');
    exit;
}

// *** Add check: Prevent deleting the last admin or the currently logged-in admin if needed ***
// Example (needs refinement based on your session structure):
// if ($admin_id === $_SESSION['admin_id']) {
//     $_SESSION['error_message'] = 'You cannot delete your own account.';
//     header('Location: admins.php');
//     exit;
// }
// $count_query = "SELECT COUNT(*) as count FROM admins";
// $count_result = $official_admin_connection->query($count_query);
// $count_row = $count_result->fetch_assoc();
// if ($count_row['count'] <= 1) {
//     $_SESSION['error_message'] = 'Cannot delete the last remaining admin.';
//     header('Location: admins.php');
//     exit;
// }

// Prepare the DELETE statement
$sql = "DELETE FROM admins WHERE id = ?";
$stmt = $official_admin_connection->prepare($sql);

if ($stmt === false) {
    // Handle prepare error
    $_SESSION['error_message'] = 'Failed to prepare delete statement: ' . $official_admin_connection->error;
    header('Location: admins.php');
    exit;
}

// Bind the admin ID parameter
$stmt->bind_param('i', $admin_id);

// Execute the statement
if ($stmt->execute()) {
    // Check if any row was actually deleted
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = 'Admin deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'Admin not found or could not be deleted.';
    }
} else {
    // Handle execution error
    $_SESSION['error_message'] = 'Error deleting admin: ' . $stmt->error;
}

// Close the statement
$stmt->close();

// Redirect back to the admin list page
header('Location: admins.php');
exit;

?> 