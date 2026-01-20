<?php
session_start();
require '../config/database.php';

// All SQL statements to create missing tables
$sqls = [
    // Post likes table
    "CREATE TABLE IF NOT EXISTS post_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Post comments table
    "CREATE TABLE IF NOT EXISTS post_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Follows table
    "CREATE TABLE IF NOT EXISTS follows (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (follower_id, following_id),
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Messages table
    "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        content TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Activity feed table
    "CREATE TABLE IF NOT EXISTS activity_feed (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        related_id INT,
        post_id INT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    )",
    
    // Add sender_id column to notifications if not exists
    "ALTER TABLE notifications ADD COLUMN sender_id INT AFTER related_id",
    
    // Ensure waterbodies table exists with data
    "CREATE TABLE IF NOT EXISTS waterbodies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        type VARCHAR(100),
        location VARCHAR(255),
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Insert sample fishing spots if not exist
    "INSERT IGNORE INTO waterbodies (name, type, location) VALUES 
    ('Danube River', 'river', 'Bulgaria'),
    ('Black Sea', 'sea', 'Bulgaria'),
    ('Lake Iskar', 'lake', 'Bulgaria'),
    ('Arda River', 'river', 'Bulgaria')"
];

echo "<pre>";
foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ Executed: " . substr($sql, 0, 50) . "...\n";
    } catch (Exception $e) {
        // Some queries might fail if columns already exist, that's okay
        echo "ℹ Skipped: " . substr($e->getMessage(), 0, 50) . "...\n";
    }
}
echo "</pre>";
echo "<h3 style='color: green;'>✓ Database setup complete!</h3>";
echo "<p><a href='../index.php'>Go back to home</a></p>";
?>
