<?php
require_once '../config.php';
requireLogin();
header('Content-Type: application/json');

try {
    $conn = getDbConnection();
    
    // Total tasks
    $sql = "SELECT COUNT(*) as total FROM tasks WHERE user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $total = $stmt->fetchColumn();
    
    // Completed tasks
    $sql = "SELECT COUNT(*) as completed FROM tasks WHERE user_id = :user_id AND completed = TRUE";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $completed = $stmt->fetchColumn();
    
    // Pending tasks (not completed and due date is in the future or not set)
    $sql = "SELECT COUNT(*) as pending FROM tasks 
            WHERE user_id = :user_id 
            AND completed = FALSE 
            AND (due_date IS NULL OR due_date >= CURDATE())";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $pending = $stmt->fetchColumn();
    
    // Overdue tasks (not completed and due date is in the past)
    $sql = "SELECT COUNT(*) as overdue FROM tasks 
            WHERE user_id = :user_id 
            AND completed = FALSE 
            AND due_date < CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $overdue = $stmt->fetchColumn();
    
    // Return data in the format JavaScript expects
    echo json_encode([
        'success' => true,
        'data' => [
            'total' => (int)$total,
            'completed' => (int)$completed,
            'pending' => (int)$pending,
            'overdue' => (int)$overdue
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}