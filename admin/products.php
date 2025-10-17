<?php
session_start();
require_once __DIR__ . '/../functions.php';
require_login();

$db = get_db();
$user = current_user();
$message = '';
$edit_product = null;

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    require_admin();
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $db->prepare('SELECT image FROM produits WHERE id = ?');
    $stmt->execute([$delete_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $product['image']) {
        @unlink(get_config('uploads_dir') . '/' . $product['image']);
    }
    
    $stmt = $db->prepare('DELETE FROM produits WHERE id = ?');
    $stmt->execute([$delete_id]);
    $message = 'Product deleted successfully.';
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    require_admin();
    
    $id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix_m2 = (float)($_POST['prix_m2'] ?? 0);
    
    if (empty($name) || $prix_m2 <= 0) {
        $message = 'Please fill in all required fields with valid values.';
    } else {
        $image = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image = upload_image($_FILES['image']);
            if (!$image) {
                $message = 'Invalid image file. Please upload JPG, PNG, or WebP.';
            }
        }
        
        if ($message === '') {
            if ($id) {
                // Update existing
                if ($image) {
                    $stmt = $db->prepare('UPDATE produits SET name = ?, description = ?, prix_m2 = ?, image = ? WHERE id = ?');
                    $stmt->execute([$name, $description, $prix_m2, $image, $id]);
                } else {
                    $stmt = $db->prepare('UPDATE produits SET name = ?, description = ?, prix_m2 = ? WHERE id = ?');
                    $stmt->execute([$name, $description, $prix_m2, $id]);
                }
                $message = 'Product updated successfully.';
            } else {
                // Insert new
                $stmt = $db->prepare('INSERT INTO produits (name, description, prix_m2, image) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $description, $prix_m2, $image ?: null]);
                $message = 'Product created successfully.';
            }
        }
    }
}

// Get edit product if specified
if (isset($_GET['edit'])) {
    require_admin();
    $stmt = $db->prepare('SELECT * FROM produits WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all products
$stmt = $db->prepare('SELECT * FROM produits ORDER BY created_at DESC');
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - ACOM Shop</title>
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
        <h2>Products Management</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Product Form -->
        <div class="card">
            <h3><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo h($edit_product['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prix_m2">Price per m² (€) *</label>
                        <input type="number" id="prix_m2" name="prix_m2" step="0.01" min="0.01" value="<?php echo ($edit_product['prix_m2'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo h($edit_product['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Product Image (JPG, PNG, WebP)</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                    <?php if ($edit_product && $edit_product['image']): ?>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                            Current: <img src="<?php echo get_config('uploads_url'); ?>/<?php echo h($edit_product['image']); ?>" alt="" style="max-width: 100px; margin-top: 0.5rem;">
                        </p>
                    <?php endif; ?>
                </div>

                <button type="submit" name="save_product" class="btn btn-success">
                    <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
                </button>
                <?php if ($edit_product): ?>
                    <a href="products.php" class="btn btn-secondary" style="margin-left: 0.5rem;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Products List -->
        <div class="card">
            <h3>All Products (<?php echo count($products); ?>)</h3>
            
            <?php if (empty($products)): ?>
                <p>No products yet. Create one above.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price/m²</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo get_config('uploads_url'); ?>/<?php echo h($product['image']); ?>" alt="<?php echo h($product['name']); ?>" style="max-width: 60px; border-radius: 4px;">
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.9rem;">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo h($product['name']); ?></strong></td>
                                    <td><?php echo h(substr($product['description'], 0, 50)); ?>...</td>
                                    <td>€<?php echo number_format($product['prix_m2'], 2); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-secondary btn-small">Edit</a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="delete_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Delete this product?');">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
