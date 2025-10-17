<?php
session_start();
require_once __DIR__ . '/functions.php';

$db = get_db();
$stmt = $db->prepare('SELECT * FROM produits ORDER BY created_at DESC');
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACOM Shop - Products</title>
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
        <h2 style="margin-bottom: 1rem;">Our Products</h2>
        <p style="color: #666; margin-bottom: 2rem;">Select a product to calculate your custom pricing.</p>

        <?php if (empty($products)): ?>
            <div class="alert alert-info">No products available yet. Please check back later.</div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php if ($product['image']): ?>
                            <img src="<?php echo get_config('uploads_url'); ?>/<?php echo h($product['image']); ?>" alt="<?php echo h($product['name']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="display: flex; align-items: center; justify-content: center; background-color: #e9ecef; color: #999;">No Image</div>
                        <?php endif; ?>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo h($product['name']); ?></h3>
                            <p class="product-price">€<?php echo number_format($product['prix_m2'], 2); ?> / m²</p>
                            <p class="product-description"><?php echo h(substr($product['description'], 0, 100)); ?>...</p>
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn">View / Calculate</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 ACOM Shop. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
