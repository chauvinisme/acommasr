<?php
session_start();
require_once __DIR__ . '/functions.php';

if (!isset($_GET['ref'])) {
    header('Location: index.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare('SELECT * FROM commandes WHERE reference = ?');
$stmt->execute([$_GET['ref']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}

$items = json_decode($order['produits_json'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ACOM Shop</title>
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
        <div class="alert alert-success">
            <h2 style="margin-bottom: 0.5rem;">✓ Order Submitted Successfully!</h2>
            <p>Your order request has been received. We will contact you shortly.</p>
        </div>

        <div class="card">
            <h2>Order Reference: <span style="color: #0b66c3;"><?php echo h($order['reference']); ?></span></h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
                <div>
                    <h3>Customer Information</h3>
                    <p><strong>Name:</strong> <?php echo h($order['client_nom']); ?></p>
                    <p><strong>Email:</strong> <?php echo h($order['client_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo h($order['client_tel']); ?></p>
                </div>
                <div>
                    <h3>Order Details</h3>
                    <p><strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status:</strong> <span style="background-color: #e3f2fd; padding: 0.25rem 0.75rem; border-radius: 4px; color: #0b66c3;"><?php echo h($order['statut']); ?></span></p>
                    <p><strong>Total Amount:</strong> <span style="font-size: 1.3rem; color: #0b66c3; font-weight: 600;">€<?php echo number_format($order['total'], 2); ?></span></p>
                </div>
            </div>

            <h3>Order Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Dimensions</th>
                        <th>Surface (m²)</th>
                        <th>Unit Price (€/m²)</th>
                        <th>Total (€)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo h($item['name']); ?></td>
                            <td><?php echo number_format($item['width'], 2); ?>m × <?php echo number_format($item['height'], 2); ?>m</td>
                            <td><?php echo number_format($item['surface'], 2); ?></td>
                            <td>€<?php echo number_format($item['prix_m2'], 2); ?></td>
                            <td>€<?php echo number_format($item['total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="<?php echo get_config('base_url'); ?>/" class="btn">Continue Shopping</a>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 ACOM Shop. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
