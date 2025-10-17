<?php
session_start();
require_once __DIR__ . '/../functions.php';
require_admin();

$db = get_db();
$user = current_user();
$message = '';
$edit_user = null;

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    if ($delete_id === $user['id']) {
        $message = 'You cannot delete your own account.';
    } else {
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$delete_id]);
        $message = 'User deleted successfully.';
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'commercial';
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email)) {
        $message = 'Please fill in all required fields.';
    } elseif (!validate_email($email)) {
        $message = 'Please enter a valid email address.';
    } else {
        if ($id) {
            // Update existing
            if (!empty($password)) {
                $stmt = $db->prepare('UPDATE users SET name = ?, email = ?, password = ?, role = ? WHERE id = ?');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $id]);
            } else {
                $stmt = $db->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
                $stmt->execute([$name, $email, $role, $id]);
            }
            $message = 'User updated successfully.';
        } else {
            // Insert new
            if (empty($password)) {
                $message = 'Password is required for new users.';
            } else {
                try {
                    $stmt = $db->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), $role]);
                    $message = 'User created successfully.';
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                        $message = 'Email address already exists.';
                    } else {
                        $message = 'Error creating user.';
                    }
                }
            }
        }
    }
}

// Get edit user if specified
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all users
$stmt = $db->prepare('SELECT * FROM users ORDER BY role DESC, name');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - ACOM Shop</title>
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
                <a href="<?php echo get_config('base_url'); ?>/admin/users.php">Users</a>
                <a href="<?php echo get_config('base_url'); ?>/admin/logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Users Management</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>

        <!-- Add/Edit User Form -->
        <div class="card">
            <h3><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h3>
            <form method="POST">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo h($edit_user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo h($edit_user['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <?php echo $edit_user ? '(leave blank to keep current)' : '*'; ?></label>
                        <input type="password" id="password" name="password" <?php echo !$edit_user ? 'required' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="commercial" <?php echo ($edit_user['role'] ?? '') === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                            <option value="admin" <?php echo ($edit_user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="save_user" class="btn btn-success">
                    <?php echo $edit_user ? 'Update User' : 'Add User'; ?>
                </button>
                <?php if ($edit_user): ?>
                    <a href="users.php" class="btn btn-secondary" style="margin-left: 0.5rem;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users List -->
        <div class="card">
            <h3>All Users (<?php echo count($users); ?>)</h3>
            
            <?php if (empty($users)): ?>
                <p>No users found.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong><?php echo h($u['name']); ?></strong></td>
                                    <td><?php echo h($u['email']); ?></td>
                                    <td>
                                        <span style="background-color: <?php echo $u['role'] === 'admin' ? '#cfe2ff' : '#d1ecf1'; ?>; padding: 0.25rem 0.75rem; border-radius: 4px;">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="?edit=<?php echo $u['id']; ?>" class="btn btn-secondary btn-small">Edit</a>
                                            <?php if ($u['id'] !== $user['id']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-small" onclick="return confirm('Delete this user?');">Delete</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #999; font-size: 0.9rem;">Current user</span>
                                            <?php endif; ?>
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
