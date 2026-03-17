<?php
// db_schema_manager.php

// Report all types of errors
error_reporting(E_ALL);
// Display errors on the screen
ini_set('display_errors', 1);
// Display startup errors
ini_set('display_startup_errors', 1);

echo "<pre>"; // For readable output in browser

// Include the database configuration and connection
// This should make $official_user_connection available
require_once __DIR__ . '/config.php';

if (!isset($official_user_connection) || !($official_user_connection instanceof mysqli)) {
    die("Failed to establish official_user_connection from config.php. Please ensure config.php sets up this mysqli connection object.");
}

echo "Successfully included config.php and official_user_connection is available.\n";

// --- Expected Schema Definition ---
$expected_schema = [
    'admins' => [
        'columns' => [
            'id'         => ['type' => 'BIGINT UNSIGNED', 'nullable' => false, 'extra' => 'AUTO_INCREMENT'],
            'username'   => ['type' => 'VARCHAR(255)', 'nullable' => false, 'unique' => true],
            'password'   => ['type' => 'VARCHAR(255)', 'nullable' => false],
            'created_at' => ['type' => 'TIMESTAMP', 'nullable' => true, 'default' => 'CURRENT_TIMESTAMP'],
            'updated_at' => ['type' => 'TIMESTAMP', 'nullable' => true, 'default' => 'CURRENT_TIMESTAMP', 'extra' => 'ON UPDATE CURRENT_TIMESTAMP'],
        ],
        'primary_key' => ['id'],
        'engine' => 'InnoDB'
    ],
    'users' => [
        'columns' => [
            'id'         => ['type' => 'BIGINT UNSIGNED', 'nullable' => false, 'extra' => 'AUTO_INCREMENT'],
            'username'   => ['type' => 'VARCHAR(255)', 'nullable' => false, 'unique' => true],
            'password'   => ['type' => 'VARCHAR(255)', 'nullable' => false],
            'created_at' => ['type' => 'TIMESTAMP', 'nullable' => true, 'default' => 'CURRENT_TIMESTAMP'],
            'updated_at' => ['type' => 'TIMESTAMP', 'nullable' => true, 'default' => 'CURRENT_TIMESTAMP', 'extra' => 'ON UPDATE CURRENT_TIMESTAMP'],
        ],
        'primary_key' => ['id'],
        'engine' => 'InnoDB'
    ],
    'api_settings' => [
        'columns' => [
            'id'           => ['type' => 'BIGINT UNSIGNED', 'nullable' => false, 'extra' => 'AUTO_INCREMENT'],
            'access_key'   => ['type' => 'VARCHAR(255)', 'nullable' => false, 'unique' => true],
            'access_token' => ['type' => 'VARCHAR(255)', 'nullable' => false],
            'name'         => ['type' => 'VARCHAR(255)', 'nullable' => true, 'default' => null],
            'is_active'    => ['type' => 'TINYINT(1)', 'nullable' => true, 'default' => 1],
            'created_at'   => ['type' => 'TIMESTAMP', 'nullable' => true, 'default' => 'CURRENT_TIMESTAMP'],
            'updated_at'   => ['type' => 'TIMESTAMP', 'nullable' => true, 'default' => 'CURRENT_TIMESTAMP', 'extra' => 'ON UPDATE CURRENT_TIMESTAMP'],
        ],
        'primary_key' => ['id'],
        'engine' => 'InnoDB'
    ],
    'site_settings' => [
        'columns' => [
            'id'                    => ['type' => 'BIGINT UNSIGNED', 'nullable' => false, 'extra' => 'AUTO_INCREMENT'],
            
            // Site Meta
            'site_title'            => ['type' => 'VARCHAR(255)', 'nullable' => false, 'default' => 'BMB99'],
            'favicon_image'           => ['type' => 'TEXT', 'nullable' => true],
            'logo_image'              => ['type' => 'TEXT', 'nullable' => true],
            'logo_link'             => ['type' => 'TEXT', 'nullable' => true],
            'hero_image'        => ['type' => 'TEXT', 'nullable' => true],
            'header_script'         => ['type' => 'TEXT', 'nullable' => true],
            
            // Color Scheme
            'primary_bg_color'      => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => '#000000'],
            'secondary_bg_color'    => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => '#000000'],
            'header_bg_start'       => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => '#000000'],
            'header_bg_end'         => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => '#2C2C2C'],
            'footer_bg_start'       => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => '#000000'],
            'footer_bg_end'         => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => '#2C2C2C'],
            'card_bg_color'         => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => '#252525'],
            'text_color'            => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => 'white'],
            'accent_color'          => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => '#F6D02C'],
            'border_color'          => ['type' => 'VARCHAR(50)', 'nullable' => true, 'default' => 'white'],
            
            // Dynamic Top Column Section (JSON format for unlimited columns)
            'top_column_sections'   => ['type' => 'JSON', 'nullable' => true],
            
            // Middle Section (Single section)
            'middle_logo_image'       => ['type' => 'TEXT', 'nullable' => true],
            'middle_section_title'  => ['type' => 'VARCHAR(500)', 'nullable' => true],
            'middle_section_content'=> ['type' => 'TEXT', 'nullable' => true],
            
            // Dynamic Bottom Column Section (JSON format for unlimited bonus sections)
            'bottom_column_sections'=> ['type' => 'JSON', 'nullable' => true],
            
            // Footer Items
            'footer_item1_url'      => ['type' => 'TEXT', 'nullable' => true],
            'footer_item1_image'    => ['type' => 'TEXT', 'nullable' => true],
            'footer_item2_url'      => ['type' => 'TEXT', 'nullable' => true],
            'footer_item2_image'    => ['type' => 'TEXT', 'nullable' => true],
            'footer_item3_url'      => ['type' => 'TEXT', 'nullable' => true],
            'footer_item3_image'    => ['type' => 'TEXT', 'nullable' => true],
            'footer_item4_url'      => ['type' => 'TEXT', 'nullable' => true],
            'footer_item4_image'    => ['type' => 'TEXT', 'nullable' => true],
            'footer_item5_url'      => ['type' => 'TEXT', 'nullable' => true],
            'footer_item5_image'    => ['type' => 'TEXT', 'nullable' => true],
            
            // Floating Action Buttons (JSON format for dynamic buttons)
            'floating_buttons'      => ['type' => 'JSON', 'nullable' => true],
            
            // Status
            'is_active'             => ['type' => 'TINYINT(1)', 'nullable' => false, 'default' => 1],
            
            'created_at'            => ['type' => 'TIMESTAMP', 'nullable' => true, 'default' => 'CURRENT_TIMESTAMP'],
            'updated_at'            => ['type' => 'TIMESTAMP', 'nullable' => true, 'default' => 'CURRENT_TIMESTAMP', 'extra' => 'ON UPDATE CURRENT_TIMESTAMP'],
        ],
        'primary_key' => ['id'],
        'engine' => 'InnoDB'
    ],
];

// --- Helper Functions ---

/**
 * Gets the current database name
 * @param mysqli $conn
 * @return string|false Database name or false on error
 */
function getDatabaseName(mysqli $conn)
{
    $dbNameResult = $conn->query("SELECT DATABASE()");
    if (!$dbNameResult) {
        echo "Error getting database name: " . $conn->error . "\n";
        return false;
    }
    $dbName = $dbNameResult->fetch_row()[0];
    $dbNameResult->free();
    return $dbName;
}

/**
 * Checks if a table exists in the database.
 * @param mysqli $conn
 * @param string $tableName
 * @return bool
 */
function tableExists(mysqli $conn, string $tableName): bool
{
    $dbName = getDatabaseName($conn);
    if (!$dbName) return false;

    $stmt = $conn->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
    if (!$stmt) {
        echo "Error preparing tableExists statement: " . $conn->error . "\n";
        return false;
    }
    $stmt->bind_param("ss", $dbName, $tableName);
    $stmt->execute();
    if ($stmt->error) {
        echo "Error executing tableExists statement: " . $stmt->error . "\n";
        $stmt->close();
        return false;
    }
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Gets all columns that exist in a table.
 * @param mysqli $conn
 * @param string $tableName
 * @return array Array of column names
 */
function getExistingColumns(mysqli $conn, string $tableName): array
{
    $dbName = getDatabaseName($conn);
    if (!$dbName) return [];

    $stmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION");
    if (!$stmt) {
        echo "Error preparing getExistingColumns statement: " . $conn->error . "\n";
        return [];
    }
    $stmt->bind_param("ss", $dbName, $tableName);
    $stmt->execute();
    if ($stmt->error) {
        echo "Error executing getExistingColumns statement: " . $stmt->error . "\n";
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['COLUMN_NAME'];
    }
    $stmt->close();
    return $columns;
}

/**
 * Gets detailed column information for comparison
 * @param mysqli $conn
 * @param string $tableName
 * @return array Array of column details
 */
function getColumnDetails(mysqli $conn, string $tableName): array
{
    $dbName = getDatabaseName($conn);
    if (!$dbName) return [];

    $stmt = $conn->prepare("
        SELECT 
            COLUMN_NAME,
            COLUMN_TYPE,
            IS_NULLABLE,
            COLUMN_DEFAULT,
            EXTRA,
            CHARACTER_SET_NAME,
            COLLATION_NAME
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? 
        ORDER BY ORDINAL_POSITION
    ");

    if (!$stmt) {
        echo "Error preparing getColumnDetails statement: " . $conn->error . "\n";
        return [];
    }

    $stmt->bind_param("ss", $dbName, $tableName);
    $stmt->execute();

    if ($stmt->error) {
        echo "Error executing getColumnDetails statement: " . $stmt->error . "\n";
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['COLUMN_NAME']] = $row;
    }
    $stmt->close();
    return $columns;
}

/**
 * Validates if column definition matches expected schema
 * @param array $existingColumn
 * @param array $expectedColumn
 * @return array Array of differences
 */
function validateColumnDefinition(array $existingColumn, array $expectedColumn): array
{
    $differences = [];

    // Check data type
    $expectedType = strtoupper($expectedColumn['type']);
    $existingType = strtoupper($existingColumn['COLUMN_TYPE']);
    if ($expectedType !== $existingType) {
        $differences['type'] = [
            'expected' => $expectedType,
            'actual' => $existingType
        ];
    }

    // Check nullable
    $expectedNullable = isset($expectedColumn['nullable']) ? ($expectedColumn['nullable'] ? 'YES' : 'NO') : 'YES';
    if ($expectedNullable !== $existingColumn['IS_NULLABLE']) {
        $differences['nullable'] = [
            'expected' => $expectedNullable,
            'actual' => $existingColumn['IS_NULLABLE']
        ];
    }

    // Check default value
    if (array_key_exists('default', $expectedColumn)) {
        $expectedDefault = $expectedColumn['default'];
        $actualDefault = $existingColumn['COLUMN_DEFAULT'];

        // Handle special cases for CURRENT_TIMESTAMP
        if (is_string($expectedDefault) && strtoupper($expectedDefault) === 'CURRENT_TIMESTAMP') {
            if ($actualDefault !== 'CURRENT_TIMESTAMP') {
                $differences['default'] = [
                    'expected' => $expectedDefault,
                    'actual' => $actualDefault
                ];
            }
        } elseif ($expectedDefault != $actualDefault) {
            $differences['default'] = [
                'expected' => $expectedDefault,
                'actual' => $actualDefault
            ];
        }
    }

    // Check charset and collation for text columns
    if (isset($expectedColumn['charset']) && $existingColumn['CHARACTER_SET_NAME'] !== null) {
        if ($expectedColumn['charset'] !== $existingColumn['CHARACTER_SET_NAME']) {
            $differences['charset'] = [
                'expected' => $expectedColumn['charset'],
                'actual' => $existingColumn['CHARACTER_SET_NAME']
            ];
        }
    }

    if (isset($expectedColumn['collation']) && $existingColumn['COLLATION_NAME'] !== null) {
        if ($expectedColumn['collation'] !== $existingColumn['COLLATION_NAME']) {
            $differences['collation'] = [
                'expected' => $expectedColumn['collation'],
                'actual' => $existingColumn['COLLATION_NAME']
            ];
        }
    }

    return $differences;
}

/**
 * Builds the SQL definition for a single column.
 * @param string $columnName
 * @param array $colDef
 * @param mysqli $conn Database connection for escaping strings
 * @return string
 */
function buildColumnDefinitionSQL(string $columnName, array $colDef, mysqli $conn): string
{
    $sql = "`{$columnName}` {$colDef['type']}";

    if (!empty($colDef['charset'])) {
        $sql .= " CHARACTER SET {$colDef['charset']}";
    }
    if (!empty($colDef['collation'])) {
        $sql .= " COLLATE {$colDef['collation']}";
    }

    if (isset($colDef['nullable'])) {
        $sql .= $colDef['nullable'] ? " NULL" : " NOT NULL";
    }

    if (array_key_exists('default', $colDef)) {
        if ($colDef['default'] === null) {
            // For "DEFAULT NULL" to be valid, the column must be nullable.
            if (!empty($colDef['nullable'])) {
                $sql .= " DEFAULT NULL";
            }
        } elseif (is_string($colDef['default']) && in_array(strtoupper($colDef['default']), ['CURRENT_TIMESTAMP', 'NOW()'])) {
            $sql .= " DEFAULT " . strtoupper($colDef['default']);
        } elseif (is_numeric($colDef['default']) && !is_string($colDef['default'])) {
            $sql .= " DEFAULT " . $colDef['default'];
        } else {
            $sql .= " DEFAULT '" . $conn->real_escape_string((string)$colDef['default']) . "'";
        }
    }

    if (!empty($colDef['extra'])) {
        $sql .= " " . $colDef['extra'];
    }

    if (!empty($colDef['unique'])) {
        $sql .= " UNIQUE";
    }
    
    return $sql;
}


// --- Main Logic ---
echo "\n--- Starting Schema Validation and Management ---\n\n";

// First, get all existing tables to understand current state
$dbName = getDatabaseName($official_user_connection);
if (!$dbName) {
    die("Failed to get database name. Cannot proceed.\n");
}

$existingTablesResult = $official_user_connection->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_TYPE = 'BASE TABLE'");
$currentTables = [];
if ($existingTablesResult) {
    while ($row = $existingTablesResult->fetch_assoc()) {
        $currentTables[] = $row['TABLE_NAME'];
    }
    $existingTablesResult->free();
}

$expectedTables = array_keys($expected_schema);
$missingTables = array_diff($expectedTables, $currentTables);
$extraTables = array_diff($currentTables, $expectedTables);

echo "Current database state:\n";
echo "- Expected tables: " . count($expectedTables) . " (" . implode(', ', $expectedTables) . ")\n";
echo "- Existing tables: " . count($currentTables) . " (" . implode(', ', $currentTables) . ")\n";
echo "- Missing tables: " . count($missingTables) . " (" . implode(', ', $missingTables) . ")\n";
echo "- Extra tables: " . count($extraTables) . " (" . implode(', ', $extraTables) . ")\n\n";

foreach ($expected_schema as $tableName => $tableDef) {
    echo "Processing table: `{$tableName}`...\n";

    if (!tableExists($official_user_connection, $tableName)) {
        echo "Table `{$tableName}` does not exist. Creating...\n";
        $columnsSQL = [];
        foreach ($tableDef['columns'] as $columnName => $colDef) {
            $columnsSQL[] = "  " . buildColumnDefinitionSQL($columnName, $colDef, $official_user_connection);
        }

        $createTableSQL = "CREATE TABLE `{$tableName}` (\n" . implode(",\n", $columnsSQL);

        if (!empty($tableDef['primary_key'])) {
            $pkColumns = array_map(function ($col) {
                return "`{$col}`";
            }, $tableDef['primary_key']);
            $createTableSQL .= ",\n  PRIMARY KEY (" . implode(", ", $pkColumns) . ")";
        }

        $createTableSQL .= "\n)";

        if (!empty($tableDef['engine'])) {
            $createTableSQL .= " ENGINE={$tableDef['engine']}";
        }
        if (!empty($tableDef['charset'])) {
            $createTableSQL .= " DEFAULT CHARSET={$tableDef['charset']}";
        }
        if (!empty($tableDef['collation'])) {
            $createTableSQL .= " COLLATE={$tableDef['collation']}";
        }
        $createTableSQL .= ";";

        echo "Executing SQL: \n{$createTableSQL}\n";
        if ($official_user_connection->query($createTableSQL) === TRUE) {
            echo "Table `{$tableName}` created successfully.\n\n";
        } else {
            echo "Error creating table `{$tableName}`: " . $official_user_connection->error . "\n\n";
        }
    } else {
        echo "Table `{$tableName}` exists. Performing detailed column analysis...\n";
        $existingColumns = getExistingColumns($official_user_connection, $tableName);
        $existingColumnDetails = getColumnDetails($official_user_connection, $tableName);
        $expectedColumns = array_keys($tableDef['columns']);

        $missingColumns = array_diff($expectedColumns, $existingColumns);
        $extraColumns = array_diff($existingColumns, $expectedColumns);
        $commonColumns = array_intersect($expectedColumns, $existingColumns);

        echo "  - Expected columns: " . count($expectedColumns) . "\n";
        echo "  - Existing columns: " . count($existingColumns) . "\n";
        echo "  - Missing columns: " . count($missingColumns) . " (" . implode(', ', $missingColumns) . ")\n";
        echo "  - Extra columns: " . count($extraColumns) . " (" . implode(', ', $extraColumns) . ")\n";
        echo "  - Common columns: " . count($commonColumns) . "\n\n";

        // Add missing columns
        if (!empty($missingColumns)) {
            echo "Adding missing columns to `{$tableName}`:\n";
            foreach ($missingColumns as $columnName) {
                $colDef = $tableDef['columns'][$columnName];
                echo "  Adding column `{$columnName}`...\n";
                $alterTableSQL = "ALTER TABLE `{$tableName}` ADD COLUMN " . buildColumnDefinitionSQL($columnName, $colDef, $official_user_connection) . ";";

                echo "  Executing SQL: {$alterTableSQL}\n";
                if ($official_user_connection->query($alterTableSQL) === TRUE) {
                    echo "  ✓ Column `{$columnName}` added successfully.\n\n";
                } else {
                    echo "  ✗ Error adding column `{$columnName}`: " . $official_user_connection->error . "\n\n";
                }
            }
        }

        // Validate existing columns for definition mismatches
        if (!empty($commonColumns)) {
            echo "Validating existing columns in `{$tableName}`:\n";
            foreach ($commonColumns as $columnName) {
                $expectedDef = $tableDef['columns'][$columnName];
                $existingDef = $existingColumnDetails[$columnName];
                $differences = validateColumnDefinition($existingDef, $expectedDef);

                if (!empty($differences)) {
                    echo "  Column `{$columnName}` has definition mismatches:\n";
                    foreach ($differences as $aspect => $diff) {
                        echo "    - {$aspect}: expected '{$diff['expected']}', actual '{$diff['actual']}'\n";
                    }

                    // Attempt to modify the column
                    echo "  Modifying column `{$columnName}`...\n";
                    $modifySQL = "ALTER TABLE `{$tableName}` MODIFY COLUMN " . buildColumnDefinitionSQL($columnName, $expectedDef, $official_user_connection) . ";";
                    echo "  Executing SQL: {$modifySQL}\n";

                    if ($official_user_connection->query($modifySQL) === TRUE) {
                        echo "  ✓ Column `{$columnName}` modified successfully.\n\n";
                    } else {
                        echo "  ✗ Error modifying column `{$columnName}`: " . $official_user_connection->error . "\n\n";
                    }
                } else {
                    echo "  ✓ Column `{$columnName}` definition matches expected schema.\n";
                }
            }
            echo "\n";
        }

        // Remove extra columns
        if (!empty($extraColumns)) {
            echo "Removing extra columns from `{$tableName}`:\n";
            foreach ($extraColumns as $columnName) {
                echo "  Removing column `{$columnName}`...\n";
                $alterTableSQL = "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`;";

                echo "  Executing SQL: {$alterTableSQL}\n";
                if ($official_user_connection->query($alterTableSQL) === TRUE) {
                    echo "  ✓ Column `{$columnName}` removed successfully.\n\n";
                } else {
                    echo "  ✗ Error removing column `{$columnName}`: " . $official_user_connection->error . "\n\n";
                }
            }
        }

        echo "Finished detailed analysis for `{$tableName}`.\n\n";
    }
}

// --- Remove Extra Tables (not in expected schema) ---
echo "\n--- Removing Extra Tables ---\n\n";

if (!empty($extraTables)) {
    echo "Found " . count($extraTables) . " extra table(s) not in expected schema.\n";
    echo "Removing extra tables...\n\n";

    foreach ($extraTables as $tableName) {
        echo "Dropping extra table `{$tableName}`...\n";
        $dropTableSQL = "DROP TABLE `{$tableName}`;";
        echo "Executing SQL: \n{$dropTableSQL}\n";

        if ($official_user_connection->query($dropTableSQL) === TRUE) {
            echo "Table `{$tableName}` dropped successfully.\n\n";
        } else {
            echo "Error dropping table `{$tableName}`: " . $official_user_connection->error . "\n\n";
        }
    }
} else {
    echo "No extra tables found. Database schema matches expected tables.\n\n";
}


// --- Final Summary ---
echo "\n--- Final Schema Validation Summary ---\n\n";

// Re-check the current state after all operations
$finalTablesResult = $official_user_connection->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_TYPE = 'BASE TABLE'");
$finalTables = [];
if ($finalTablesResult) {
    while ($row = $finalTablesResult->fetch_assoc()) {
        $finalTables[] = $row['TABLE_NAME'];
    }
    $finalTablesResult->free();
}

$finalMissingTables = array_diff($expectedTables, $finalTables);
$finalExtraTables = array_diff($finalTables, $expectedTables);

echo "Final database state:\n";
echo "- Expected tables: " . count($expectedTables) . "\n";
echo "- Actual tables: " . count($finalTables) . "\n";
echo "- Missing tables: " . count($finalMissingTables) . " (" . implode(', ', $finalMissingTables) . ")\n";
echo "- Extra tables: " . count($finalExtraTables) . " (" . implode(', ', $finalExtraTables) . ")\n\n";

if (empty($finalMissingTables) && empty($finalExtraTables)) {
    echo "✅ SUCCESS: Database schema is now fully synchronized with expected schema!\n";

    // Check if all columns are also correct
    $allColumnsCorrect = true;
    foreach ($expectedTables as $tableName) {
        if (in_array($tableName, $finalTables)) {
            $existingColumns = getExistingColumns($official_user_connection, $tableName);
            $expectedColumns = array_keys($expected_schema[$tableName]['columns']);
            if (array_diff($expectedColumns, $existingColumns) || array_diff($existingColumns, $expectedColumns)) {
                $allColumnsCorrect = false;
                break;
            }
        }
    }

    if ($allColumnsCorrect) {
        echo "✅ All tables and columns are correctly synchronized.\n";
    } else {
        echo "⚠️  Some column mismatches may still exist. Please review the detailed output above.\n";
    }
} else {
    echo "❌ WARNING: Database schema synchronization incomplete!\n";
    if (!empty($finalMissingTables)) {
        echo "   Missing tables: " . implode(', ', $finalMissingTables) . "\n";
    }
    if (!empty($finalExtraTables)) {
        echo "   Extra tables: " . implode(', ', $finalExtraTables) . "\n";
    }
    echo "   Please review the errors above and run the script again.\n";
}

echo "\n--- Schema Validation and Management Complete ---\n";
echo "</pre>";

// Consider closing the connection if your config.php doesn't handle it or if the script is long-running.
// if ($official_user_connection) {
//    $official_user_connection->close();
// }