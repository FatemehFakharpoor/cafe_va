<?php
// توابع کمکی

function formatPrice($price) {
    return number_format($price, 0, '', ',') . ' تومان';
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['is_admin'] == 1;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function uploadImage($file, $targetDir = 'products/') {
    $targetPath = UPLOAD_PATH . $targetDir;
    
    // ایجاد پوشه اگر وجود ندارد
    if (!file_exists($targetPath)) {
        mkdir($targetPath, 0777, true);
    }
    
    // نام فایل یکتا
    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $file['name']);
    $fullPath = $targetPath . $fileName;
    
    // آپلود
    if (move_uploaded_file($file['tmp_name'], $fullPath)) {
        return $targetDir . $fileName;
    }
    
    return null;
}

function getCategories() {
    return ['بارگرم', 'بارسرد', 'کیک', 'شربت', 'چای و دمنوش', 'شیک'];
}

function getCartCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}
?>