<?php
session_start(); // Start session to access it

include '../cors.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Unset all session variables
    session_unset();

    // Destroy the session
    session_destroy();

    // ✅ Expire the PHPSESSID cookie on the client
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Logged out successfully'
    ]);
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed'
    ]);
}
