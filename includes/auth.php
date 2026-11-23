<?php
// توابع احراز هویت

function loginUser($pdo, $phone, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'phone' => $user['phone'],
            'is_admin' => $user['is_admin']
        ];
        return true;
    }
    
    return false;
}

function registerUser($pdo, $name, $phone, $password) {
    // بررسی تکراری بودن شماره
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'این شماره قبلاً ثبت شده است'];
    }
    
    // هش کردن رمز
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (name, phone, password) VALUES (?, ?, ?)");
    $result = $stmt->execute([$name, $phone, $hashedPassword]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        $_SESSION['user'] = [
            'id' => $userId,
            'name' => $name,
            'phone' => $phone,
            'is_admin' => 0
        ];
        return ['success' => true];
    }
    
    return ['success' => false, 'message' => 'خطا در ثبت‌نام'];
}

function logoutUser() {
    $_SESSION = [];
    session_destroy();
}
?>