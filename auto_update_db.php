<?php
require_once 'config.php';
require_once 'auto_sync_data.php';

/**
 * 自动同步 company schema
 * - 自动创建不存在的表
 * - 自动补齐缺失的 column
 * - 不会删除任何数据
 * - Schema 取自 latest_schema.sql
 */
function autoSyncCompanySchema(PDO $pdo, string $prefix)
{
    $schemaFile = __DIR__ . '/latest_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $sql = file_get_contents($schemaFile);
    //echo "<b>Schema file loaded, length:</b> " . strlen($sql) . "<br>";

    preg_match_all('/CREATE TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?(\w+)`?\s*\(([\s\S]*?)\)\s*;/i', $sql, $matches, PREG_SET_ORDER);

    //echo "<b>Matched tables:</b> " . count($matches) . "<br>";

    if (empty($matches)) {
        echo "⚠️ No CREATE TABLE found in latest_schema.sql<br>";
    }

    foreach ($matches as $match) {
        $baseTable = $match[1];
        $definition = trim($match[2]);
        $tableName = $prefix . $baseTable;

        //echo "<hr><b>Checking table:</b> $tableName<br>";

        // 清除主键/索引部分
        $definition_clean = preg_replace('/,(?:\s*(?:PRIMARY|UNIQUE|KEY|CONSTRAINT)[^,]*)+/i', '', $definition);

        // ✅ 改良版字段匹配，支持 ENUM, DEFAULT, COMMENT 等含逗号定义
        preg_match_all('/`?(\w+)`?\s+((?:[^,\(\)]|\([^\)]*\))+)(?:,|$)/', $definition_clean, $cols, PREG_SET_ORDER);

        //echo "➡️ Found " . count($cols) . " columns in schema for $baseTable<br>";

        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        $exists = $stmt->rowCount() > 0;

        if (!$exists) {
            $pdo->exec("CREATE TABLE `$tableName` ($definition) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✅ Created missing table: $tableName<br>";
        } else {
            foreach ($cols as $col) {
                $colName = $col[1];
                $colDef = trim($col[2]);

                if (stripos($colDef, 'PRIMARY KEY') !== false || stripos($colDef, 'KEY ') === 0) {
                    continue;
                }

                $colCheck = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
                $colCheck->execute([$colName]);

                if ($colCheck->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE `$tableName` ADD COLUMN `$colName` $colDef");
                    echo "✅ Added missing column: $tableName.$colName<br>";
                } else {
                    //echo "🟢 Column exists: $tableName.$colName<br>";
                }
            }
        }
    }

    // ✅ 接着补默认数据
    autoSyncDefaultData($pdo, $prefix);
}
