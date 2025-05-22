<?php
header("Content-Type: application/json");
require_once '../config.php';

try {
    jsonResponse(true, [
        'loggedIn' => isLoggedIn(),
        'user' => isLoggedIn() ? [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ] : null
    ]);
} catch (Exception $e) {
    jsonResponse(false, null, $e->getMessage());
}