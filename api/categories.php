<?php
// Start output buffering to catch any unwanted output
ob_start();

header("Content-Type: application/json");
require_once '../config.php';

// Disable error display in production - log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }
    
    $conn = getDbConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle GET request - fetch categories
        $sql = "SELECT 
                    category_id,
                    name,
                    color,
                    created_at
                FROM categories 
                WHERE user_id = :user_id
                ORDER BY name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Clear any unwanted output
        ob_clean();
        
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON input
        $inputRaw = file_get_contents('php://input');
        
        // Debug logging - remove this in production
        error_log("Raw input: " . $inputRaw);
        
        if (empty($inputRaw)) {
            throw new Exception("No input data received");
        }
        
        $input = json_decode($inputRaw, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON input: " . json_last_error_msg());
        }
        
        // Debug logging - remove this in production
        error_log("Parsed input: " . print_r($input, true));
        
        // Validate required fields
        if (empty($input['name']) || trim($input['name']) === '') {
            throw new Exception("Category name is required");
        }
        
        $name = trim($input['name']);
        $color = isset($input['color']) ? $input['color'] : '#4a6fa5';
        
        // Validate color format (optional but recommended)
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#4a6fa5'; // Default color if invalid
        }
        
        // Check if category name already exists for this user (excluding current category when editing)
        if (empty($input['category_id'])) {
            // When adding new category, check for any duplicate
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = :name AND user_id = :user_id");
            $checkStmt->execute([':name' => $name, ':user_id' => $_SESSION['user_id']]);
        } else {
            // When editing existing category, exclude the current category from duplicate check
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = :name AND user_id = :user_id AND category_id != :category_id");
            $checkStmt->execute([
                ':name' => $name, 
                ':user_id' => $_SESSION['user_id'],
                ':category_id' => $input['category_id']
            ]);
        }
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("A category with this name already exists");
        }
        
        // Prepare SQL based on category_id
        if (empty($input['category_id'])) {
            // Insert new category
            $sql = "INSERT INTO categories (name, color, user_id, created_at)
                    VALUES (:name, :color, :user_id, NOW())";
            
            $stmt = $conn->prepare($sql);
            $params = [
                ':name' => $name,
                ':color' => $color,
                ':user_id' => $_SESSION['user_id']
            ];
            
            $stmt->execute($params);
            $category_id = $conn->lastInsertId();
            
        } else {
            // Update existing category
            $sql = "UPDATE categories SET
                        name = :name,
                        color = :color
                    WHERE category_id = :category_id AND user_id = :user_id";
            
            $stmt = $conn->prepare($sql);
            $params = [
                ':name' => $name,
                ':color' => $color,
                ':category_id' => $input['category_id'],
                ':user_id' => $_SESSION['user_id']
            ];
            
            $stmt->execute($params);
            $category_id = $input['category_id'];
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Category not found or access denied");
            }
        }
        
        // Clear any unwanted output
        ob_clean();
        
        // Return proper JSON response
        echo json_encode([
            'success' => true,
            'category_id' => $category_id,
            'message' => 'Category saved successfully'
        ]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Handle DELETE request
        parse_str(file_get_contents('php://input'), $input);
        
        if (empty($input['category_id'])) {
            throw new Exception("Category ID is required");
        }
        
        // First check if category is in use
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM task_categories WHERE category_id = ?");
        $checkStmt->execute([$input['category_id']]);
        $taskCount = $checkStmt->fetchColumn();
        
        if ($taskCount > 0) {
            throw new Exception("Cannot delete category: it is assigned to {$taskCount} task(s)");
        }
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ? AND user_id = ?");
        $stmt->execute([$input['category_id'], $_SESSION['user_id']]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Category not found or access denied");
        }
        
        // Clear any unwanted output
        ob_clean();
        
        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
        exit;
    }
    
    // Method not allowed
    http_response_code(405);
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    
} catch (Exception $e) {
    // Clear any unwanted output
    ob_clean();
    
    // Set appropriate HTTP status code
    http_response_code(400);
    
    // Log the error for debugging
    error_log("Categories API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
?>