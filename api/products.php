<?php
// ====== فعال‌سازی نمایش خطاها برای دیباگ ======
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// ====== بررسی دسترسی ویژه (mobin) یا ادمین معمولی ======
function isSpecialAdmin() {
    if (!isLoggedIn()) return false;
    
    $user = $_SESSION['user'];
    if ($user['name'] === 'mobin' && $user['email'] === 'mobinmk90mk16s@gmail.com') {
        return true;
    }
    if (isset($user['is_admin']) && $user['is_admin'] == 1) {
        return true;
    }
    return false;
}

if (!isSpecialAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
            $products = $stmt->fetchAll();
            
            foreach ($products as &$product) {
                $product['formatted_price'] = formatPrice($product['price']);
            }
            
            echo json_encode(['success' => true, 'products' => $products]);
            break;
            
        case 'get':
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            if ($product) {
                $product['formatted_price'] = formatPrice($product['price']);
                echo json_encode(['success' => true, 'product' => $product]);
            } else {
                echo json_encode(['success' => false, 'message' => 'محصول یافت نشد']);
            }
            break;
            
        case 'add':
            $name = sanitizeInput($_POST['name']);
            $price = (int)$_POST['price'];
            $category = sanitizeInput($_POST['category']);
            
            if (empty($name) || $price <= 0) {
                echo json_encode(['success' => false, 'message' => 'نام و قیمت الزامی است']);
                exit;
            }
            
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image = uploadImage($_FILES['image'], 'products/');
                if ($image === null) {
                    echo json_encode(['success' => false, 'message' => 'خطا در آپلود عکس']);
                    exit;
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO products (name, price, category, image) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$name, $price, $category, $image]);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'محصول با موفقیت اضافه شد',
                    'id' => $pdo->lastInsertId()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'خطا در افزودن محصول']);
            }
            break;
            
        case 'edit':
            $id = (int)$_POST['id'];
            $name = sanitizeInput($_POST['name']);
            $price = (int)$_POST['price'];
            $category = sanitizeInput($_POST['category']);
            
            if (empty($name) || $price <= 0) {
                echo json_encode(['success' => false, 'message' => 'نام و قیمت الزامی است']);
                exit;
            }
            
            $image = null;
            $hasNewImage = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;
            
            if ($hasNewImage) {
                $image = uploadImage($_FILES['image'], 'products/');
                if ($image === null) {
                    echo json_encode(['success' => false, 'message' => 'خطا در آپلود عکس']);
                    exit;
                }
            }
            
            if ($hasNewImage) {
                // حذف عکس قدیمی
                $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $oldProduct = $stmt->fetch();
                if ($oldProduct && $oldProduct['image'] && file_exists(UPLOAD_PATH . $oldProduct['image'])) {
                    @unlink(UPLOAD_PATH . $oldProduct['image']);
                }
                
                $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, category=?, image=? WHERE id=?");
                $result = $stmt->execute([$name, $price, $category, $image, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, category=? WHERE id=?");
                $result = $stmt->execute([$name, $price, $category, $id]);
            }
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'محصول با موفقیت ویرایش شد' : 'خطا در ویرایش محصول'
            ]);
            break;
            
        case 'delete':
            $id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            if ($product && $product['image'] && file_exists(UPLOAD_PATH . $product['image'])) {
                @unlink(UPLOAD_PATH . $product['image']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'محصول با موفقیت حذف شد' : 'خطا در حذف محصول'
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'عملکرد نامعتبر']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطای دیتابیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطای سرور: ' . $e->getMessage()]);
}
?>