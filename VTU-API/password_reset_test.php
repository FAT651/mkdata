<?php
/**
 * Password Reset Debug Test Script
 * Run this to verify your SMTP and database setup
 * Access via: http://your-domain/password_reset_test.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Debug Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .pass { background-color: #d4edda; border-color: #c3e6cb; }
        .fail { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        h3 { margin-top: 0; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Password Reset Debug Test</h1>
    
    <?php
    // Test 1: Check PHP Version
    echo '<div class="test ' . (PHP_VERSION_ID >= 70200 ? 'pass' : 'fail') . '">';
    echo '<h3>✓ PHP Version</h3>';
    echo '<p>Current: ' . phpversion() . '</p>';
    echo '</div>';

    // Test 2: Check Required Extensions
    $extensions = ['pdo', 'pdo_mysql', 'json', 'openssl'];
    foreach ($extensions as $ext) {
        $loaded = extension_loaded($ext);
        echo '<div class="test ' . ($loaded ? 'pass' : 'fail') . '">';
        echo '<h3>' . ($loaded ? '✓' : '✗') . ' ' . strtoupper($ext) . ' Extension</h3>';
        echo '<p>' . ($loaded ? 'Loaded' : 'NOT LOADED - Required for password reset') . '</p>';
        echo '</div>';
    }

    // Test 3: Check PHP Error Log
    $php_ini = php_ini_loaded_file();
    echo '<div class="test info">';
    echo '<h3>ℹ PHP Configuration</h3>';
    echo '<p><strong>php.ini:</strong> ' . $php_ini . '</p>';
    
    $error_log = ini_get('error_log');
    echo '<p><strong>Error Log Location:</strong> ' . ($error_log ?: 'Not configured (using system default)') . '</p>';
    
    $display_errors = ini_get('display_errors') ? 'On' : 'Off';
    echo '<p><strong>Display Errors:</strong> ' . $display_errors . '</p>';
    echo '</div>';

    // Test 4: Check Database Connection
    require_once __DIR__ . '/config/database.php';
    
    try {
        $database = new \Binali\Config\Database();
        $db = $database->getConnection();
        
        if ($db) {
            echo '<div class="test pass">';
            echo '<h3>✓ Database Connection</h3>';
            echo '<p>Successfully connected to database</p>';
            
            // Check subscribers table
            try {
                $result = $db->query("SELECT COUNT(*) as count FROM subscribers LIMIT 1");
                $count = $result->fetch()['count'] ?? 0;
                echo '<p><strong>Subscribers Table:</strong> OK (' . $count . ' records)</p>';
            } catch (Exception $e) {
                echo '<p><strong>Subscribers Table:</strong> ERROR - ' . $e->getMessage() . '</p>';
            }
            
            // Check password_resets table
            try {
                $result = $db->query("SELECT COUNT(*) as count FROM password_resets LIMIT 1");
                $count = $result->fetch()['count'] ?? 0;
                echo '<p><strong>Password Resets Table:</strong> OK (' . $count . ' records)</p>';
            } catch (Exception $e) {
                echo '<p><strong>Password Resets Table:</strong> ERROR - ' . $e->getMessage() . '</p>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="test fail">';
            echo '<h3>✗ Database Connection</h3>';
            echo '<p>Failed to connect to database</p>';
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<div class="test fail">';
        echo '<h3>✗ Database Error</h3>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '</div>';
    }

    // Test 5: Check SMTP Configuration
    echo '<div class="test info">';
    echo '<h3>ℹ SMTP Configuration</h3>';
    echo '<pre>';
    echo "Host: mail.mkdata.com.ng\n";
    echo "Port: 587\n";
    echo "Security: STARTTLS\n";
    echo "Username: no-reply@mkdata.com.ng\n";
    echo "Password: [SET]\n";
    echo '</pre>';
    
    echo '<h4>To Test SMTP Connection:</h4>';
    echo '<pre>telnet mail.mkdata.com.ng 587</pre>';
    echo '<p>You should see: <code>220 ... ESMTP ...</code></p>';
    echo '</div>';

    // Test 6: Check PHPMailer
    $phpmailer_exists = class_exists('PHPMailer\PHPMailer\PHPMailer');
    echo '<div class="test ' . ($phpmailer_exists ? 'pass' : 'fail') . '">';
    echo '<h3>' . ($phpmailer_exists ? '✓' : '✗') . ' PHPMailer Library</h3>';
    echo '<p>' . ($phpmailer_exists ? 'PHPMailer is installed and available' : 'PHPMailer NOT FOUND - Run: composer install') . '</p>';
    echo '</div>';

    // Test 7: Check File Permissions
    $files_to_check = [
        __DIR__ . '/request_password_reset.php' => 'Request Password Reset Script',
        __DIR__ . '/../reset-password.php' => 'Reset Password Page',
        __DIR__ . '/../config/database.php' => 'Database Config'
    ];

    foreach ($files_to_check as $file => $name) {
        $exists = file_exists($file);
        $readable = $exists && is_readable($file);
        echo '<div class="test ' . ($readable ? 'pass' : 'fail') . '">';
        echo '<h3>' . ($readable ? '✓' : '✗') . ' ' . $name . '</h3>';
        echo '<p>' . ($readable ? 'Exists and readable' : ($exists ? 'Exists but not readable' : 'File NOT FOUND')) . '</p>';
        echo '<p><code>' . $file . '</code></p>';
        echo '</div>';
    }

    // Test 8: Recent Logs
    echo '<div class="test info">';
    echo '<h3>ℹ Troubleshooting Steps</h3>';
    echo '<ol>';
    echo '<li>Check the error log at: <code>' . ($error_log ?: '/var/log/php-fpm/error.log or similar') . '</code></li>';
    echo '<li>Look for lines containing: <code>PASSWORD RESET</code> or <code>SMTP</code></li>';
    echo '<li>Test SMTP manually with: <code>telnet mail.mkdata.com.ng 587</code></li>';
    echo '<li>Verify database tables exist and have correct data</li>';
    echo '<li>Check PHPMailer installation: <code>composer install</code></li>';
    echo '</ol>';
    echo '</div>';

    // Test 9: Sample cURL Test
    echo '<div class="test info">';
    echo '<h3>ℹ Test with cURL</h3>';
    echo '<pre>curl -X POST http://api.mkdata.com.ng/auth/request_password_reset.php \\';
    echo "\n  -H \"Content-Type: application/json\" \\";
    echo "\n  -d '{\"email\": \"test@example.com\"}'</pre>";
    echo '</div>';

    ?>

</body>
</html>
