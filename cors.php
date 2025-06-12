<?php
// Set CORS headers for all requests

$allowedOrigin = 'http://localhost:5173';  // Change this to your frontend URL

// Set CORS headers
header("Access-Control-Allow-Origin: $allowedOrigin");

// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// Allow specific headers (e.g., Content-Type, Authorization)
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Allow credentials (cookies, session data)
header('Access-Control-Allow-Credentials: true');

// If the request is an OPTIONS request (preflight), respond with a 200 OK
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit(); // End the preflight request
}
