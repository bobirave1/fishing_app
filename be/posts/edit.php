<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
    $postId = $_POST['id'] ?? null;
    
    if (!$postId) {
        http_response_code(400);
        exit('Post ID required');
    }

    // Fetch the post
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        http_response_code(404);
        exit('Post not found');
    }

    // Check if the user is the owner
    if ($post['user_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        exit('You can only edit your own posts');
    }

    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $visibility = $_POST['visibility'] ?? 'public';
    $imagePath = $post['image'];

    // Handle new file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        // Delete old image if it exists
        if (!empty($post['image']) && file_exists('../../' . $post['image'])) {
            unlink('../../' . $post['image']);
        }

        // Upload new image
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $target = '../../fe/assets/img/' . $filename;
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
        $imagePath = 'fe/assets/img/' . $filename;
    }

    // Update post
    $stmt = $pdo->prepare("
        UPDATE posts 
        SET title = ?, content = ?, image = ?, visibility = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$title, $content, $imagePath, $visibility, $postId]);

    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
    exit;
}
