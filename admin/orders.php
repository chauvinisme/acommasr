<?php
session_start();
require_once __DIR__ . '/../functions.php';
require_login();

$db = get_db();
$user = current_user();
$message = '';
$view_order = null;

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $statut = $_POST['statut'] ?? '';
    
    $stmt = $db->prepare('UPDATE commandes SET statut = ? WHERE id = ?');
    $stmt->execute([$statut, $order_id]);
    $message = 'Order status updated.';
}

// Handle assign commercial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_commercial'])) {
    require_admin();
    $order_id = (int)$_POST['order_id'];
    $commercial_id = (int)$_POST['commercial_id'];
    
    $stmt = $db->prepare('UPDATE commandes SET assigne_a = ? WHERE id = ?');
    $stmt->execute([$commercial_id ?: null, $order_id]);
    $message = 'Order assigned successfully.';
}

// View order details
if (isset($_GET['view'])) {
    $stmt = $db->prepare('SELECT * FROM commandes WHERE id = ?');
    $stmt->execute([(int)$_GET['view']]);
    $view_order = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all orders (filter for commercial users)
if ($user['role'] === 'admin') {
    $stmt = $db->prepare('SELECT * FROM commandes ORDER BY created_at DESC');
    $stmt->execute();
} else {
    $stmt = $db->prepare('SELECT * FROM commandes WHERE assigne_a = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get commercials for dropdown
$stmt = $db->prepare('SELECT id, name FROM users WHERE role = ? ORDER BY name');
$stmt->execute(['commercial']);
$commercials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - ACOM Shop</title>
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
                <a href="<?php echo get_config('base_url'); ?>/admin/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Orders Management</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>

        <?php if ($view_order): ?>
            <!-- Order Details View -->
            <div class="card">
                <a href="orders.php" style="display: inline-block; margin-bottom: 1rem;">&larr; Back to Orders</a>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div>
                        <h3>Order Reference: <?php echo h($view_order['reference']); ?></h3>
                        <p><strong>Customer Name:</strong> <?php echo h($view_order['client_nom']); ?></p>
                        <p><strong>Email:</strong> <?php echo h($view_order['client_email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo h($view_order['client_tel']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($view_order['created_at'])); ?></p>
                    </div>
                    <div>
                        <h3>Order Status</h3>
                        <form method="POST" style="background: none; padding: 0;">
                            <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="statut">
                                    <option value="Nouveau" <?php echo $view_order['statut'] === 'Nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                                    <option value="En cours" <?php echo $view_order['statut'] === 'En cours' ? 'selected' : ''; ?>>En cours</option>
                                    <option value="Traité" <?php echo $view_order['statut'] === 'Traité' ? 'selected' : ''; ?>>Traité</option>
                                    <option value="Annulé" <?php echo $view_order['statut'] === 'Annulé' ? 'selected' : ''; ?>>Annulé</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
                        </form>

                        <?php if ($user['role'] === 'admin'): ?>
                            <form method="POST" style="background: none; padding: 0; margin-top: 1rem;">
                                <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                                <div class="form-group">
                                    <label>Assign to Commercial</label>
                                    <select name="commercial_id">
                                        <option value="">Select...</option>
                                        <?php foreach ($commercials as $commercial): ?>
                                            <option value="<?php echo $commercial['id']; ?>" <?php echo $view_order['assigne_a'] == $commercial['id'] ? 'selected' : ''; ?>>
                                                <?php echo h($commercial['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="assign_commercial" class="btn btn-secondary">Assign</button>
                            </form>
                        <?php endif; ?>
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
                        <?php 
                        $items = json_decode($view_order['produits_json'], true);
                        foreach ($items as $item): 
                        ?>
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
                <div style="text-align: right; padding-top: 1rem; border-top: 2px solid #ddd;">
                    <h3>Grand Total: <span style="color: #0b66c3;">€<?php echo number_format($view_order['total'], 2); ?></span></h3>
                </div>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="card">
                <h3>All Orders (<?php echo count($orders); ?>)</h3>
                
                <?php if (empty($orders)): ?>
                    <p>No orders found.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Customer</th>
                                    <th>Total (€)</th>
                                    <th>Status</th>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <th>Assigned To</th>
                                    <?php endif; ?>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo h($order['reference']); ?></strong></td>
                                        <td><?php echo h($order['client_nom']); ?></td>
                                        <td>€<?php echo number_format($order['total'], 2); ?></td>
                                        <td>
                                            <span style="background-color: 
                                                <?php 
                                                    if ($order['statut'] === 'Nouveau') echo '#fff3cd';
                                                    elseif ($order['statut'] === 'En cours') echo '#d1ecf1';
                                                    elseif ($order['statut'] === 'Traité') echo '#d4edda';
                                                    else echo '#f8d7da';
                                                ?>; 
                                                padding: 0.25rem 0.75rem; border-radius: 4px;">
                                                <?php echo h($order['statut']); ?>
                                            </span>
                                        </td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <td>
                                                <?php 
                                                if ($order['assigne_a']) {
                                                    $stmt = $db->prepare('SELECT name FROM users WHERE id = ?');
                                                    $stmt->execute([$order['assigne_a']]);
                                                    $commercial = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    echo h($commercial['name']);
                                                } else {
                                                    echo '<span style="color: #999;">Unassigned</span>';
                                                }
                                                ?>
                                            </td>
                                        <?php endif; ?>
                                        <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="?view=<?php echo $order['id']; ?>" class="btn btn-secondary btn-small">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
