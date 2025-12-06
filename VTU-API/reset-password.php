<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Binali One Data</title>
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #36474F;
            font-size: 24px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #36474F;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }

        input:focus {
            border-color: #36474F;
            outline: none;
        }

        .error {
            color: #dc3545;
            font-size: 14px;
            margin-top: 0.5rem;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #36474F;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #2c3a40;
        }

        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="/assets/images/logo.png" alt="Binali One Data" class="logo">
    </div>

    <div class="container">
        <h1>Reset Your Password</h1>

        <?php
        require_once __DIR__ . '/config/database.php';

        use Binali\Config\Database;
        use PDO;
        use PDOException;

        $token = $_GET['token'] ?? '';
        $error = '';
        $success = '';

        if (empty($token)) {
            $error = 'Invalid reset link';
        } else {
            try {
                $database = new Database();
                $db = $database->getConnection();

                if (!$db) {
                    throw new Exception("Database connection failed");
                }

                $query = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute([$token]);

                if ($stmt->rowCount() == 0) {
                    $error = 'This reset link has expired or is invalid';
                }
            } catch (Exception $e) {
                error_log("Reset page error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }

        if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <form id="resetForm" action="auth/reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required
                        minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                    <div class="password-requirements">
                        Password must be at least 8 characters long and include:
                        <ul>
                            <li>One uppercase letter</li>
                            <li>One lowercase letter</li>
                            <li>One number</li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div id="password-match-error" class="error" style="display: none;">
                        Passwords do not match
                    </div>
                </div>

                <button type="submit">Reset Password</button>
            </form>

            <script>
                document.getElementById('resetForm').onsubmit = function(e) {
                    var password = document.getElementById('password').value;
                    var confirm = document.getElementById('confirm_password').value;
                    var error = document.getElementById('password-match-error');

                    if (password !== confirm) {
                        error.style.display = 'block';
                        e.preventDefault();
                        return false;
                    }

                    error.style.display = 'none';
                    return true;
                };
            </script>
        <?php endif; ?>
    </div>
</body>

</html>