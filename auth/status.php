<?php

// Include CORS headers to allow cross-origin requests
include '../cors.php';
// Include database configuration and connection
include '../config/db.php';


session_start();

// Check if the session variables for user ID and username are set
if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    // If session is valid, return user data as a JSON response
    echo json_encode([
        'status' => 'success',
        'loggedIn' => true,
        'user' => [
            'id' => $_SESSION['user_id'],  // Session user ID
            'username' => $_SESSION['username'],  // Session username
            'email' => $_SESSION['email'],  // Session email (assuming it's stored)
            'created_at' => $_SESSION['created_at'],  // Session creation date (if stored)
            'role' => $_SESSION['role'] ?? 'user', // ✅ Add this line
        ]
    ]);
} else {
    // If session is not valid or expired, return error status
    echo json_encode([
        'status' => 'error',
        'message' => 'No active session found',
    ]);
}
