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
    $conn = getDbConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle GET request - fetch tasks
        $sql = "SELECT 
                    t.task_id,
                    t.title,
                    t.description,
                    t.due_date,
                    t.completed,
                    t.created_at,
                    p.priority_id,
                    p.name as priority_name
                FROM tasks t
                LEFT JOIN priorities p ON t.priority_id = p.priority_id
                WHERE t.user_id = :user_id
                ORDER BY t.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get categories for each task
        foreach ($tasks as &$task) {
            $categoryStmt = $conn->prepare("
                SELECT c.category_id, c.name, c.color 
                FROM task_categories tc 
                JOIN categories c ON tc.category_id = c.category_id 
                WHERE tc.task_id = ?
            ");
            $categoryStmt->execute([$task['task_id']]);
            $task['categories'] = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ensure completed is boolean
            $task['completed'] = (bool)$task['completed'];
        }
        
        // Clear any unwanted output
        ob_clean();
        
        echo json_encode([
            'success' => true,
            'data' => $tasks
        ]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON input");
        }
        
        // Validate required fields
        if (empty($input['title'])) {
            throw new Exception("Title is required");
        }
        
        // Prepare SQL based on task_id
        if (empty($input['task_id'])) {
            // Insert new task
            $sql = "INSERT INTO tasks (title, description, due_date, priority_id, completed, user_id)
                    VALUES (:title, :description, :due_date, :priority_id, :completed, :user_id)";
        } else {
            // Update existing task
            $sql = "UPDATE tasks SET
                        title = :title,
                        description = :description,
                        due_date = :due_date,
                        priority_id = :priority_id,
                        completed = :completed
                    WHERE task_id = :task_id AND user_id = :user_id";
        }
        
        $stmt = $conn->prepare($sql);
        $params = [
            ':title' => $input['title'],
            ':description' => $input['description'] ?? null,
            ':due_date' => !empty($input['due_date']) ? $input['due_date'] : null,
            ':priority_id' => $input['priority_id'] ?? 2,
            ':completed' => isset($input['completed']) ? (int)$input['completed'] : 0,
            ':user_id' => $_SESSION['user_id']
        ];
        
        if (!empty($input['task_id'])) {
            $params[':task_id'] = $input['task_id'];
        }
        
        $stmt->execute($params);
        $task_id = $input['task_id'] ?? $conn->lastInsertId();
        
        // Handle categories
        if (!empty($input['categories'])) {
            // Clear existing categories
            $conn->prepare("DELETE FROM task_categories WHERE task_id = ?")
                 ->execute([$task_id]);
            
            // Insert new categories
            $stmt = $conn->prepare("INSERT INTO task_categories (task_id, category_id) VALUES (?, ?)");
            foreach ($input['categories'] as $category_id) {
                if (!empty($category_id)) {
                    $stmt->execute([$task_id, $category_id]);
                }
            }
        }
        
        // Clear any unwanted output
        ob_clean();
        
        // Return proper JSON response
        echo json_encode([
            'success' => true,
            'task_id' => $task_id,
            'message' => 'Task saved successfully'
        ]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Handle DELETE request
        parse_str(file_get_contents('php://input'), $input);
        
        if (empty($input['task_id'])) {
            throw new Exception("Task ID is required");
        }
        
        $stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ? AND user_id = ?");
        $stmt->execute([$input['task_id'], $_SESSION['user_id']]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Task not found or access denied");
        }
        
        // Clear any unwanted output
        ob_clean();
        
        echo json_encode([
            'success' => true,
            'message' => 'Task deleted successfully'
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
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}