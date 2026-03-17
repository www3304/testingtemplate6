<?php
// Run database schema validation and management silently
ob_start();
require_once 'db_schema_manager.php';
ob_end_clean();
require_once 'get_site_settings.php';
$settings = getActiveSiteSettings();

// Get domain name for folder structure
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$domain_folder = preg_replace('/[^a-zA-Z0-9.-]/', '_', $domain);
$uploads = 'uploads/' . $domain_folder . '/';
$default_folder = 'uploads/default.png';

$floating_buttons = $settings['floating_buttons'] ?? [
    [
        'image' => $default_folder,
        'link' => 'https://google.com'
    ],
    [
        'image' => $default_folder,
        'link' => 'https://google.com'
    ],
    [
        'image' => $default_folder,
        'link' => 'https://google.com'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_title'] ?? 'Lorem'); ?></title>
    <!-- Favicon (Site Icon) -->
    <link rel="icon" href="<?php echo htmlspecialchars($settings['favicon_image'] ?? $default_folder); ?>" type="image/png">
    <!-- External CSS -->
    <link rel="preload" href="main.css" as="style">
    <link rel="stylesheet" href="main.css">
    <!-- Header Script (Google Analytics, etc.) -->
    <?php if (!empty($settings['header_script'])): ?>
        <?php echo $settings['header_script']; ?>
    <?php endif; ?>
</head>

<body>

    <header>
        <div class="logo">
            <a href="<?php echo htmlspecialchars($settings['logo_link'] ?? 'https://google.com'); ?>">
                <img src="<?php echo htmlspecialchars($settings['logo_image'] ?? $default_folder); ?>" alt="Site Logo">
            </a>
        </div>
    </header>

    <main class="main-container">
        <!-- Top Image -->
        <img src="<?php echo htmlspecialchars($settings['hero_image'] ?? $default_folder); ?>" alt="Main Content Image" class="hero-image" loading="lazy">

        <!-- Top Column Section -->
        <div class="dynamic-section">
            <?php 
            $top_sections = $settings['top_column_sections'] ?? [
                [
                    'title' => 'Lorem ipsum dolor sit amet',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'btn_text' => 'Lorem ipsum',
                    'btn_url' => 'https://google.com'
                ],
                [
                    'title' => 'Lorem ipsum dolor sit amet',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'btn_text' => 'Lorem ipsum',
                    'btn_url' => 'https://google.com'
                ],
            ];
            foreach ($top_sections as $section): ?>
            <div class="dynamic-section-card">
                <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                <p><?php echo htmlspecialchars($section['content']); ?></p>
                <a href="<?php echo htmlspecialchars($section['btn_url']); ?>" class="btn-primary"><?php echo htmlspecialchars($section['btn_text']); ?></a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Logo Section -->
        <div class="logo-section">
            <img src="<?php echo htmlspecialchars($settings['middle_logo_image'] ?? $default_folder); ?>" alt="Logo" loading="lazy">
        </div>

        <!-- Middle Section: Content -->
        <div class="middle-section">
            <h2><?php echo htmlspecialchars($settings['middle_section_title'] ?? 'Lorem ipsum dolor sit amet'); ?></h2>
           <?php 
            $rawContent = $settings['middle_section_content'] ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';

            // Normalize all line breaks to "\n"
            $normalized = str_replace(["\r\n", "\r"], "\n", $rawContent);

            // Split on blank lines (one or more newlines, possibly with spaces)
            $paragraphs = preg_split("/\n\s*\n/", $normalized);

            foreach ($paragraphs as $paragraph) {
                echo '<p>' . htmlspecialchars(trim($paragraph)) . '</p>';
            }
            ?>
        </div>

        <!-- Bottom Column Section -->
        <div class="dynamic-section">
            <?php 
            $bottom_sections = $settings['bottom_column_sections'] ?? [
                [
                    'title' => 'Lorem ipsum dolor sit amet',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'btn_text' => 'Lorem ipsum',
                    'btn_url' => 'https://google.com'
                ],
                [
                    'title' => 'Lorem ipsum dolor sit amet',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'btn_text' => 'Lorem ipsum',
                    'btn_url' => 'https://google.com'
                ],
                [
                    'title' => 'Lorem ipsum dolor sit amet',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'btn_text' => 'Lorem ipsum',
                    'btn_url' => 'https://google.com'
                ],
            ];
            foreach ($bottom_sections as $section): ?>
            <div class="dynamic-section-card">
                <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                <p><?php echo htmlspecialchars($section['content']); ?></p>
                <a href="<?php echo htmlspecialchars($section['btn_url']); ?>" class="btn-primary"><?php echo htmlspecialchars($section['btn_text']); ?></a>
            </div>
            <?php endforeach; ?>
        </div>

    </main>

    <!-- Footer Section -->
    <footer>
        <div>
            <!-- Item 1 -->
            <div>
                <a href="<?php echo htmlspecialchars($settings['footer_item1_url'] ?? 'https://google.com'); ?>">
                    <img src="<?php echo htmlspecialchars($settings['footer_item1_image'] ?? $default_folder); ?>" alt="Item 1" loading="lazy">
                </a>
            </div>

            <!-- Item 2 -->
            <div>
                <a href="<?php echo htmlspecialchars($settings['footer_item2_url'] ?? 'https://google.com'); ?>">
                    <img src="<?php echo htmlspecialchars($settings['footer_item2_image'] ?? $default_folder); ?>" alt="Item 2" loading="lazy">
                </a>
            </div>

            <!-- Item 3 -->
            <div>
                <a href="<?php echo htmlspecialchars($settings['footer_item3_url'] ?? 'https://google.com'); ?>">
                    <img src="<?php echo htmlspecialchars($settings['footer_item3_image'] ?? $default_folder); ?>" alt="Item 3" loading="lazy">
                </a>
            </div>

            <!-- Item 4 -->
            <div>
                <a href="<?php echo htmlspecialchars($settings['footer_item4_url'] ?? 'https://google.com'); ?>">
                    <img src="<?php echo htmlspecialchars($settings['footer_item4_image'] ?? $default_folder); ?>" alt="Item 4" loading="lazy">
                </a>
            </div>

            <!-- Item 5 -->
            <div>
                <a href="<?php echo htmlspecialchars($settings['footer_item5_url'] ?? 'https://google.com'); ?>">
                    <img src="<?php echo htmlspecialchars($settings['footer_item5_image'] ?? $default_folder); ?>" alt="Item 5" loading="lazy">
                </a>
            </div>
        </div>
    </footer>

    <!-- Dynamic Floating Action Buttons -->
    <!-- (Removed PHP loop for floating buttons; JS will handle rendering) -->

    <!-- External JavaScript -->
    <script src="script.js" defer></script>
    
    <!-- Pass PHP data to JavaScript -->
    <script>
        // Pass PHP settings to JavaScript for dynamic styling
        window.siteSettings = {
            primaryBgColor: '<?php echo htmlspecialchars($settings['primary_bg_color'] ?? '#000000'); ?>',
            secondaryBgColor: '<?php echo htmlspecialchars($settings['secondary_bg_color'] ?? '#000000'); ?>',
            headerBgStart: '<?php echo htmlspecialchars($settings['header_bg_start'] ?? '#000000'); ?>',
            headerBgEnd: '<?php echo htmlspecialchars($settings['header_bg_end'] ?? '#2C2C2C'); ?>',
            footerBgStart: '<?php echo htmlspecialchars($settings['footer_bg_start'] ?? '#000000'); ?>',
            footerBgEnd: '<?php echo htmlspecialchars($settings['footer_bg_end'] ?? '#2C2C2C'); ?>',
            cardBgColor: '<?php echo htmlspecialchars($settings['card_bg_color'] ?? '#252525'); ?>',
            textColor: '<?php echo htmlspecialchars($settings['text_color'] ?? 'white'); ?>',
            accentColor: '<?php echo htmlspecialchars($settings['accent_color'] ?? '#F6D02C'); ?>',
            borderColor: '<?php echo htmlspecialchars($settings['border_color'] ?? 'white'); ?>',
            floatingButtons: <?php echo json_encode($floating_buttons); ?>
        };
    </script>
</body>
</html>