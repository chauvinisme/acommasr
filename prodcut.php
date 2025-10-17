<?php
session_start();
require_once __DIR__ . '/functions.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare('SELECT * FROM produits WHERE id = ?');
$stmt->execute([$_GET['id']]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $width = (float)($_POST['width'] ?? 0);
    $height = (float)($_POST['height'] ?? 0);
    
    if ($width > 0 && $height > 0) {
        add_to_cart($product['id'], $width, $height);
        $message = 'Product added to cart!';
    } else {
        $message = 'Invalid dimensions. Please enter positive numbers.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($product['name']); ?> - ACOM Shop</title>
    <link rel="stylesheet" href="<?php echo get_config('uploads_url'); ?>/../assets/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🛍️ ACOM Shop</h1>
            <nav>
                <a href="<?php echo get_config('base_url'); ?>/">Home</a>
                <a href="<?php echo get_config('base_url'); ?>/cart.php">Shopping Cart</a>
                <?php if (is_logged()): ?>
                    <a href="<?php echo get_config('base_url'); ?>/admin/">Admin Panel</a>
                    <a href="<?php echo get_config('base_url'); ?>/admin/logout.php">Logout</a>
                <?php else: ?>
                    <a href="<?php echo get_config('base_url'); ?>/admin/login.php">Admin Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <a href="<?php echo get_config('base_url'); ?>/" style="margin-bottom: 1rem; display: inline-block;">&larr; Back to Products</a>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
                <div>
                    <?php if ($product['image']): ?>
                        <img src="<?php echo get_config('uploads_url'); ?>/<?php echo h($product['image']); ?>" alt="<?php echo h($product['name']); ?>" style="width: 100%; border-radius: 8px;">
                    <?php else: ?>
                        <div style="width: 100%; height: 300px; background-color: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">No Image Available</div>
                    <?php endif; ?>
                </div>

                <div>
                    <h1><?php echo h($product['name']); ?></h1>
                    <p style="font-size: 1.3rem; color: #0b66c3; font-weight: 600; margin: 1rem 0;">€<?php echo number_format($product['prix_m2'], 2); ?> / m²</p>
                    
                    <h3>Description</h3>
                    <p><?php echo nl2br(h($product['description'])); ?></p>

                    <div class="calculator">
                        <h3>📐 Price Calculator</h3>
                        <form method="POST" style="background: none; padding: 0; margin: 0;">
                            <div class="calculator-inputs">
                                <div class="form-group" style="margin: 0;">
                                    <label for="width">Width (m)</label>
                                    <input type="number" id="width" name="width" step="0.01" min="0" required>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="height">Height (m)</label>
                                    <input type="number" id="height" name="height" step="0.01" min="0" required>
                                </div>
                            </div>
                            <button type="submit" class="btn" style="width: 100%; text-align: center;">Calculate & Add to Cart</button>
                        </form>
                    </div>
                </div>
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