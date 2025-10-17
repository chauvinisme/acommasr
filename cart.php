<?php
session_start();
require_once __DIR__ . '/functions.php';

$db = get_db();
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove_item' && isset($_POST['item_index'])) {
        $index = (int)$_POST['item_index'];
        if (isset($_SESSION['cart'][$index])) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }
        header('Location: cart.php');
        exit;
    }
    
    if ($_POST['action'] === 'clear_cart') {
        clear_cart();
        header('Location: cart.php');
        exit;
    }
    
    if ($_POST['action'] === 'submit_order') {
        $name = trim($_POST['client_nom'] ?? '');
        $email = trim($_POST['client_email'] ?? '');
        $phone = trim($_POST['client_tel'] ?? '');
        $cart = get_cart();
        
        if (empty($name) || empty($email) || empty($phone) || empty($cart)) {
            $message = 'Please fill in all fields and have items in your cart.';
        } elseif (!validate_email($email)) {
            $message = 'Please enter a valid email address.';
        } elseif (!validate_phone($phone)) {
            $message = 'Please enter a valid phone number.';
        } else {
            $reference = gen_reference();
            $total = get_cart_total();
            $products_json = json_encode($cart);
            
            $stmt = $db->prepare('
                INSERT INTO commandes (reference, client_nom, client_email, client_tel, produits_json, total)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            
            try {
                $stmt->execute([$reference, $name, $email, $phone, $products_json, $total]);
                $_SESSION['order_reference'] = $reference;
                $success = true;
                clear_cart();
            } catch (PDOException $e) {
                $message = 'Error submitting order. Please try again.';
            }
        }
    }
}

if ($success) {
    header('Location: confirmation.php?ref=' . $_SESSION['order_reference']);
    exit;
}

$cart = get_cart();
$total = get_cart_total();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ACOM Shop</title>
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
        <h2>Shopping Cart</h2>

        <?php if ($message): ?>
            <div class="alert alert-error"><?php echo h($message); ?></div>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
            <div class="alert alert-info">Your cart is empty. <a href="<?php echo get_config('base_url'); ?>/">Continue shopping</a></div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <div>
                    <div class="card">
                        <h3>Cart Items</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Dimensions</th>
                                    <th>Surface (m²)</th>
                                    <th>Unit Price (€/m²)</th>
                                    <th>Total (€)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $index => $item): ?>
                                    <tr>
                                        <td><?php echo h($item['name']); ?></td>
                                        <td><?php echo number_format($item['width'], 2); ?>m × <?php echo number_format($item['height'], 2); ?>m</td>
                                        <td><?php echo number_format($item['surface'], 2); ?></td>
                                        <td>€<?php echo number_format($item['prix_m2'], 2); ?></td>
                                        <td><strong>€<?php echo number_format($item['total'], 2); ?></strong></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="item_index" value="<?php echo $index; ?>">
                                                <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Remove this item?');">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div style="text-align: right; padding-top: 1rem; border-top: 2px solid #ddd;">
                            <h3 style="margin-bottom: 0;">Grand Total: <span style="color: #0b66c3;">€<?php echo number_format($total, 2); ?></span></h3>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <h3>Order Request</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="submit_order">
                            
                            <div class="form-group">
                                <label for="client_nom">Full Name *</label>
                                <input type="text" id="client_nom" name="client_nom" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="client_email">Email *</label>
                                <input type="email" id="client_email" name="client_email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="client_tel">Phone *</label>
                                <input type="tel" id="client_tel" name="client_tel" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success" style="width: 100%; text-align: center; font-weight: 600;">Submit Order Request</button>
                        </form>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="<?php echo get_config('base_url'); ?>/" class="btn btn-secondary">Continue Shopping</a>
                <form method="POST" style="display: inline; margin-left: 1rem;">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Clear entire cart?');">Clear Cart</button>
                </form>
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
