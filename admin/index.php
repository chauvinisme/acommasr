<?php
session_start();
require_once __DIR__ . '/../functions.php';
require_login();

$db = get_db();
$user = current_user();

// Get statistics
$stmt = $db->prepare('SELECT COUNT(*) as total FROM produits');
$stmt->execute();
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare('SELECT COUNT(*) as total FROM commandes');
$stmt->execute();
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare('SELECT COUNT(*) as total FROM commandes WHERE statut = ?');
$stmt->execute(['Nouveau']);
$pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare('SELECT COUNT(*) as total FROM users WHERE role = ?');
$stmt->execute(['commercial']);
$total_commercials = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ACOM Shop</title>
    <link rel="stylesheet" href="<?php echo get_config('uploads_url'); ?>/../assets/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🛍️ ACOM Shop - Admin Panel</h1>
            <nav>
                <a href="<?php echo get_config('base_url'); ?>/">Shop</a>
                <a href="<?php echo get_config('base_url'); ?>/admin/">Dashboard</a>
                <a href="<?php echo get_config('base_url'); ?>/admin/products.php">Products</a>
                <a href="<?php echo get_config('base_url'); ?>/admin/orders.php">Orders</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?php echo get_config('base_url'); ?>/admin/users.php">Users</a>
                <?php endif; ?>
                <a href="<?php echo get_config('base_url'); ?>/admin/logout.php">Logout (<?php echo h($user['name']); ?>)</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Dashboard</h2>
        <p style="color: #666; margin-bottom: 2rem;">Welcome, <strong><?php echo h($user['name']); ?></strong> (<?php echo ucfirst($user['role']); ?>)</p>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_products; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" style="color: #ff9800;"><?php echo $pending_orders; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <?php if ($user['role'] === 'admin'): ?>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_commercials; ?></div>
                    <div class="stat-label">Commercial Users</div>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
            <div class="card">
                <h3>📦 Products</h3>
                <p>Manage your product catalog, prices, and images.</p>
                <a href="products.php" class="btn" style="display: inline-block; margin-top: 1rem;">Manage Products</a>
            </div>

            <div class="card">
                <h3>📋 Orders</h3>
                <p>View, track, and manage customer orders.</p>
                <a href="orders.php" class="btn" style="display: inline-block; margin-top: 1rem;">Manage Orders</a>
            </div>

            <?php if ($user['role'] === 'admin'): ?>
                <div class="card">
                    <h3>👥 Users</h3>
                    <p>Manage admin and commercial user accounts.</p>
                    <a href="users.php" class="btn" style="display: inline-block; margin-top: 1rem;">Manage Users</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 ACOM Shop. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>