<?php
// User Authentication Handler
// File: includes/fetch_user.php

// For testing purposes, create a default user
// Replace this with actual authentication logic when deploying

if (!isset($_SESSION['user_id'])) {
    // Default test user
    $user = [
        'user_id' => 1,
        'username' => 'admin',
        'email' => 'admin@example.com',
        'profile_pic' => 'https://cdn-icons-png.flaticon.com/512/847/847969.png',
        'full_name' => 'Administrator'
    ];
} else {
    // Fetch real user from database if logged in
    $stmt = $conn->prepare("SELECT user_id, username, email, profile_pic, full_name FROM users WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        } else {
            // User not found, use default
            $user = [
                'user_id' => 0,
                'username' => 'guest',
                'profile_pic' => 'https://cdn-icons-png.flaticon.com/512/847/847969.png'
            ];
        }
        $stmt->close();
    }
}
?>
