<?php
// Load environment variables (if using .env file)
if (file_exists(__DIR__ . '/../.env')) {
    $envLines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

$host = $_ENV['DB_HOST'] ?? "localhost";
$db   = $_ENV['DB_NAME'] ?? "fishing_app";
$user = $_ENV['DB_USER'] ?? "root";
$pass = $_ENV['DB_PASS'] ?? "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // If database doesn't exist, try to set it up
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo '<div style="font-family: Arial; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; margin: 20px;">';
        echo '<h3 style="margin-top: 0;">⚠️ Database Not Found</h3>';
        echo '<p>The database needs to be set up. Please run the setup script:</p>';
        echo '<ol>';
        echo '<li>Open your browser and go to: <strong><a href="' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/setup.php">setup.php</a></strong></li>';
        echo '<li>Or run in terminal: <code>php setup.php</code></li>';
        echo '</ol>';
        echo '</div>';
        exit;
    }
    
    // Don't expose sensitive info in production
    error_log("DB Connection Error: " . $e->getMessage());
    http_response_code(500);
    die("Database connection failed. Please try again later.");
}
