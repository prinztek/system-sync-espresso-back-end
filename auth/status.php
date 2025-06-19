<?php

include '../cors.php';
include '../config/db.php';


session_start();

// Check if the session variables for user ID and username are set
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    // If session is valid, return user data as a JSON response
    echo json_encode([
        'status' => 'success',
        'loggedIn' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'created_at' => $_SESSION['created_at'],
            'role' => $_SESSION['role'] ?? 'user',
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No active session found',
    ]);
}
