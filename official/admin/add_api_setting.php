<?php
// Include the configuration file
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';
$name = '';
$access_key = '';
$access_token = '';
$is_active = 1; // Default to active

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $access_key = trim($_POST['access_key'] ?? '');
    $access_token = trim($_POST['access_token'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate required fields
    if (empty($access_key) || empty($access_token)) {
        $error_message = 'Access Key and Access Token are required.';
    } else {
        // Optional: Add more validation (e.g., key/token format/length)
        
        // Check if access_key already exists (should be unique)
        $check_query = "SELECT COUNT(*) as count FROM api_settings WHERE access_key = ?";
        $stmt = $official_admin_connection->prepare($check_query);
        if ($stmt === false) {
             $error_message = 'Error preparing statement: ' . $official_admin_connection->error;
        } else {
            $stmt->bind_param('s', $access_key);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row['count'] > 0) {
                $error_message = 'Access Key already exists. Please use a unique key.';
            } else {
                // Insert setting into database
                // name can be NULL, so handle empty string
                $name_to_insert = !empty($name) ? $name : null;
                
                // Hash the access token
                $hashed_token = password_hash($access_token, PASSWORD_DEFAULT);
                
                if ($hashed_token === false) {
                    $error_message = 'Error hashing access token.';
                } else {
                    $insert_query = "INSERT INTO api_settings (access_key, access_token, name, is_active) VALUES (?, ?, ?, ?)";
                    
                    $stmt = $official_admin_connection->prepare($insert_query);
                     if ($stmt === false) {
                         $error_message = 'Error preparing insert statement: ' . $official_admin_connection->error;
                    } else {
                        // Use the hashed token in bind_param
                        $stmt->bind_param('sssi', $access_key, $hashed_token, $name_to_insert, $is_active);
                        
                        if ($stmt->execute()) {
                            $success_message = 'API Setting added successfully!';
                            // Reset form data (keep token empty for security)
                            $name = '';
                            $access_key = '';
                            $access_token = ''; // Clear the token field after successful save
                            $is_active = 1; // Reset to default
                        } else {
                            $error_message = 'Error adding setting to database: ' . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            }
        } 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add API Setting - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tailwind Color -->
    <script src="assets/js/tailwind_color.js"></script>
    <style>
        .form-input {
            transition: all 0.2s ease-in-out;
            border-width: 1px;
        }
        .form-input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.25);
        }
        /* Style for toggle switch */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #0ea5e9;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #0ea5e9;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header -->
            <?php include 'header.php'; ?>
            
            <!-- Main Content Area -->
            <main class="flex-1 p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Add New API Setting</h2>
                            <p class="mt-1 text-sm text-gray-500">Create a new API key and token</p>
                        </div>
                        <div>
                            <a href="api_settings.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to API Settings
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Form Card -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-6">
                        <?php if (!empty($error_message)): ?>
                        <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
                            <p><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                        <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
                            <p><?php echo htmlspecialchars($success_message); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <form action="add_api_setting.php" method="POST">
                            <div class="space-y-6">
                                <!-- Name Field (Optional) -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name (Optional)</label>
                                    <input type="text" name="name" id="name" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="e.g., My App Integration" value="<?php echo htmlspecialchars($name); ?>">
                                    <p class="mt-1.5 text-xs text-gray-500">A descriptive name for this API setting.</p>
                                </div>
                                
                                <!-- Access Key Field -->
                                <div>
                                    <label for="access_key" class="block text-sm font-medium text-gray-700 mb-1">Access Key</label>
                                    <input type="text" name="access_key" id="access_key" required class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter a unique access key" value="<?php echo htmlspecialchars($access_key); ?>">
                                     <p class="mt-1.5 text-xs text-gray-500">Unique identifier for the API client. Must be unique.</p>
                                    <!-- Add button to auto-generate? -->
                                </div>
                                
                                <!-- Access Token Field -->
                                <div>
                                    <label for="access_token" class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                                    <div class="relative">
                                        <input type="password" name="access_token" id="access_token" required class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter the secret access token" value="<?php echo htmlspecialchars($access_token); ?>">
                                        <button type="button" id="toggle-token" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                            <!-- Eye icon SVG -->
                                        </button>
                                    </div>
                                    <p class="mt-1.5 text-xs text-gray-500">Secret token for authentication. Treat this like a password.</p>
                                     <!-- Add button to auto-generate? -->
                                </div>

                                 <!-- Is Active Field -->
                                <div>
                                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                                        <input type="checkbox" name="is_active" id="is_active" value="1" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer" <?php echo ($is_active == 1) ? 'checked' : ''; ?>/>
                                        <label for="is_active" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                                    </div>
                                    <span class="text-sm text-gray-600">Active</span>
                                     <p class="mt-1.5 text-xs text-gray-500">Inactive settings cannot be used for authentication.</p>
                                </div>

                                <!-- Form Actions -->
                                <div class="flex justify-end space-x-3 pt-8">
                                    <a href="api_settings.php" class="inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                        Cancel
                                    </a>
                                    <button type="submit" class="inline-flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform hover:scale-105">
                                        Create API Setting
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Basic script to toggle token visibility (add SVG icons as needed)
        document.addEventListener('DOMContentLoaded', function() {
            const toggleToken = document.getElementById('toggle-token');
            const tokenField = document.getElementById('access_token');
            
            if (toggleToken && tokenField) {
                // Add SVG icons inside the button for eye/eye-off
                 toggleToken.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="eye-icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="eye-off-icon">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>`;
                const eyeIcon = toggleToken.querySelector('#eye-icon');
                const eyeOffIcon = toggleToken.querySelector('#eye-off-icon');
                
                toggleToken.addEventListener('click', function() {
                    const type = tokenField.getAttribute('type') === 'password' ? 'text' : 'password';
                    tokenField.setAttribute('type', type);
                    eyeIcon.classList.toggle('hidden');
                    eyeOffIcon.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html> 