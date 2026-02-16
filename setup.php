<?php
/**
 * Database Setup Script
 * Automatically imports fishing_app.sql if database is empty or doesn't exist
 */

$host = 'localhost';
$dbname = 'fishing_app';
$username = 'root';
$password = '';

try {
    // Connect without specifying database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "✓ Database '$dbname' ready\n";
    
    // Connect to the database
    $pdo->exec("USE `$dbname`");
    
    // Check if tables exist
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) === 0) {
        echo "→ Database is empty. Importing SQL file...\n";
        
        // Read SQL file
        $sqlFile = __DIR__ . '/database/fishing_app.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Remove comments and split by semicolons
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignore errors for statements like SET commands
                    if (strpos($statement, 'CREATE TABLE') !== false || 
                        strpos($statement, 'INSERT INTO') !== false) {
                        echo "Warning: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        
        echo "✓ Database imported successfully!\n";
        
        // Run migrations
        echo "→ Running migrations...\n";
        $migrationsDir = __DIR__ . '/migrations';
        if (is_dir($migrationsDir)) {
            $migrations = glob($migrationsDir . '/*.sql');
            foreach ($migrations as $migration) {
                echo "  → " . basename($migration) . "\n";
                $migrationSql = file_get_contents($migration);
                $migrationStatements = array_filter(array_map('trim', explode(';', $migrationSql)));
                
                foreach ($migrationStatements as $stmt) {
                    if (!empty($stmt)) {
                        try {
                            $pdo->exec($stmt);
                        } catch (PDOException $e) {
                            // Table might already exist, that's ok
                        }
                    }
                }
            }
            echo "✓ Migrations completed!\n";
        }
    } else {
        echo "✓ Database already contains " . count($tables) . " tables. Skipping import.\n";
        echo "  Tables: " . implode(', ', $tables) . "\n";
    }
    
    echo "\n✅ Setup completed! You can now use the application.\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
