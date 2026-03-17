<?php
// get_site_settings.php - Helper to fetch active site settings
require_once 'config.php';

function getActiveSiteSettings() {
    global $official_user_connection;
    
    $query = "SELECT * FROM site_settings WHERE is_active = 1 LIMIT 1";
    $result = $official_user_connection->query($query);
    
    if ($result && $result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        
        // Decode JSON fields if they exist
        if (isset($settings['top_column_sections'])) {
            $settings['top_column_sections'] = json_decode($settings['top_column_sections'], true) ?: [];
        }
        
        if (isset($settings['bottom_column_sections'])) {
            $settings['bottom_column_sections'] = json_decode($settings['bottom_column_sections'], true) ?: [];
        }
        
        if (isset($settings['floating_buttons'])) {
            $settings['floating_buttons'] = json_decode($settings['floating_buttons'], true) ?: [];
        }
        
        return $settings;
    }
    
    return [];
}
?>