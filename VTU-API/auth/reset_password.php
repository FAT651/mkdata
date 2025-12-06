<?php
header("Content-Type: text/html; charset=UTF-8");

require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Africa/Lagos'); // Set timezone for Nigeria

use Binali\Config\Database;
use PDO;
use PDOException;

// CSRF protection
session_start();
if (!isset($_POST['token']) || empty($_POST['token'])) {
    die('Invalid request');
}

$token = $_POST['token'];
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate password
if (empty($password) || empty($confirm_password)) {
    die('All fields are required');
}

if ($password !== $confirm_password) {
    die('Passwords do not match');
}

// Password strength validation
if (strlen($password) < 8 || 
    !preg_match("/[A-Z]/", $password) || 
    !preg_match("/[a-z]/", $password) || 
    !preg_match("/[0-9]/", $password)) {
    die('Password does not meet requirements');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Get user email from valid reset token with proper timezone handling
    $query = "SELECT email, expires_at FROM password_resets 
              WHERE token = ? 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() == 0) {
        $db->rollBack();
        die('This reset link is invalid');
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $expires_at = strtotime($result['expires_at']);
    $email = $result['email'];
    
    if (time() > $expires_at) {
        $db->rollBack();
        die('This reset link has expired');
    }
    
    // Update user's password using website-compatible legacy hash
    $password_hash = substr(sha1(md5($password)), 3, 10);
    $update_query = "UPDATE subscribers 
                    SET sPass = ? 
                    WHERE sEmail = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$password_hash, $email]);
    
    if ($update_stmt->rowCount() == 0) {
        $db->rollBack();
        die('Failed to update password');
    }
    
    // Delete all reset tokens for this email
    $delete_query = "DELETE FROM password_resets WHERE email = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->execute([$email]);
    
    // Commit transaction
    $db->commit();
    
    // Show success page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset Successful - Binali One Data</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f5f5f5;
                color: #333;
            }
            .header {
                background-color: #36474F;
                padding: 1rem 0;
                text-align: center;
            }
            .logo {
                max-height: 40px;
            }
            .container {
                max-width: 400px;
                margin: 2rem auto;
                padding: 2rem;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                text-align: center;
            }
            h1 {
                color: #36474F;
                font-size: 24px;
                margin-bottom: 1rem;
            }
            .success-message {
                color: #28a745;
                margin-bottom: 2rem;
            }
            .button {
                display: inline-block;
                padding: 0.75rem 2rem;
                background-color: #36474F;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                font-weight: 600;
                transition: background-color 0.2s;
            }
            .button:hover {
                background-color: #2c3a40;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="/assets/images/logo.png" alt="Binali One Data" class="logo">
        </div>
        
        <div class="container">
            <h1>Password Reset Successful</h1>
            <p class="success-message">Your password has been successfully updated.</p>
            <a href="/login" class="button">Return to Login</a>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Password reset error: " . $e->getMessage());
    die('An error occurred. Please try again later.');
}
?>
