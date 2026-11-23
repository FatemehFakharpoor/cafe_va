<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header('Location: /cafe_va/index.php');
    exit;
}

// آمار کلی
$stats = [];

// تعداد کل سفارشات
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $stmt->fetch()['total'];

// تعداد سفارشات امروز
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['today_orders'] = $stmt->fetch()['total'];

// کل فروش
$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// فروش امروز
$stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
$stats['today_revenue'] = $stmt->fetch()['total'] ?? 0;

// تعداد کل محصولات
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$stats['total_products'] = $stmt->fetch()['total'];

// تعداد کل کاربران
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch()['total'];

// وضعیت سفارشات
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$orderStatus = [];
while ($row = $stmt->fetch()) {
    $orderStatus[$row['status']] = $row['count'];
}

// ۵ سفارش اخیر
$stmt = $pdo->query("
    SELECT o.*, u.name as user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$recentOrders = $stmt->fetchAll();

// پرفروش‌ترین محصولات
$stmt = $pdo->query("
    SELECT p.id, p.name, 
           SUM(oi.quantity) as total_sold,
           SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 5
");
$topProducts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد | کافه وی‌ای</title>
    <!-- ====== لینک‌های CSS ====== -->
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-card);
        }
        .stat-card .stat-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--accent);
        }
        .stat-card .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        .stat-card .stat-change {
            font-size: 12px;
            margin-top: 8px;
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
        }
        .stat-card .stat-change.positive {
            background: #eafaf1;
            color: #27ae60;
        }
        .stat-card .stat-change.negative {
            background: #fdedec;
            color: #e74c3c;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .dashboard-panel {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border);
        }
        .dashboard-panel h3 {
            margin-bottom: 16px;
            color: var(--accent);
            font-size: 18px;
        }

        .recent-order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 8px;
        }
        .recent-order-item:last-child {
            border-bottom: none;
        }
        .recent-order-item .order-user {
            font-weight: 600;
        }
        .recent-order-item .order-total {
            font-weight: 700;
            color: var(--accent);
        }
        .recent-order-item .order-status {
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
        }
        .order-status.pending { background: #fef9e7; color: #f39c12; }
        .order-status.processing { background: #ebf5fb; color: #3498db; }
        .order-status.completed { background: #eafaf1; color: #27ae60; }
        .order-status.cancelled { background: #fdedec; color: #e74c3c; }

        .top-product-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }
        .top-product-item:last-child {
            border-bottom: none;
        }
        .top-product-item .product-name {
            font-weight: 500;
        }
        .top-product-item .product-sales {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .empty-state {
            text-align: center;
            padding: 30px 0;
            color: var(--text-secondary);
        }

        .view-all-link {
            display: inline-block;
            margin-top: 12px;
            color: var(--accent);
            text-decoration: none;
            font-size: 14px;
        }
        .view-all-link:hover {
            text-decoration: underline;
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
                <a href="dashboard.php" class="active">📈 داشبورد</a>
                <a href="orders.php">📦 سفارشات</a>
                <a href="products.php">☕ محصولات</a>
                <a href="users.php">👥 کاربران</a>
                <a href="../index.php" class="logout-link">🏠 بازگشت به سایت</a>
            </nav>
        </aside>

        <!-- محتوای اصلی -->
        <main class="admin-content">
            <div class="page-header">
                <h1>📈 داشبورد مدیریت</h1>
                <span class="date-display"><?php echo jdate(date('Y-m-d H:i:s')); ?></span>
            </div>

            <!-- آمار -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-number"><?php echo formatPrice($stats['total_revenue']); ?></div>
                    <div class="stat-label">کل فروش</div>
                    <span class="stat-change positive">↑ امروز: <?php echo formatPrice($stats['today_revenue']); ?></span>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">کل سفارشات</div>
                    <span class="stat-change positive">↑ امروز: <?php echo $stats['today_orders']; ?></span>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">☕</div>
                    <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                    <div class="stat-label">محصولات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">کاربران</div>
                </div>
            </div>

            <!-- گرید داشبورد -->
            <div class="dashboard-grid">
                <!-- سفارشات اخیر -->
                <div class="dashboard-panel">
                    <h3>🕐 آخرین سفارشات</h3>
                    <?php if (empty($recentOrders)): ?>
                        <div class="empty-state">هیچ سفارشی ثبت نشده است</div>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): 
                            $statusLabels = ['pending' => 'در انتظار', 'processing' => 'در حال پردازش', 'completed' => 'تکمیل شده', 'cancelled' => 'لغو شده'];
                        ?>
                        <div class="recent-order-item">
                            <span class="order-user">#<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['user_name']); ?></span>
                            <span class="order-total"><?php echo formatPrice($order['total_amount']); ?></span>
                            <span class="order-status <?php echo $order['status']; ?>">
                                <?php echo $statusLabels[$order['status']] ?? $order['status']; ?>
                            </span>
                            <span style="font-size:12px;color:var(--text-secondary);">
                                <?php echo jdate($order['created_at']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <a href="orders.php" class="view-all-link">مشاهده همه سفارشات →</a>
                    <?php endif; ?>
                </div>

                <!-- پرفروش‌ترین محصولات -->
                <div class="dashboard-panel">
                    <h3>🏆 پرفروش‌ترین محصولات</h3>
                    <?php if (empty($topProducts)): ?>
                        <div class="empty-state">هنوز فروشی ثبت نشده است</div>
                    <?php else: ?>
                        <?php foreach ($topProducts as $product): ?>
                        <div class="top-product-item">
                            <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                            <span class="product-sales">
                                <?php echo $product['total_sold']; ?> عدد
                                (<?php echo formatPrice($product['total_revenue']); ?>)
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // تاریخ شمسی
        function jdate(datetime) {
            const date = new Date(datetime);
            return date.toLocaleDateString('fa-IR') + ' ' + 
                   date.toLocaleTimeString('fa-IR', {hour: '2-digit', minute: '2-digit'});
        }
    </script>
</body>
</html>