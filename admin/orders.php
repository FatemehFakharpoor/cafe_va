<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header('Location: /cafe_va/index.php');
    exit;
}

// دریافت لیست سفارشات
$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT o.*, u.name as user_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id";

if ($statusFilter !== 'all') {
    $sql .= " WHERE o.status = ?";
}
$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($statusFilter !== 'all') {
    $stmt->execute([$statusFilter]);
} else {
    $stmt->execute();
}
$orders = $stmt->fetchAll();

// آمار سفارشات
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM orders
");
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت سفارشات | کافه وی‌ای</title>
    <!-- ====== لینک‌های CSS ====== -->
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .orders-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-secondary);
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--border);
        }
        .stat-card .number {
            font-size: 24px;
            font-weight: 700;
            display: block;
        }
        .stat-card .label {
            font-size: 14px;
            color: var(--text-secondary);
        }
        .stat-card.pending .number { color: #f39c12; }
        .stat-card.processing .number { color: #3498db; }
        .stat-card.completed .number { color: #27ae60; }
        .stat-card.cancelled .number { color: #e74c3c; }

        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-bar a {
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border);
            transition: 0.2s;
        }
        .filter-bar a.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        .filter-bar a:hover:not(.active) {
            background: var(--accent-light);
        }

        .order-row {
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
        .order-row:hover {
            box-shadow: var(--shadow-card);
        }
        .order-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .order-info .order-id {
            font-weight: 700;
            font-size: 16px;
        }
        .order-info .order-user {
            color: var(--text-secondary);
        }
        .order-info .order-date {
            font-size: 14px;
            color: var(--text-secondary);
        }
        .order-info .order-total {
            font-weight: 700;
            color: var(--accent);
        }
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .order-status.pending { background: #fef9e7; color: #f39c12; }
        .order-status.processing { background: #ebf5fb; color: #3498db; }
        .order-status.completed { background: #eafaf1; color: #27ae60; }
        .order-status.cancelled { background: #fdedec; color: #e74c3c; }
        
        .order-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .order-actions select {
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        .btn-detail {
            padding: 6px 16px;
            border-radius: 8px;
            border: none;
            background: var(--accent);
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-detail:hover {
            background: var(--accent-dark);
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
            max-width: 600px;
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
        .order-details-grid .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }
        .order-items-list .item-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            background: var(--bg-primary);
            border-radius: 8px;
            margin-bottom: 4px;
        }
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: var(--text-secondary);
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
                <a href="orders.php" class="active">📦 سفارشات</a>
                <a href="products.php">☕ محصولات</a>
                <a href="users.php">👥 کاربران</a>
                <a href="../index.php" class="logout-link">🏠 بازگشت به سایت</a>
            </nav>
        </aside>

        <!-- محتوای اصلی -->
        <main class="admin-content">
            <div class="page-header">
                <h1>📦 مدیریت سفارشات</h1>
            </div>

            <!-- آمار -->
            <div class="orders-stats">
                <div class="stat-card">
                    <span class="number"><?php echo $stats['total'] ?? 0; ?></span>
                    <span class="label">کل سفارشات</span>
                </div>
                <div class="stat-card pending">
                    <span class="number"><?php echo $stats['pending'] ?? 0; ?></span>
                    <span class="label">در انتظار</span>
                </div>
                <div class="stat-card processing">
                    <span class="number"><?php echo $stats['processing'] ?? 0; ?></span>
                    <span class="label">در حال پردازش</span>
                </div>
                <div class="stat-card completed">
                    <span class="number"><?php echo $stats['completed'] ?? 0; ?></span>
                    <span class="label">تکمیل شده</span>
                </div>
                <div class="stat-card cancelled">
                    <span class="number"><?php echo $stats['cancelled'] ?? 0; ?></span>
                    <span class="label">لغو شده</span>
                </div>
            </div>

            <!-- فیلتر -->
            <div class="filter-bar">
                <a href="?status=all" class="<?php echo $statusFilter === 'all' ? 'active' : ''; ?>">همه</a>
                <a href="?status=pending" class="<?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">در انتظار</a>
                <a href="?status=processing" class="<?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">در حال پردازش</a>
                <a href="?status=completed" class="<?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">تکمیل شده</a>
                <a href="?status=cancelled" class="<?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">لغو شده</a>
            </div>

            <!-- لیست سفارشات -->
            <?php if (empty($orders)): ?>
                <div class="empty-state">هیچ سفارشی یافت نشد</div>
            <?php else: ?>
                <?php foreach ($orders as $order): 
                    $statusLabels = ['pending' => 'در انتظار', 'processing' => 'در حال پردازش', 'completed' => 'تکمیل شده', 'cancelled' => 'لغو شده'];
                ?>
                <div class="order-row" id="order-<?php echo $order['id']; ?>">
                    <div class="order-info">
                        <span class="order-id">#<?php echo $order['id']; ?></span>
                        <span class="order-user">👤 <?php echo htmlspecialchars($order['user_name']); ?></span>
                        <span class="order-date">📅 <?php echo jdate($order['created_at']); ?></span>
                        <span class="order-total">💰 <?php echo formatPrice($order['total_amount']); ?></span>
                        <span class="order-status <?php echo $order['status']; ?>">
                            <?php echo $statusLabels[$order['status']] ?? $order['status']; ?>
                        </span>
                    </div>
                    <div class="order-actions">
                        <select onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)">
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>در انتظار</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>در حال پردازش</option>
                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>تکمیل شده</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>لغو شده</option>
                        </select>
                        <button class="btn-detail" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                            جزئیات
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- مودال جزئیات سفارش -->
    <div class="modal-overlay" id="orderDetailsModal">
        <div class="modal">
            <button class="modal-close" onclick="closeOrderDetails()">&times;</button>
            <div class="modal-title">📋 جزئیات سفارش</div>
            <div id="orderDetailsContent">
                <div class="empty-state">در حال بارگذاری...</div>
            </div>
        </div>
    </div>

    <script>
    async function updateOrderStatus(orderId, status) {
        if (!confirm('آیا از تغییر وضعیت این سفارش مطمئنید؟')) {
            location.reload();
            return;
        }

        try {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', status);

            const response = await fetch('../api/orders.php?action=update_status', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                alert('✅ ' + result.message);
                location.reload();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('خطا در ارتباط با سرور');
        }
    }

    async function viewOrderDetails(orderId) {
        const modal = document.getElementById('orderDetailsModal');
        const content = document.getElementById('orderDetailsContent');
        
        content.innerHTML = '<div class="empty-state">در حال بارگذاری...</div>';
        modal.classList.add('active');

        try {
            const response = await fetch(`../api/orders.php?action=details&id=${orderId}`);
            const result = await response.json();

            if (result.success) {
                const order = result.order;
                const statusLabels = {
                    'pending': 'در انتظار',
                    'processing': 'در حال پردازش',
                    'completed': 'تکمیل شده',
                    'cancelled': 'لغو شده'
                };
                const statusColors = {
                    'pending': '#f39c12',
                    'processing': '#3498db',
                    'completed': '#27ae60',
                    'cancelled': '#e74c3c'
                };

                let itemsHtml = order.items.map(item => `
                    <div class="item-row">
                        <span>${item.product_name}</span>
                        <span>${item.quantity} × ${item.formatted_price}</span>
                        <span><strong>${item.formatted_subtotal}</strong></span>
                    </div>
                `).join('');

                content.innerHTML = `
                    <div class="order-details-grid">
                        <div class="detail-row">
                            <span><strong>شماره سفارش:</strong></span>
                            <span>#${order.id}</span>
                        </div>
                        <div class="detail-row">
                            <span><strong>کاربر:</strong></span>
                            <span>${order.user_name}</span>
                        </div>
                        <div class="detail-row">
                            <span><strong>تاریخ:</strong></span>
                            <span>${order.formatted_date}</span>
                        </div>
                        <div class="detail-row">
                            <span><strong>وضعیت:</strong></span>
                            <span style="color:${statusColors[order.status] || '#000'}">
                                ${statusLabels[order.status] || order.status}
                            </span>
                        </div>
                        <div class="detail-row">
                            <span><strong>مبلغ کل:</strong></span>
                            <span><strong>${order.formatted_total}</strong></span>
                        </div>
                        <div class="order-items-list">
                            <h4>محصولات:</h4>
                            ${itemsHtml}
                            <div class="item-row" style="font-weight:700;border-top:2px solid var(--border);margin-top:8px;padding-top:12px;">
                                <span>مجموع</span>
                                <span></span>
                                <span>${order.formatted_total}</span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = '<div class="empty-state" style="color:red;">❌ ' + result.message + '</div>';
            }
        } catch (error) {
            console.error('Error:', error);
            content.innerHTML = '<div class="empty-state" style="color:red;">❌ خطا در دریافت اطلاعات</div>';
        }
    }

    function closeOrderDetails() {
        document.getElementById('orderDetailsModal').classList.remove('active');
    }

    document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeOrderDetails();
        }
    });

    function jdate(datetime) {
        const date = new Date(datetime);
        return date.toLocaleDateString('fa-IR') + ' ' + 
               date.toLocaleTimeString('fa-IR', {hour: '2-digit', minute: '2-digit'});
    }
    </script>
</body>
</html>