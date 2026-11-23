<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header('Location: /cafe_va/index.php');
    exit;
}

// دریافت لیست محصولات
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();

// دسته‌بندی‌ها
$categories = ['بارگرم', 'بارسرد', 'کیک', 'شربت', 'چای و دمنوش', 'شیک'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت محصولات | کافه وی‌ای</title>
    <!-- ====== لینک‌های CSS ====== -->
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .product-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .product-stats .stat-card {
            background: var(--bg-secondary);
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--border);
        }
        .product-stats .stat-card .number {
            font-size: 24px;
            font-weight: 700;
            display: block;
            color: var(--accent);
        }
        .product-stats .stat-card .label {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .add-product-btn {
            margin-bottom: 20px;
            padding: 12px 24px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .add-product-btn:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
        }

        .product-row {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            transition: 0.2s;
        }
        .product-row:hover {
            box-shadow: var(--shadow-card);
        }
        .product-info {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }
        .product-info .product-thumb {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--bg-primary);
        }
        .product-info .product-thumb-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        .product-info .product-name {
            font-weight: 700;
            font-size: 16px;
        }
        .product-info .product-category {
            font-size: 14px;
            color: var(--text-secondary);
            padding: 2px 12px;
            background: var(--bg-primary);
            border-radius: 12px;
        }
        .product-info .product-price {
            font-weight: 700;
            color: var(--accent);
        }
        .product-actions {
            display: flex;
            gap: 8px;
        }
        .btn-edit {
            padding: 6px 16px;
            border-radius: 8px;
            border: none;
            background: var(--accent);
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-edit:hover {
            background: var(--accent-dark);
        }
        .btn-delete {
            padding: 6px 16px;
            border-radius: 8px;
            border: none;
            background: #e74c3c;
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-delete:hover {
            background: #c0392b;
        }

        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: var(--text-secondary);
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: 0.3s;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal {
            background: var(--bg-secondary);
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 24px;
            position: relative;
            border: 1px solid var(--border);
        }
        .modal-close {
            position: absolute;
            top: 12px;
            left: 16px;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        .modal-close:hover {
            color: var(--danger);
        }
        .modal-title {
            font-weight: 700;
            font-size: 20px;
            text-align: center;
            margin-bottom: 20px;
            color: var(--accent);
        }
        .modal-form-group {
            margin-bottom: 16px;
        }
        .modal-form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            font-size: 14px;
            color: var(--text-secondary);
        }
        .modal-form-group input,
        .modal-form-group select {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 14px;
        }
        .modal-form-group input[type="file"] {
            padding: 8px;
        }
        .modal-form-group .image-preview {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 8px;
            display: none;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-primary:hover {
            background: var(--accent-dark);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- سایدبار -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2>📊 پنل مدیریت</h2>
                <div class="admin-user"><?php echo $_SESSION['user']['name']; ?></div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php">📈 داشبورد</a>
                <a href="orders.php">📦 سفارشات</a>
                <a href="products.php" class="active">☕ محصولات</a>
                <a href="users.php">👥 کاربران</a>
                <a href="../index.php" class="logout-link">🏠 بازگشت به سایت</a>
            </nav>
        </aside>

        <!-- محتوای اصلی -->
        <main class="admin-content">
            <div class="page-header">
                <h1>☕ مدیریت محصولات</h1>
            </div>

            <!-- آمار -->
            <?php
            $totalProducts = count($products);
            $categoryCounts = [];
            foreach ($products as $p) {
                $categoryCounts[$p['category']] = ($categoryCounts[$p['category']] ?? 0) + 1;
            }
            ?>
            <div class="product-stats">
                <div class="stat-card">
                    <span class="number"><?php echo $totalProducts; ?></span>
                    <span class="label">کل محصولات</span>
                </div>
                <?php foreach ($categories as $cat): ?>
                    <div class="stat-card">
                        <span class="number"><?php echo $categoryCounts[$cat] ?? 0; ?></span>
                        <span class="label"><?php echo $cat; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- دکمه افزودن -->
            <button class="add-product-btn" onclick="openAddModal()">➕ افزودن محصول جدید</button>

            <!-- لیست محصولات -->
            <?php if (empty($products)): ?>
                <div class="empty-state">هیچ محصولی یافت نشد</div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                <div class="product-row" id="product-<?php echo $product['id']; ?>">
                    <div class="product-info">
                        <?php if ($product['image']): ?>
                            <img src="../assets/uploads/<?php echo $product['image']; ?>" 
                                 class="product-thumb" alt="<?php echo $product['name']; ?>">
                        <?php else: ?>
                            <div class="product-thumb-placeholder">☕</div>
                        <?php endif; ?>
                        <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                        <span class="product-category"><?php echo $product['category']; ?></span>
                        <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                    </div>
                    <div class="product-actions">
                        <button class="btn-edit" onclick="openEditModal(<?php echo $product['id']; ?>)">✏️ ویرایش</button>
                        <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">🗑️ حذف</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- مودال افزودن/ویرایش محصول -->
    <div class="modal-overlay" id="productModal">
        <div class="modal">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <div class="modal-title" id="modalTitle">افزودن محصول جدید</div>
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="productId" name="id">
                <div class="modal-form-group">
                    <label>نام محصول *</label>
                    <input type="text" id="productName" name="name" required>
                </div>
                <div class="modal-form-group">
                    <label>قیمت (تومان) *</label>
                    <input type="number" id="productPrice" name="price" required>
                </div>
                <div class="modal-form-group">
                    <label>دسته‌بندی</label>
                    <select id="productCategory" name="category">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-form-group">
                    <label>عکس محصول</label>
                    <input type="file" id="productImage" name="image" accept="image/*">
                    <img id="imagePreview" class="image-preview" alt="پیش‌نمایش">
                </div>
                <button type="submit" class="btn-primary">💾 ذخیره</button>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = '➕ افزودن محصول جدید';
        document.getElementById('productId').value = '';
        document.getElementById('productName').value = '';
        document.getElementById('productPrice').value = '';
        document.getElementById('productCategory').value = 'بارگرم';
        document.getElementById('productImage').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('productModal').classList.add('active');
    }

    async function openEditModal(productId) {
        try {
            const response = await fetch(`../api/products.php?action=get&id=${productId}`);
            const result = await response.json();

            if (result.success) {
                const product = result.product;
                document.getElementById('modalTitle').textContent = '✏️ ویرایش محصول';
                document.getElementById('productId').value = product.id;
                document.getElementById('productName').value = product.name;
                document.getElementById('productPrice').value = product.price;
                document.getElementById('productCategory').value = product.category;
                document.getElementById('productImage').value = '';
                
                if (product.image) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = `../assets/uploads/${product.image}`;
                    preview.style.display = 'block';
                } else {
                    document.getElementById('imagePreview').style.display = 'none';
                }
                
                document.getElementById('productModal').classList.add('active');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('خطا در دریافت اطلاعات محصول');
        }
    }

    function closeModal() {
        document.getElementById('productModal').classList.remove('active');
    }

    document.getElementById('productImage').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const preview = document.getElementById('imagePreview');
                preview.src = ev.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    document.getElementById('productForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const id = document.getElementById('productId').value;
        const formData = new FormData(this);
        
        try {
            const action = id ? 'edit' : 'add';
            const response = await fetch(`../api/products.php?action=${action}`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                alert('✅ ' + result.message);
                closeModal();
                location.reload();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('خطا در ارتباط با سرور');
        }
    });

    async function deleteProduct(productId) {
        if (!confirm('⚠️ آیا از حذف این محصول مطمئنید؟')) return;

        try {
            const response = await fetch(`../api/products.php?action=delete&id=${productId}`);
            const result = await response.json();

            if (result.success) {
                alert('✅ ' + result.message);
                const row = document.getElementById(`product-${productId}`);
                if (row) row.remove();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('خطا در ارتباط با سرور');
        }
    }

    document.getElementById('productModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    </script>
</body>
</html>