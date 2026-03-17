<?php
function autoSyncDefaultData(PDO $pdo, string $prefix)
{
    //echo "<hr>🧩 Syncing default data for prefix: $prefix<br>";

    // === Default sections ===
    $defaultSections = [
        'about' => 'active',
        'features' => 'active',
        'provide' => 'active',
        'gallery' => 'active',
        'video' => 'active',
        'contact' => 'active',
        'address' => 'active',
        'subnav' => 'inactive',
        'blog' => 'inactive',
    ];

    foreach ($defaultSections as $key => $status) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}companySections WHERE section_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetchColumn() == 0) {
            $insert = $pdo->prepare("INSERT INTO {$prefix}companySections (section_key, status) VALUES (?, ?)");
            $insert->execute([$key, $status]);
            echo "✅ Inserted missing section: $key<br>";
        }
    }

    // === Default language ===
    $defaultLang = 'english';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}companyLanguages WHERE language = ?");
    $stmt->execute([$defaultLang]);
    if ($stmt->fetchColumn() == 0) {
        $insert = $pdo->prepare("INSERT INTO {$prefix}companyLanguages (language) VALUES (?)");
        $insert->execute([$defaultLang]);
        echo "✅ Inserted default language: $defaultLang<br>";
    }

    // === Default carousels ===
    $defaultCarousels = [
        ['about', 'About Carousel'],
        ['features', 'Features Carousel'],
        ['provide', 'Provides Carousel'],
        ['gallery', 'Gallery Carousel'],
        ['video', 'Videos Carousel'],
    ];

    foreach ($defaultCarousels as [$section, $title]) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}companyCarousel WHERE section = ?");
        $stmt->execute([$section]);
        if ($stmt->fetchColumn() == 0) {
            $insert = $pdo->prepare("INSERT INTO {$prefix}companyCarousel (company_id, section, title, language_id) VALUES (1, ?, ?, 1)");
            $insert->execute([$section, $title]);
            echo "✅ Inserted missing carousel: $section<br>";
        }
    }

    //echo "🟢 Default data sync complete.<br>";
}
