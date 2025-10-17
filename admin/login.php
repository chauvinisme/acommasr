<?php
session_start();
require_once __DIR__ . '/../functions.php';

if (is_logged()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ACOM Shop</title>
    <link rel="stylesheet" href="<?php echo get_config('uploads_url'); ?>/../assets/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🛍️ ACOM Shop</h1>
            <nav>
                <a href="<?php echo get_config('base_url'); ?>/">Home</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="login-container">
            <div class="login-form">
                <h2>Admin Panel Login</h2>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo h($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn" style="width: 100%; text-align: center;">Login</button>
                </form>

                <p style="text-align: center; margin-top: 1.5rem; color: #666; font-size: 0.9rem;">
                    Default credentials:<br>
                    Email: <code style="background-color: #f0f0f0; padding: 0.25rem 0.5rem; border-radius: 3px;">admin@acom.local</code><br>
                    Password: <code style="background-color: #f0f0f0; padding: 0.25rem 0.5rem; border-radius: 3px;">Admin123!</code>
                </p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 ACOM Shop. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>