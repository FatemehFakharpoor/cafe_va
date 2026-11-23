<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isAdmin()) {
    header('Location: /cafe_va/index.php');
    exit;
}

// دریافت لیست کاربران
$stmt = $pdo->query("
    SELECT id, name, phone, is_admin, created_at 
    FROM users 
    ORDER BY id DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کاربران | کافه وی‌ای</title>
    <!-- ====== لینک‌های CSS ====== -->
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .user-stats .stat-card {
            background: var(--bg-secondary);
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--border);
        }
        .user-stats .stat-card .number {
            font-size: 24px;
            font-weight: 700;
            display: block;
            color: var(--accent);
        }
        .user-stats .stat-card .label {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .user-row {
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
        .user-row:hover {
            box-shadow: var(--shadow-card);
        }
        .user-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        .user-info .user-name {
            font-weight: 700;
            font-size: 16px;
        }
        .user-info .user-phone {
            color: var(--text-secondary);
        }
        .user-info .user-date {
            font-size: 14px;
            color: var(--text-secondary);
        }
        .user-role-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .user-role-badge.admin {
            background: #ebf5fb;
            color: #3498db;
        }
        .user-role-badge.user {
            background: #f0f0f0;
            color: #666;
        }
        .user-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-toggle-role {
            padding: 6px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
            font-size: 14px;
        }
        .btn-toggle-role.make-admin {
            background: var(--accent);
            color: white;
        }
        .btn-toggle-role.make-admin:hover {
            background: var(--accent-dark);
        }
        .btn-toggle-role.remove-admin {
            background: #fdedec;
            color: #e74c3c;
        }
        .btn-toggle-role.remove-admin:hover {
            background: #fadbd8;
        }
        .btn-delete-user {
            padding: 6px 16px;
            border-radius: 8px;
            border: none;
            background: #e74c3c;
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-delete-user:hover {
            background: #c0392b;
        }
        .btn-delete-user:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
                <a href="orders.php">📦 سفارشات</a>
                <a href="products.php">☕ محصولات</a>
                <a href="users.php" class="active">👥 کاربران</a>
                <a href="../index.php" class="logout-link">🏠 بازگشت به سایت</a>
            </nav>
        </aside>

        <!-- محتوای اصلی -->
        <main class="admin-content">
            <div class="page-header">
                <h1>👥 مدیریت کاربران</h1>
            </div>

            <!-- آمار -->
            <?php
            $totalUsers = count($users);
            $totalAdmins = count(array_filter($users, function($u) { return $u['is_admin'] == 1; }));
            $totalRegularUsers = $totalUsers - $totalAdmins;
            ?>
            <div class="user-stats">
                <div class="stat-card">
                    <span class="number"><?php echo $totalUsers; ?></span>
                    <span class="label">کل کاربران</span>
                </div>
                <div class="stat-card">
                    <span class="number"><?php echo $totalAdmins; ?></span>
                    <span class="label">ادمین‌ها</span>
                </div>
                <div class="stat-card">
                    <span class="number"><?php echo $totalRegularUsers; ?></span>
                    <span class="label">کاربران عادی</span>
                </div>
            </div>

            <!-- لیست کاربران -->
            <?php if (empty($users)): ?>
                <div class="empty-state">هیچ کاربری یافت نشد</div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                <div class="user-row" id="user-<?php echo $user['id']; ?>">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                        <span class="user-phone">📱 <?php echo $user['phone']; ?></span>
                        <span class="user-role-badge <?php echo $user['is_admin'] ? 'admin' : 'user'; ?>">
                            <?php echo $user['is_admin'] ? '👑 ادمین' : '👤 کاربر'; ?>
                        </span>
                        <span class="user-date">📅 <?php echo jdate($user['created_at']); ?></span>
                    </div>
                    <div class="user-actions">
                        <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                            <button class="btn-toggle-role <?php echo $user['is_admin'] ? 'remove-admin' : 'make-admin'; ?>" 
                                    onclick="toggleUserRole(<?php echo $user['id']; ?>, <?php echo $user['is_admin'] ? '0' : '1'; ?>)">
                                <?php echo $user['is_admin'] ? '⬇️ لغو ادمین' : '⬆️ تبدیل به ادمین'; ?>
                            </button>
                            <button class="btn-delete-user" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                🗑️ حذف
                            </button>
                        <?php else: ?>
                            <span style="color:var(--text-secondary);font-size:14px;">(خودتان)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
    async function toggleUserRole(userId, isAdmin) {
        const action = isAdmin ? 'تبدیل به ادمین' : 'لغو ادمین';
        if (!confirm(`آیا از ${action} این کاربر مطمئنید؟`)) return;

        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('is_admin', isAdmin);

            const response = await fetch('../api/users.php?action=update_role', {
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

    async function deleteUser(userId) {
        if (!confirm('⚠️ آیا از حذف این کاربر مطمئنید؟ این عملیات قابل بازگشت نیست!')) return;

        try {
            const response = await fetch(`../api/users.php?action=delete&id=${userId}`);
            const result = await response.json();

            if (result.success) {
                alert('✅ ' + result.message);
                const row = document.getElementById(`user-${userId}`);
                if (row) row.remove();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('خطا در ارتباط با سرور');
        }
    }

    function jdate(datetime) {
        const date = new Date(datetime);
        return date.toLocaleDateString('fa-IR') + ' ' + 
               date.toLocaleTimeString('fa-IR', {hour: '2-digit', minute: '2-digit'});
    }
    </script>
</body>
</html>