<?php
header("Content-Type: application/json");
require_once '../config.php';

// Debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $conn = getDbConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate input
        if (empty($input['name'])) {
            throw new Exception("Category name is required");
        }

        if (empty($input['category_id'])) {
            // Insert new category
            $stmt = $conn->prepare("INSERT INTO categories (name, color, user_id) VALUES (?, ?, ?)");
            $stmt->execute([
                $input['name'],
                $input['color'] ?? '#4a6fa5',
                $_SESSION['user_id']
            ]);
            $category_id = $conn->lastInsertId();
        } else {
            // Update existing category
            $stmt = $conn->prepare("UPDATE categories SET name = ?, color = ? WHERE category_id = ? AND user_id = ?");
            $stmt->execute([
                $input['name'],
                $input['color'] ?? '#4a6fa5',
                $input['category_id'],
                $_SESSION['user_id']
            ]);
            $category_id = $input['category_id'];
        }

        // Return proper JSON response
        echo json_encode([
            'success' => true,
            'category_id' => $category_id
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}