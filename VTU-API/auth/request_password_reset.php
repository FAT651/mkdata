<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Lagos'); // Set timezone for Nigeria
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PHPMailer

use Binali\Config\Database;
use PDO;
use PDOException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Rate limiting using sessions
session_start();
$current_time = time();
$timeout = 300; // 5 minutes timeout
$max_attempts = 3;

if (isset($_SESSION['reset_attempts'])) {
    // Clear old attempts
    foreach ($_SESSION['reset_attempts'] as $time => $count) {
        if ($current_time - $time > $timeout) {
            unset($_SESSION['reset_attempts'][$time]);
        }
    }
}

// Count recent attempts
$recent_attempts = 0;
if (isset($_SESSION['reset_attempts'])) {
    foreach ($_SESSION['reset_attempts'] as $count) {
        $recent_attempts += $count;
    }
}

if ($recent_attempts >= $max_attempts) {
    http_response_code(429);
    echo json_encode([
        "status" => "error",
        "message" => "Too many reset attempts. Please try again later."
    ]);
    exit();
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Email is required"
    ]);
    exit();
}

$email = filter_var($data->email, FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email format"
    ]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Check if email exists in subscribers table
    $check_query = "SELECT sId, sEmail FROM subscribers WHERE sEmail = ? LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$email]);

    if ($check_stmt->rowCount() == 0) {
        // Don't reveal that the email doesn't exist
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "If your email is registered, you will receive reset instructions shortly."
        ]);
        exit();
    }

    // Generate secure random token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));

    // Delete any existing reset tokens for this email
    $delete_query = "DELETE FROM password_resets WHERE email = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->execute([$email]);

    // Insert new reset token
    $insert_query = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->execute([$email, $token, $expires_at]);

    // Record this attempt
    $_SESSION['reset_attempts'][$current_time] = 1;

    // Send email using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'mail.mkdata.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@mkdata.com';
        $mail->Password = ']xG28YL,APm-+xbx';

        // Use STARTTLS for port 587
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('no-reply@mkdata.com', 'Binali One Data');
        $mail->addAddress($email);



        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';

        $reset_link = "http://api.mkdata.com.ng/reset-password.php?token=" . $token;

        $mail->Body = "
            <html>
            <body>
                <h2>Password Reset Request</h2>
                <p>We received a request to reset your password. Click the button below to set a new password:</p>
                <p>
                    <a href='{$reset_link}' 
                       style='background-color: #36474F; 
                              color: white; 
                              padding: 10px 20px; 
                              text-decoration: none; 
                              border-radius: 5px;
                              display: inline-block;'>
                        Reset Password
                    </a>
                </p>
                <p>This link will expire in 24 hours.</p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>For security reasons, please don't share this link with anyone.</p>
            </body>
            </html>
        ";

        $mail->AltBody = "Click this link to reset your password: {$reset_link}";

        $mail->send();

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "If your email is registered, you will receive reset instructions shortly."
        ]);
    } catch (Exception $e) {
        error_log("Mail Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Unable to send reset instructions. Please try again later."
        ]);
    }
} catch (Exception $e) {
    error_log("Reset Request Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "An error occurred. Please try again later."
    ]);
}
