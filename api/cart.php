<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// فقط کاربران لاگین شده
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'لطفاً وارد شوید']);
    exit;
}

$userId = $_SESSION['user']['id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = (int)$_POST['product_id'];
        
        // بررسی وجود محصول در سبد
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // افزایش تعداد
            $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
            $result = $stmt->execute([$existing['id']]);
        } else {
            // افزودن جدید
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $result = $stmt->execute([$userId, $productId]);
        }
        
        // بازگرداندن تعداد کل سبد
        $totalItems = getCartCount($pdo, $userId);
        echo json_encode(['success' => $result, 'totalItems' => $totalItems]);
        break;
        
    case 'get':
        $stmt = $pdo->prepare("
            SELECT c.*, p.name, p.price, p.image 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();
        
        $total = 0;
        foreach ($cartItems as &$item) {
            $item['subtotal'] = $item['price'] * $item['quantity'];
            $total += $item['subtotal'];
            $item['formatted_price'] = formatPrice($item['price']);
            $item['formatted_subtotal'] = formatPrice($item['subtotal']);
        }
        
        echo json_encode([
            'success' => true,
            'items' => $cartItems,
            'total' => $total,
            'formatted_total' => formatPrice($total),
            'totalItems' => array_sum(array_column($cartItems, 'quantity'))
        ]);
        break;
        
    case 'update':
        $productId = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($quantity <= 0) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $result = $stmt->execute([$userId, $productId]);
        } else {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $result = $stmt->execute([$quantity, $userId, $productId]);
        }
        
        echo json_encode(['success' => $result]);
        break;
        
    case 'remove':
        $productId = (int)$_GET['product_id'];
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $result = $stmt->execute([$userId, $productId]);
        echo json_encode(['success' => $result]);
        break;
        
    case 'clear':
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $result = $stmt->execute([$userId]);
        echo json_encode(['success' => $result]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'عملکرد نامعتبر']);
}
?>