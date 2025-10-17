<?php
/**
 * Global helper functions for ACOM Shop
 */

// Database connection
function get_db() {
    static $db = null;
    
    if ($db === null) {
        $config = require __DIR__ . '/config.php';
        
        try {
            $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
            $db = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
    }
    
    return $db;
}

// Session management
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . get_config('base_url') . '/admin/login.php');
        exit;
    }
}

function is_logged() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged()) return null;
    
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function require_admin() {
    require_login();
    $user = current_user();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        die('Access denied. Admin role required.');
    }
}

// HTML escaping
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Configuration
function get_config($key = null) {
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $key ? $config[$key] : $config;
}

// Generate unique order reference
function gen_reference() {
    return 'DEV-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

// Cart management (session-based)
function get_cart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function add_to_cart($product_id, $width, $height) {
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM produits WHERE id = ?');
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        return false;
    }
    
    $surface = $width * $height;
    $price = $surface * $product['prix_m2'];
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $_SESSION['cart'][] = [
        'product_id' => $product_id,
        'name' => $product['name'],
        'width' => $width,
        'height' => $height,
        'surface' => $surface,
        'prix_m2' => $product['prix_m2'],
        'total' => $price
    ];
    
    return true;
}

function clear_cart() {
    $_SESSION['cart'] = [];
}

function get_cart_total() {
    $cart = get_cart();
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['total'];
    }
    return $total;
}

// File upload handling
function upload_image($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return null;
    }
    
    $upload_dir = get_config('uploads_dir');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $upload_path = $upload_dir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $filename;
    }
    
    return null;
}

// Validation
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_phone($phone) {
    return strlen(preg_replace('/[^0-9+\-\s]/', '', $phone)) >= 9;
}
?>