<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'update_role':
        $userId = (int)$_POST['user_id'];
        $isAdmin = (int)$_POST['is_admin'];
        
        // جلوگیری از تغییر نقش خودش
        if ($userId == $_SESSION['user']['id']) {
            echo json_encode(['success' => false, 'message' => 'نمی‌توانید نقش خودتان را تغییر دهید']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $result = $stmt->execute([$isAdmin, $userId]);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'نقش کاربر با موفقیت تغییر کرد' : 'خطا در تغییر نقش'
        ]);
        break;
        
    case 'delete':
        $userId = (int)$_GET['id'];
        
        // جلوگیری از حذف خودش
        if ($userId == $_SESSION['user']['id']) {
            echo json_encode(['success' => false, 'message' => 'نمی‌توانید حساب خودتان را حذف کنید']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'کاربر با موفقیت حذف شد' : 'خطا در حذف کاربر'
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'عملکرد نامعتبر']);
}
?>