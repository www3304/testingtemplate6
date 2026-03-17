<?php
// Include the configuration file
require_once 'config.php';

// Check if user is logged in and is admin (adjust permission if needed)
if (!isset($_SESSION['official_admin_logged_in']) || $_SESSION['official_admin_logged_in'] !== true) {
    // Redirect to login page if not logged in
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';

// Initialize form variables
$form_data = [
    'site_title' => '',
    'favicon_image' => '',
    'logo_image' => '',
    'logo_link' => '',
    'hero_image' => '',
    'header_script' => '',
    
    'primary_bg_color' => '#000000',
    'secondary_bg_color' => '#000000',
    'header_bg_start' => '#000000',
    'header_bg_end' => '#2C2C2C',
    'footer_bg_start' => '#000000',
    'footer_bg_end' => '#2C2C2C',
    'card_bg_color' => '#252525',
    'text_color' => '#FFFFFF',
    'accent_color' => '#F6D02C',
    'border_color' => '#FFFFFF',
    
    'top_column_sections' => json_encode([]),
    'middle_logo_image' => '',
    'middle_section_title' => '',
    'middle_section_content' => '',
    'bottom_column_sections' => json_encode([]),
    
    'footer_item1_url' => '',
    'footer_item1_image' => '',
    'footer_item2_url' => '',
    'footer_item2_image' => '',
    'footer_item3_url' => '',
    'footer_item3_image' => '',
    'footer_item4_url' => '',
    'footer_item4_image' => '',
    'footer_item5_url' => '',
    'footer_item5_image' => '',
    
    'floating_buttons' => json_encode([['link' => '', 'image' => '']]),
    
    'is_active' => 1
];

// File upload function
function handleFileUpload($file_input_name, $upload_dir = 'uploads/') {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    
    $file = $_FILES[$file_input_name];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return false;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Get domain name for folder structure
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $domain_folder = preg_replace('/[^a-zA-Z0-9.-]/', '_', $domain);
    
    // Create folder structure: uploads/domain_name/
    $full_upload_dir = '../' . $upload_dir . $domain_folder . '/';
    
    // Use original filename
    $filename = $file['name'];
    $upload_path = $full_upload_dir . $filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir($full_upload_dir)) {
        mkdir($full_upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $upload_dir . $domain_folder . '/' . $filename;
    }
    
    return false;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and merge with defaults
    foreach ($form_data as $key => $default) {
        $form_data[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : $default;
    }
    
    // Handle checkbox for is_active
    $form_data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle file uploads for image fields
    $image_fields = [
        'favicon_image' => 'favicon_upload',
        'logo_image' => 'logo_upload',
        'hero_image' => 'hero_image_upload',
        'middle_logo_image' => 'middle_logo_upload',
        'footer_item1_image' => 'footer_item1_upload',
        'footer_item2_image' => 'footer_item2_upload',
        'footer_item3_image' => 'footer_item3_upload',
        'footer_item4_image' => 'footer_item4_upload',
        'footer_item5_image' => 'footer_item5_upload'
    ];
    
    // Handle top column sections (dynamic)
    $top_column_sections = [];
    if (isset($_POST['top_column_section_count']) && is_numeric($_POST['top_column_section_count'])) {
        $count = (int)$_POST['top_column_section_count'];
        for ($i = 0; $i < $count; $i++) {
            $title = trim($_POST["top_col_section_{$i}_title"] ?? '');
            $content = trim($_POST["top_col_section_{$i}_content"] ?? '');
            $btn_text = trim($_POST["top_col_section_{$i}_btn_text"] ?? '');
            $btn_url = trim($_POST["top_col_section_{$i}_btn_url"] ?? '');
            
            if (!empty($title) || !empty($content)) {
                $top_column_sections[] = [
                    'title' => $title,
                    'content' => $content,
                    'btn_text' => $btn_text,
                    'btn_url' => $btn_url
                ];
            }
        }
    }
    $form_data['top_column_sections'] = json_encode($top_column_sections);
    
    // Handle bottom column sections (dynamic)
    $bottom_column_sections = [];
    if (isset($_POST['bottom_column_section_count']) && is_numeric($_POST['bottom_column_section_count'])) {
        $count = (int)$_POST['bottom_column_section_count'];
        for ($i = 0; $i < $count; $i++) {
            $title = trim($_POST["bottom_col_section_{$i}_title"] ?? '');
            $content = trim($_POST["bottom_col_section_{$i}_content"] ?? '');
            $btn_text = trim($_POST["bottom_col_section_{$i}_btn_text"] ?? '');
            $btn_url = trim($_POST["bottom_col_section_{$i}_btn_url"] ?? '');
            
            if (!empty($title) || !empty($content)) {
                $bottom_column_sections[] = [
                    'title' => $title,
                    'content' => $content,
                    'btn_text' => $btn_text,
                    'btn_url' => $btn_url
                ];
            }
        }
    }
    $form_data['bottom_column_sections'] = json_encode($bottom_column_sections);
    
    // Handle floating buttons separately (dynamic)
    $floating_buttons = [];
    if (isset($_POST['float_btn_count']) && is_numeric($_POST['float_btn_count'])) {
        $count = (int)$_POST['float_btn_count'];
        for ($i = 0; $i < $count; $i++) {
            $link = trim($_POST["float_btn_{$i}_link"] ?? '');
            $image = '';
            
            // Handle file upload for this button
            $upload_result = handleFileUpload("float_btn_{$i}_upload");
            if ($upload_result !== false && $upload_result !== '') {
                $image = $upload_result;
            }
            
            if (!empty($link) || !empty($image)) {
                $floating_buttons[] = ['link' => $link, 'image' => $image];
            }
        }
    }
    $form_data['floating_buttons'] = json_encode($floating_buttons);
    
    $upload_errors = [];
    foreach ($image_fields as $db_field => $upload_field) {
        $upload_result = handleFileUpload($upload_field);
        if ($upload_result === false && isset($_FILES[$upload_field]) && $_FILES[$upload_field]['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_errors[] = "Error uploading " . str_replace('_', ' ', $upload_field);
        } elseif ($upload_result !== false && $upload_result !== '') {
            $form_data[$db_field] = $upload_result;
        }
    }
    
    // Check for upload errors
    if (!empty($upload_errors)) {
        $error_message = implode(', ', $upload_errors) . '. Please ensure files are images (JPG, PNG, GIF, WebP) and under 5MB.';
    } elseif (empty($form_data['site_title'])) {
        $error_message = 'Site title is required.';
    } else {
        // Prepare column names and values for insertion
        $columns = array_keys($form_data);
        $placeholders = array_fill(0, count($columns), '?');
        $values = array_values($form_data);
        
        // Create the INSERT query
        $insert_query = "INSERT INTO site_settings (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $official_admin_connection->prepare($insert_query);
        if ($stmt === false) {
            $error_message = 'Error preparing insert statement: ' . $official_admin_connection->error;
        } else {
            // Create type string - all are strings except is_active which is int
            $types = str_repeat('s', count($values) - 1) . 'i'; // All strings except last one (is_active) is integer
            
            $stmt->bind_param($types, ...$values);
            
            if ($stmt->execute()) {
                // If this setting is set to active, deactivate all other settings
                if ($form_data['is_active'] == 1) {
                    $deactivate_query = "UPDATE site_settings SET is_active = 0 WHERE id != LAST_INSERT_ID()";
                    $official_admin_connection->query($deactivate_query);
                }
                
                $success_message = 'Site setting added successfully!';
                // Reset form data to defaults
                $form_data = [
                    'site_title' => 'BMB99',
                    'favicon_image' => '',
                    'logo_image' => '',
                    'logo_link' => '',
                    'hero_image' => '',
                    'header_script' => '',
                    
                    'primary_bg_color' => '#000000',
                    'secondary_bg_color' => '#000000',
                    'header_bg_start' => '#000000',
                    'header_bg_end' => '#2C2C2C',
                    'footer_bg_start' => '#000000',
                    'footer_bg_end' => '#2C2C2C',
                    'card_bg_color' => '#252525',
                    'text_color' => 'white',
                    'accent_color' => '#F6D02C',
                    'border_color' => 'white',
                    
                    'top_column_sections' => json_encode([]),
                    'middle_logo_image' => '',
                    'middle_section_title' => '',
                    'middle_section_content' => '',
                    'bottom_column_sections' => json_encode([]),
                    
                    'footer_item1_url' => '',
                    'footer_item1_image' => '',
                    'footer_item2_url' => '',
                    'footer_item2_image' => '',
                    'footer_item3_url' => '',
                    'footer_item3_image' => '',
                    'footer_item4_url' => '',
                    'footer_item4_image' => '',
                    'footer_item5_url' => '',
                    'footer_item5_image' => '',
                    
                    'floating_buttons' => json_encode([['link' => '', 'image' => '']]),
                    
                    'is_active' => 1
                ];
            } else {
                $error_message = 'Error adding site setting to database: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Site Setting - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tailwind Color -->
    <script src="assets/js/tailwind_color.js"></script>
    <style>
        /* Improved input styles */
        .form-input, .form-textarea {
            transition: all 0.2s ease-in-out;
            border-width: 1px;
        }
        .form-input:focus, .form-textarea:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.25);
        }
        .section-header {
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
        }
        .color-preview {
            width: 40px;
            height: 40px;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
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
                            <h2 class="text-xl font-bold text-gray-900">Add New Site Setting</h2>
                            <p class="mt-1 text-sm text-gray-500">Create a new site configuration</p>
                        </div>
                        <div>
                            <a href="site_settings.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to Site Settings
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
                        
                        <form action="add_site_setting.php" method="POST" enctype="multipart/form-data">
                            <!-- Site Meta Information -->
                            <div class="section-header">
                                <h3 class="text-lg font-medium text-gray-900">Site Meta Information</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    <label for="site_title" class="block text-sm font-medium text-gray-700 mb-1">Site Title *</label>
                                    <input type="text" name="site_title" id="site_title" required class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter site title" value="<?php echo htmlspecialchars($form_data['site_title']); ?>">
                                </div>
                                <div>
                                    <label for="favicon_upload" class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                                    <input type="file" name="favicon_upload" id="favicon_upload" accept="image/*" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0">
                                    <p class="mt-1 text-xs text-gray-500">Upload favicon image (JPG, PNG, GIF, WebP, max 5MB)</p>
                                </div>
                                <div>
                                    <label for="logo_upload" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                                    <input type="file" name="logo_upload" id="logo_upload" accept="image/*" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0">
                                    <p class="mt-1 text-xs text-gray-500">Upload logo image (JPG, PNG, GIF, WebP, max 5MB)</p>
                                </div>
                                <div>
                                    <label for="logo_link" class="block text-sm font-medium text-gray-700 mb-1">Logo Link</label>
                                    <input type="url" name="logo_link" id="logo_link" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="https://example.com/" value="<?php echo htmlspecialchars($form_data['logo_link']); ?>">
                                </div>
                                <div class="md:col-span-2">
                                    <label for="hero_image_upload" class="block text-sm font-medium text-gray-700 mb-1">Banner Image</label>
                                    <input type="file" name="hero_image_upload" id="hero_image_upload" accept="image/*" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0">
                                    <p class="mt-1 text-xs text-gray-500">Upload hero image (JPG, PNG, GIF, WebP, max 5MB)</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="header_script" class="block text-sm font-medium text-gray-700 mb-1">Header Script (Google Analytics, etc.)</label>
                                    <textarea name="header_script" id="header_script" rows="6" class="form-textarea shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Insert tracking codes like Google Analytics here..."><?php echo htmlspecialchars($form_data['header_script']); ?></textarea>
                                    <p class="mt-1 text-xs text-gray-500">Add custom scripts that should be placed in the &lt;head&gt; section</p>
                                </div>
                            </div>

                            <!-- Color Scheme -->
                            <div class="section-header">
                                <h3 class="text-lg font-medium text-gray-900">Color Scheme</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                <?php 
                                $color_fields = [
                                    'primary_bg_color' => 'Primary Background',
                                    'secondary_bg_color' => 'Secondary Background',
                                    'header_bg_start' => 'Header Start',
                                    'header_bg_end' => 'Header End',
                                    'footer_bg_start' => 'Footer Start',
                                    'footer_bg_end' => 'Footer End',
                                    'card_bg_color' => 'Card Background',
                                    'text_color' => 'Text Color',
                                    'accent_color' => 'Accent Color',
                                    'border_color' => 'Border Color'
                                ];
                                
                                foreach ($color_fields as $field => $label): ?>
                                <div>
                                    <label for="<?php echo $field; ?>" class="block text-sm font-medium text-gray-700 mb-1"><?php echo $label; ?></label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" name="<?php echo $field; ?>" id="<?php echo $field; ?>" class="color-preview" value="<?php echo htmlspecialchars($form_data[$field]); ?>">
                                        <input type="text" class="form-input shadow-sm flex-1 px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="#000000" value="<?php echo htmlspecialchars($form_data[$field]); ?>" onchange="document.getElementById('<?php echo $field; ?>').value = this.value">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Dynamic Top Column Section Content -->
                            <div class="section-header">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-medium text-gray-900">Top Column Section</h3>
                                    <button type="button" id="addTopColumnSection" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Add Column
                                    </button>
                                </div>
                            </div>
                            <div id="topColumnSectionsContainer" class="mb-8">
                                <!-- Dynamic top column sections will be populated here -->
                                <div class="top-column-section-item bg-gray-50 p-4 rounded border mb-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-md font-medium text-gray-800">Column 1</h4>
                                        <button type="button" class="removeTopColumnSection text-red-600 hover:text-red-900" title="Remove Column">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                                <input type="text" name="top_col_section_0_title" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter column title">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
                                                <input type="text" name="top_col_section_0_btn_text" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Button text">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Button URL</label>
                                                <input type="url" name="top_col_section_0_btn_url" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="https://example.com">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                                            <textarea name="top_col_section_0_content" rows="4" class="form-textarea shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter column content"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="topColumnSectionCount" name="top_column_section_count" value="1">

                            <!-- Middle Section Content -->
                            <div class="section-header">
                                <h3 class="text-lg font-medium text-gray-900">Middle Section</h3>
                            </div>
                            <div class="grid grid-cols-1 gap-6 mb-8">
                                <div>
                                    <label for="middle_logo_upload" class="block text-sm font-medium text-gray-700 mb-1">Middle Logo</label>
                                    <input type="file" name="middle_logo_upload" id="middle_logo_upload" accept="image/*" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0">
                                    <p class="mt-1 text-xs text-gray-500">Upload middle logo image (JPG, PNG, GIF, WebP, max 5MB)</p>
                                </div>
                                <div>
                                    <label for="middle_section_title" class="block text-sm font-medium text-gray-700 mb-1">Section Title</label>
                                    <input type="text" name="middle_section_title" id="middle_section_title" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter section title" value="<?php echo htmlspecialchars($form_data['middle_section_title'] ?? ''); ?>">
                                </div>
                                <div>
                                    <label for="middle_section_content" class="block text-sm font-medium text-gray-700 mb-1">Section Content</label>
                                    <textarea name="middle_section_content" id="middle_section_content" rows="6" class="form-textarea shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter section content (supports multiple paragraphs)"><?php echo htmlspecialchars($form_data['middle_section_content'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <!-- Dynamic Bottom Column Section -->
                            <div class="section-header">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-medium text-gray-900">Bottom Column Section</h3>
                                    <button type="button" id="addBottomColumnSection" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Add Column
                                    </button>
                                </div>
                            </div>
                            <div id="bottomColumnSectionsContainer" class="mb-8">
                                <!-- Dynamic bottom column sections will be populated here -->
                                <div class="bottom-column-section-item bg-gray-50 p-4 rounded border mb-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-md font-medium text-gray-800">Column 1</h4>
                                        <button type="button" class="removeBottomColumnSection text-red-600 hover:text-red-900" title="Remove Column">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                                <input type="text" name="bottom_col_section_0_title" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter column title">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
                                                <input type="text" name="bottom_col_section_0_btn_text" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Button text">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Button URL</label>
                                                <input type="url" name="bottom_col_section_0_btn_url" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="https://example.com">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                                            <textarea name="bottom_col_section_0_content" rows="4" class="form-textarea shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter column content"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="bottomColumnSectionCount" name="bottom_column_section_count" value="1">

                            <!-- Footer Items -->
                            <div class="section-header">
                                <h3 class="text-lg font-medium text-gray-900">Footer Items (5 Items)</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="space-y-4">
                                    <h4 class="text-md font-medium text-gray-800">Footer Item <?php echo $i; ?></h4>
                                    <div>
                                        <label for="footer_item<?php echo $i; ?>_url" class="block text-sm font-medium text-gray-700 mb-1">Link URL</label>
                                        <input type="url" name="footer_item<?php echo $i; ?>_url" id="footer_item<?php echo $i; ?>_url" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="https://example.com" value="<?php echo htmlspecialchars($form_data["footer_item{$i}_url"]); ?>">
                                    </div>
                                    <div>
                                        <label for="footer_item<?php echo $i; ?>_upload" class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                                        <input type="file" name="footer_item<?php echo $i; ?>_upload" id="footer_item<?php echo $i; ?>_upload" accept="image/*" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0">
                                        <p class="mt-1 text-xs text-gray-500">Upload image (JPG, PNG, GIF, WebP, max 5MB)</p>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>

                            <!-- Floating Action Buttons -->
                            <div class="section-header">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-lg font-medium text-gray-900">Floating Action Buttons</h3>
                                    <button type="button" id="addFloatButton" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Add Button
                                    </button>
                                </div>
                            </div>
                            <div id="floatingButtonsContainer" class="mb-8">
                                <!-- Dynamic floating buttons will be added here -->
                                <div class="floating-button-item bg-gray-50 p-4 rounded border mb-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-md font-medium text-gray-800">Floating Button 1</h4>
                                        <button type="button" class="removeFloatButton text-red-600 hover:text-red-900" title="Remove Button">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Link</label>
                                            <input type="text" name="float_btn_0_link" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="https://google.com" value="">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                                            <input type="file" name="float_btn_0_upload" accept="image/*" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0">
                                            <p class="mt-1 text-xs text-gray-500">Upload button image (JPG, PNG, GIF, WebP, max 5MB)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="floatBtnCount" name="float_btn_count" value="1">

                            <!-- Status -->
                            <div class="section-header">
                                <h3 class="text-lg font-medium text-gray-900">Status</h3>
                            </div>
                            <div class="mb-8">
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_active" id="is_active" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" <?php echo $form_data['is_active'] ? 'checked' : ''; ?>>
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Enable this site setting configuration.</p>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex justify-end space-x-3 pt-8">
                                <a href="site_settings.php" class="inline-flex justify-center py-2.5 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                                    Cancel
                                </a>
                                <button type="submit" class="inline-flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform hover:scale-105">
                                    Create Site Setting
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Color picker synchronization
        document.addEventListener('DOMContentLoaded', function() {
            // Sync color inputs with color pickers
            const colorFields = ['primary_bg_color', 'secondary_bg_color', 'header_bg_start', 'header_bg_end', 'footer_bg_start', 'footer_bg_end', 'card_bg_color', 'text_color', 'accent_color', 'border_color'];
            
            colorFields.forEach(field => {
                const colorPicker = document.getElementById(field);
                const textInput = colorPicker.nextElementSibling;
                
                colorPicker.addEventListener('change', function() {
                    textInput.value = this.value;
                });
                
                textInput.addEventListener('input', function() {
                    if (this.value.match(/^#[0-9A-F]{6}$/i)) {
                        colorPicker.value = this.value;
                    }
                });
            });
            
            // Dynamic top column sections functionality
            let topColumnSectionCount = 1;
            
            document.getElementById('addTopColumnSection').addEventListener('click', function() {
                const container = document.getElementById('topColumnSectionsContainer');
                const newSection = document.createElement('div');
                newSection.className = 'top-column-section-item bg-gray-50 p-4 rounded border mb-4';
                newSection.innerHTML = `
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-md font-medium text-gray-800">Column ${topColumnSectionCount + 1}</h4>
                        <button type="button" class="removeTopColumnSection text-red-600 hover:text-red-900" title="Remove Column">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                <input type="text" name="top_col_section_${topColumnSectionCount}_title" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter column title">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
                                <input type="text" name="top_col_section_${topColumnSectionCount}_btn_text" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Button text">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Button URL</label>
                                <input type="url" name="top_col_section_${topColumnSectionCount}_btn_url" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="https://example.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                            <textarea name="top_col_section_${topColumnSectionCount}_content" rows="4" class="form-textarea shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter column content"></textarea>
                        </div>
                    </div>
                `;
                
                container.appendChild(newSection);
                topColumnSectionCount++;
                document.getElementById('topColumnSectionCount').value = topColumnSectionCount;
                
                // Add remove functionality to the new section
                newSection.querySelector('.removeTopColumnSection').addEventListener('click', function() {
                    newSection.remove();
                    updateTopColumnSectionNumbers();
                });
            });
            
            // Dynamic bottom column sections functionality
            let bottomColumnSectionCount = 1;
            
            document.getElementById('addBottomColumnSection').addEventListener('click', function() {
                const container = document.getElementById('bottomColumnSectionsContainer');
                const newSection = document.createElement('div');
                newSection.className = 'bottom-column-section-item bg-gray-50 p-4 rounded border mb-4';
                newSection.innerHTML = `
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-md font-medium text-gray-800">Column ${bottomColumnSectionCount + 1}</h4>
                        <button type="button" class="removeBottomColumnSection text-red-600 hover:text-red-900" title="Remove Column">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                <input type="text" name="bottom_col_section_${bottomColumnSectionCount}_title" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter column title">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
                                <input type="text" name="bottom_col_section_${bottomColumnSectionCount}_btn_text" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Button text">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Button URL</label>
                                <input type="url" name="bottom_col_section_${bottomColumnSectionCount}_btn_url" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="https://example.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                            <textarea name="bottom_col_section_${bottomColumnSectionCount}_content" rows="4" class="form-textarea shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="Enter column content"></textarea>
                        </div>
                    </div>
                `;
                
                container.appendChild(newSection);
                bottomColumnSectionCount++;
                document.getElementById('bottomColumnSectionCount').value = bottomColumnSectionCount;
                
                // Add remove functionality to the new section
                newSection.querySelector('.removeBottomColumnSection').addEventListener('click', function() {
                    newSection.remove();
                    updateBottomColumnSectionNumbers();
                });
            });
            
            // Dynamic floating buttons functionality
            let floatBtnCount = 1;
            
            document.getElementById('addFloatButton').addEventListener('click', function() {
                const container = document.getElementById('floatingButtonsContainer');
                const newButton = document.createElement('div');
                newButton.className = 'floating-button-item bg-gray-50 p-4 rounded border mb-4';
                newButton.innerHTML = `
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-md font-medium text-gray-800">Floating Button ${floatBtnCount + 1}</h4>
                        <button type="button" class="removeFloatButton text-red-600 hover:text-red-900" title="Remove Button">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Link</label>
                            <input type="text" name="float_btn_${floatBtnCount}_link" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0" placeholder="/path">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                            <input type="file" name="float_btn_${floatBtnCount}_upload" accept="image/*" class="form-input shadow-sm block w-full px-3 py-2.5 border-gray-300 rounded-md text-sm bg-white focus:outline-none focus:ring-0">
                            <p class="mt-1 text-xs text-gray-500">Upload button image (JPG, PNG, GIF, WebP, max 5MB)</p>
                        </div>
                    </div>
                `;
                
                container.appendChild(newButton);
                floatBtnCount++;
                document.getElementById('floatBtnCount').value = floatBtnCount;
                
                // Add remove functionality to the new button
                newButton.querySelector('.removeFloatButton').addEventListener('click', function() {
                    newButton.remove();
                    updateFloatButtonNumbers();
                });
            });
            
            // Add remove functionality to existing remove buttons
            document.addEventListener('click', function(e) {
                if (e.target.closest('.removeTopColumnSection')) {
                    const sectionItem = e.target.closest('.top-column-section-item');
                    if (document.querySelectorAll('.top-column-section-item').length > 1) {
                        sectionItem.remove();
                        updateTopColumnSectionNumbers();
                    } else {
                        alert('At least one column is required.');
                    }
                }
                
                if (e.target.closest('.removeBottomColumnSection')) {
                    const sectionItem = e.target.closest('.bottom-column-section-item');
                    if (document.querySelectorAll('.bottom-column-section-item').length > 1) {
                        sectionItem.remove();
                        updateBottomColumnSectionNumbers();
                    } else {
                        alert('At least one column is required.');
                    }
                }
                
                if (e.target.closest('.removeFloatButton')) {
                    const buttonItem = e.target.closest('.floating-button-item');
                    if (document.querySelectorAll('.floating-button-item').length > 1) {
                        buttonItem.remove();
                        updateFloatButtonNumbers();
                    } else {
                        alert('At least one floating button is required.');
                    }
                }
            });
            
            function updateTopColumnSectionNumbers() {
                const items = document.querySelectorAll('.top-column-section-item');
                topColumnSectionCount = items.length;
                document.getElementById('topColumnSectionCount').value = topColumnSectionCount;
                
                items.forEach((item, index) => {
                    // Update the heading
                    const heading = item.querySelector('h4');
                    heading.textContent = `Column ${index + 1}`;
                    
                    // Update input names
                    const inputs = item.querySelectorAll('input, textarea');
                    inputs.forEach(input => {
                        const fieldName = input.name.split('_').slice(-2).join('_'); // Get the last two parts (e.g., "title", "content", etc.)
                        input.name = `top_col_section_${index}_${fieldName}`;
                    });
                });
            }
            
            function updateBottomColumnSectionNumbers() {
                const items = document.querySelectorAll('.bottom-column-section-item');
                bottomColumnSectionCount = items.length;
                document.getElementById('bottomColumnSectionCount').value = bottomColumnSectionCount;
                
                items.forEach((item, index) => {
                    // Update the heading
                    const heading = item.querySelector('h4');
                    heading.textContent = `Column ${index + 1}`;
                    
                    // Update input names
                    const inputs = item.querySelectorAll('input, textarea');
                    inputs.forEach(input => {
                        const fieldName = input.name.split('_').slice(-2).join('_'); // Get the last two parts (e.g., "title", "content", etc.)
                        input.name = `bottom_col_section_${index}_${fieldName}`;
                    });
                });
            }
            
            function updateFloatButtonNumbers() {
                const items = document.querySelectorAll('.floating-button-item');
                floatBtnCount = items.length;
                document.getElementById('floatBtnCount').value = floatBtnCount;
                
                items.forEach((item, index) => {
                    // Update the heading
                    const heading = item.querySelector('h4');
                    heading.textContent = `Floating Button ${index + 1}`;
                    
                    // Update input names
                    const linkInput = item.querySelector('input[type="text"]');
                    const fileInput = item.querySelector('input[type="file"]');
                    
                    linkInput.name = `float_btn_${index}_link`;
                    fileInput.name = `float_btn_${index}_upload`;
                });
            }
        });
    </script>
</body>
</html>