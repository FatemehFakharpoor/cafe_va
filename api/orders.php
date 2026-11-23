<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'لطفاً وارد شوید']);
    exit;
}

$userId = $_SESSION['user']['id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        // ثبت سفارش جدید
        try {
            $pdo->beginTransaction();
            
            // دریافت آیتم‌های سبد خرید
            $stmt = $pdo->prepare("
                SELECT c.*, p.price 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
            $cartItems = $stmt->fetchAll();
            
            if (empty($cartItems)) {
                echo json_encode(['success' => false, 'message' => 'سبد خرید خالی است']);
                exit;
            }
            
            // محاسبه کل مبلغ
            $totalAmount = array_sum(array_map(function($item) {
                return $item['price'] * $item['quantity'];
            }, $cartItems));
            
            // ثبت سفارش
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total_amount, status, payment_status) 
                VALUES (?, ?, 'pending', 'pending')
            ");
            $stmt->execute([$userId, $totalAmount]);
            $orderId = $pdo->lastInsertId();
            
            // ثبت جزئیات سفارش
            foreach ($cartItems as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            }
            
            // خالی کردن سبد خرید
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'total_amount' => $totalAmount,
                'formatted_total' => formatPrice($totalAmount),
                'message' => 'سفارش شما با موفقیت ثبت شد'
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'خطا در ثبت سفارش: ' . $e->getMessage()]);
        }
        break;
        
    case 'list':
        // لیست سفارشات کاربر
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();
        
        foreach ($orders as &$order) {
            $order['formatted_total'] = formatPrice($order['total_amount']);
            $order['formatted_date'] = jdate($order['created_at']);
        }
        
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;
        
    case 'details':
        // جزئیات یک سفارش
        $orderId = (int)$_GET['id'];
        
        // بررسی دسترسی
        if (!isAdmin()) {
            $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            if ($order && $order['user_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
                exit;
            }
        }
        
        // دریافت اطلاعات سفارش
        $stmt = $pdo->prepare("
            SELECT o.*, u.name as user_name, u.phone as user_phone 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'سفارش یافت نشد']);
            exit;
        }
        
        // دریافت جزئیات
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        
        $order['formatted_total'] = formatPrice($order['total_amount']);
        $order['formatted_date'] = jdate($order['created_at']);
        $order['items'] = $items;
        
        foreach ($order['items'] as &$item) {
            $item['formatted_price'] = formatPrice($item['price']);
            $item['subtotal'] = $item['price'] * $item['quantity'];
            $item['formatted_subtotal'] = formatPrice($item['subtotal']);
        }
        
        echo json_encode(['success' => true, 'order' => $order]);
        break;
        
    case 'update_status':
        // فقط ادمین
        if (!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
            exit;
        }
        
        $orderId = (int)$_POST['order_id'];
        $status = sanitizeInput($_POST['status']);
        
        $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'وضعیت نامعتبر']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $orderId]);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'وضعیت سفارش با موفقیت تغییر کرد' : 'خطا در تغییر وضعیت'
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'عملکرد نامعتبر']);
}

// ==========================================
// توابع کمکی
// ==========================================
function jdate($datetime) {
    $timestamp = strtotime($datetime);
    return date('Y/m/d H:i', $timestamp);
}
?>