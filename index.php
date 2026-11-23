<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// ====== دریافت محصولات از دیتابیس ======
$category = $_GET['category'] ?? 'all';
$sql = "SELECT * FROM products";
if ($category !== 'all') {
    $sql .= " WHERE category = ?";
}
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
if ($category !== 'all') {
    $stmt->execute([$category]);
} else {
    $stmt->execute();
}
$products = $stmt->fetchAll();

// ====== بررسی کاربر ویژه (mobin) ======
$isSpecialUser = false;
if (isLoggedIn()) {
    $user = $_SESSION['user'];
    if ($user['name'] === 'mobin' && $user['email'] === 'mobinmk90mk16s@gmail.com') {
        $isSpecialUser = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>کافه وی ای</title>
    
    <!-- ====== لینک‌های Swiper CSS ====== -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    
    <link rel="stylesheet" href="/cafe_va/assets/css/app.css">

    <style>
        /* ==========================================
           استایل‌های کامل
           ========================================== */
        :root {
            --bg-primary: #f8f5f0;
            --bg-secondary: #ffffff;
            --text-primary: #2e2e2e;
            --text-secondary: #5c5c5c;
            --accent: #b08968;
            --accent-light: #ddc3a5;
            --accent-dark: #8b5e3c;
            --border: #e0d6cc;
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.04);
            --shadow-card: 0 8px 24px rgba(0,0,0,0.05);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.15);
            --radius-sm: 12px;
            --radius-md: 20px;
            --radius-lg: 28px;
            --btn-text: #ffffff;
            --danger: #c1666b;
            --success: #6b8e5a;
            --overlay: rgba(0,0,0,0.3);
            --overlay-heavy: rgba(0,0,0,0.7);
            --cart-bg: #ffffff;
            --cart-text: #2e2e2e;
        }

        body.dark {
            --bg-primary: #1e1e1e;
            --bg-secondary: #2c2c2c;
            --text-primary: #f0e9e0;
            --text-secondary: #b7a99a;
            --accent: #c69c6d;
            --accent-light: #5e4b3a;
            --accent-dark: #a0784a;
            --border: #3f3a35;
            --shadow-sm: 0 4px 12px rgba(0,0,0,0.3);
            --shadow-card: 0 8px 24px rgba(0,0,0,0.4);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.5);
            --cart-bg: #2c2c2c;
            --cart-text: #f0e9e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.25s, color 0.25s;
            min-height: 100vh;
            line-height: 1.6;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        /* ====== لودینگ ====== */
        #loadingOverlay {
            position: fixed;
            inset: 0;
            background: var(--bg-primary);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 30px;
            transition: opacity 0.8s ease, visibility 0.8s ease;
            opacity: 1;
            visibility: visible;
        }
        #loadingOverlay.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .loading-logo {
            position: relative;
            width: 120px;
            height: 120px;
            animation: goldenPulse 1.5s ease-in-out infinite;
        }
        .loading-logo svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        @keyframes goldenPulse {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 20px rgba(184, 137, 104, 0.3)); }
            50% { transform: scale(1.08); filter: drop-shadow(0 0 40px rgba(184, 137, 104, 0.7)) drop-shadow(0 0 80px rgba(184, 137, 104, 0.3)); }
        }

        .spinner-wrapper {
            position: relative;
            width: 70px;
            height: 70px;
        }
        .spinner-ring {
            position: absolute;
            border-radius: 50%;
            border: 3px solid transparent;
            animation: spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }
        .spinner-ring:nth-child(1) {
            width: 70px;
            height: 70px;
            border-top-color: var(--accent);
            animation-duration: 1.2s;
        }
        .spinner-ring:nth-child(2) {
            width: 50px;
            height: 50px;
            top: 10px;
            left: 10px;
            border-bottom-color: var(--accent-light);
            animation-duration: 0.8s;
            animation-direction: reverse;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .progress-container {
            width: 250px;
            max-width: 80%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .progress-bar {
            width: 100%;
            height: 6px;
            background: var(--border);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--accent-light), var(--accent), var(--accent-dark));
            border-radius: 10px;
            transition: width 0.1s linear;
        }
        .progress-text {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 600;
            letter-spacing: 1px;
        }
        .loading-text {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
            animation: textFade 2s ease-in-out infinite;
        }

        @keyframes textFade {
            0%, 100% { opacity: 0.3; transform: translateY(5px); }
            50% { opacity: 1; transform: translateY(0); }
        }

        .particles-container {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--accent);
            border-radius: 50%;
            opacity: 0;
            animation: floatParticle 4s ease-in-out infinite;
        }
        .particle:nth-child(1) { left: 10%; animation-delay: 0s; width: 4px; height: 4px; }
        .particle:nth-child(2) { left: 25%; animation-delay: 1.2s; width: 8px; height: 8px; }
        .particle:nth-child(3) { left: 40%; animation-delay: 0.6s; width: 5px; height: 5px; }
        .particle:nth-child(4) { left: 55%; animation-delay: 2s; width: 7px; height: 7px; }
        .particle:nth-child(5) { left: 70%; animation-delay: 0.3s; width: 4px; height: 4px; }
        .particle:nth-child(6) { left: 85%; animation-delay: 1.8s; width: 6px; height: 6px; }
        .particle:nth-child(7) { left: 15%; animation-delay: 2.5s; width: 9px; height: 9px; }
        .particle:nth-child(8) { left: 50%; animation-delay: 0.9s; width: 5px; height: 5px; }
        .particle:nth-child(9) { left: 75%; animation-delay: 3s; width: 7px; height: 7px; }
        .particle:nth-child(10) { left: 92%; animation-delay: 1.5s; width: 4px; height: 4px; }
        .particle:nth-child(11) { left: 5%; animation-delay: 0.7s; width: 6px; height: 6px; }
        .particle:nth-child(12) { left: 60%; animation-delay: 2.2s; width: 8px; height: 8px; }

        @keyframes floatParticle {
            0% { opacity: 0; transform: translateY(0) scale(0); }
            20% { opacity: 0.6; }
            80% { opacity: 0.6; }
            100% { opacity: 0; transform: translateY(-80px) scale(1.5); }
        }

        @media (max-width: 480px) {
            .loading-logo { width: 90px; height: 90px; }
            .spinner-wrapper { width: 55px; height: 55px; }
            .spinner-ring:nth-child(1) { width: 55px; height: 55px; }
            .spinner-ring:nth-child(2) { width: 38px; height: 38px; top: 8px; left: 8px; }
            .progress-container { width: 200px; }
            .loading-text { font-size: 0.85rem; }
        }

        /* ====== خوش‌آمدگویی ====== */
        .welcome-overlay {
            position: fixed;
            inset: 0;
            background: var(--overlay-heavy);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.4s, visibility 0.4s;
        }
        .welcome-overlay.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .welcome-box {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            width: 85%;
            max-width: 360px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--border);
            animation: fadeInUpWelcome 0.5s ease;
        }

        @keyframes fadeInUpWelcome {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-box-top {
            padding: 32px 20px 20px;
            text-align: center;
        }
        .welcome-box-top h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 4px;
        }
        .welcome-box-divider {
            height: 1px;
            background: var(--border);
            margin: 0;
        }
        .welcome-box-bottom {
            padding: 16px 20px;
            text-align: center;
        }
        .welcome-box-bottom .btn-close-welcome {
            background: var(--accent);
            color: var(--btn-text);
            border: none;
            padding: 12px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: 0.2s;
        }
        .welcome-box-bottom .btn-close-welcome:active {
            background: var(--accent-dark);
            transform: scale(0.96);
        }

        /* ====== توست خوش‌آمدگویی ====== */
        .toast-welcome {
            position: fixed;
            bottom: -120px;
            left: 16px;
            right: 16px;
            max-width: 500px;
            margin: 0 auto;
            background: var(--accent);
            color: var(--btn-text);
            padding: 16px 20px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.25);
            z-index: 300;
            transition: bottom 0.5s cubic-bezier(0.22, 0.61, 0.36, 1);
            font-weight: 600;
            font-size: 0.95rem;
        }
        .toast-welcome.show {
            bottom: 24px;
        }
        .toast-close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .toast-close-btn:hover {
            background: rgba(255,255,255,0.35);
        }
        .toast-timer-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255,255,255,0.7);
            border-radius: 0 0 var(--radius-md) var(--radius-md);
        }

        /* ====== هدر ====== */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background-color: var(--bg-secondary);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 1px solid var(--border);
            margin-bottom: 8px;
            transition: background-color 0.25s;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .hamburger-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 5px;
            width: 36px;
            height: 36px;
            justify-content: center;
            align-items: center;
            transition: 0.2s;
        }
        .hamburger-btn:active {
            transform: scale(0.9);
        }
        .hamburger-btn span {
            display: block;
            width: 24px;
            height: 2px;
            background: var(--text-primary);
            border-radius: 2px;
            transition: 0.3s;
        }

        .logo {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            color: var(--accent);
        }

        .theme-toggle {
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 50px;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.3rem;
            color: var(--text-primary);
            transition: all 0.25s;
            box-shadow: var(--shadow-sm);
        }
        .theme-toggle:active {
            transform: scale(0.9);
        }

        .cart-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .cart-circle {
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s;
            box-shadow: var(--shadow-sm);
            color: var(--text-primary);
            font-size: 1.4rem;
        }
        .cart-circle:active {
            transform: scale(0.9);
        }
        .cart-badge {
            position: absolute;
            top: -4px;
            left: -4px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            pointer-events: none;
            animation: bounceIn 0.4s ease;
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* ====== استایل Swiper ====== */
        .slider-wrapper {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            padding: 0 16px;
            position: relative;
        }

        .slider-container {
            width: 100%;
            height: 35vh;
            max-height: 400px;
            min-height: 150px;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            box-shadow: var(--shadow-card);
            background: var(--bg-primary);
        }

        .slider-container .swiper-slide {
            width: 100%;
            height: 100%;
            border-radius: 20px;
            overflow: hidden;
        }

        .slider-container .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* دکمه‌های ناوبری Swiper */
        .swiper-button-next,
        .swiper-button-prev {
            color: var(--accent);
            background: rgba(255,255,255,0.8);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .swiper-button-next::after,
        .swiper-button-prev::after {
            font-size: 18px;
            font-weight: 700;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: var(--accent);
            color: white;
        }

        /* Pagination دات‌ها */
        .swiper-pagination {
            position: absolute;
            bottom: 12px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 8px;
            z-index: 10;
        }

        .swiper-pagination-bullet {
            width: 10px;
            height: 10px;
            background: rgba(255,255,255,0.6);
            opacity: 0.8;
            border-radius: 50%;
            transition: all 0.3s;
            cursor: pointer;
        }

        .swiper-pagination-bullet-active {
            background: var(--accent);
            width: 28px;
            border-radius: 10px;
            opacity: 1;
        }

        /* ====== دسته‌بندی‌ها ====== */
        .category-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 12px 16px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            max-width: 700px;
            margin: 0 auto;
        }
        .category-scroll::-webkit-scrollbar {
            display: none;
        }
        .category-chip {
            flex-shrink: 0;
            padding: 10px 18px;
            border-radius: 50px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            transition: 0.2s;
            white-space: nowrap;
            color: var(--text-primary);
        }
        .category-chip:active {
            transform: scale(0.94);
        }
        .category-chip.active {
            background: var(--accent);
            color: var(--btn-text);
            border-color: var(--accent);
        }

        /* ====== محصولات ====== */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 16px;
            padding: 12px 16px 24px;
            max-width: 700px;
            margin: 0 auto;
        }

        .menu-item {
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            padding: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border);
            transition: transform 0.3s, box-shadow 0.3s, background 0.25s;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            animation: fadeInUp 0.5s ease both;
        }
        .menu-item:active {
            transform: scale(0.96);
            background: var(--accent-light);
        }
        .menu-item:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .item-image {
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--accent);
        }
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }

        .item-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 4px;
            color: var(--text-primary);
        }
        .item-price {
            font-weight: 400;
            font-size: 0.85rem;
            color: var(--text-secondary);
            background: var(--bg-primary);
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 6px;
            display: inline-block;
        }
        .item-category-tag {
            font-size: 0.7rem;
            background: var(--accent-light);
            color: var(--text-primary);
            padding: 2px 8px;
            border-radius: 12px;
            margin-top: 4px;
        }

        /* ====== همبرگری ====== */
        .hamburger-overlay {
            position: fixed;
            inset: 0;
            background: var(--overlay);
            z-index: 90;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .hamburger-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .hamburger-panel {
            position: fixed;
            top: 0;
            right: 0;
            width: 300px;
            height: 100vh;
            background: var(--bg-secondary);
            box-shadow: -8px 0 30px rgba(0,0,0,0.15);
            z-index: 95;
            transform: translateX(100%);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 20px;
            gap: 8px;
        }
        .hamburger-panel.open {
            transform: translateX(0);
        }
        .hamburger-panel a,
        .hamburger-panel .hamburger-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
            background: none;
            border: none;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 0.95rem;
            width: 100%;
            text-align: right;
        }
        .hamburger-panel a:hover,
        .hamburger-panel .hamburger-item:hover {
            background: var(--bg-primary);
            transform: translateX(-4px);
        }
        .hamburger-panel svg {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }

        /* ====== مودال‌ها ====== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 400px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            position: relative;
            border: 1px solid var(--border);
            animation: bounceIn 0.4s ease;
        }
        .modal-close {
            position: absolute;
            top: 12px;
            left: 12px;
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--text-secondary);
            line-height: 1;
            transition: 0.2s;
        }
        .modal-close:hover {
            color: var(--danger);
        }
        .modal-title {
            font-weight: 700;
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 4px;
            color: var(--accent);
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .form-group input,
        .form-group select {
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(176, 137, 104, 0.15);
        }

        .btn {
            padding: 12px 20px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            position: relative;
            overflow: hidden;
        }
        .btn:active {
            transform: scale(0.95);
        }
        .btn-primary {
            background: var(--accent);
            color: var(--btn-text);
        }
        .btn-primary:hover {
            background: var(--accent-dark);
        }
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .auth-switch {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        .auth-switch a {
            color: var(--accent);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }
        .auth-switch a:hover {
            text-decoration: underline;
        }

        /* ====== پنل مدیریت ویژه (mobin) ====== */
        .special-admin-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 150;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .special-admin-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .special-admin-panel {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 500px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            padding: 30px 24px;
            border: 1px solid var(--border);
            animation: bounceIn 0.4s ease;
        }
        .special-admin-panel h2 {
            color: var(--accent);
            text-align: center;
            margin-bottom: 20px;
        }
        .special-admin-panel .admin-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background: var(--bg-primary);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
        }
        .special-admin-panel .admin-item .item-info {
            display: flex;
            flex-direction: column;
        }
        .special-admin-panel .admin-item .item-info .name {
            font-weight: 600;
        }
        .special-admin-panel .admin-item .item-info .details {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        .special-admin-panel .admin-actions {
            display: flex;
            gap: 8px;
        }
        .special-admin-panel .admin-actions button {
            padding: 4px 12px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .special-admin-panel .admin-actions .edit-btn {
            background: var(--accent);
            color: white;
        }
        .special-admin-panel .admin-actions .delete-btn {
            background: var(--danger);
            color: white;
        }
        .special-admin-panel .add-btn {
            width: 100%;
            padding: 12px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            margin-top: 12px;
            font-size: 1rem;
        }
        .special-admin-panel .add-btn:hover {
            background: var(--accent-dark);
        }
        .special-admin-panel .close-admin-btn {
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
            margin-top: 8px;
            font-size: 0.9rem;
        }
        .special-admin-panel .close-admin-btn:hover {
            background: var(--bg-primary);
        }

        /* ====== سبد خرید ====== */
        .cart-panel {
            position: fixed;
            top: 0;
            right: 0;
            width: 100%;
            max-width: 420px;
            height: 100vh;
            height: 100dvh;
            background: var(--cart-bg);
            box-shadow: -8px 0 30px rgba(0,0,0,0.15);
            z-index: 100;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 1px solid var(--border);
            color: var(--cart-text);
        }
        .cart-panel.open {
            transform: translateX(0);
        }
        .cart-overlay {
            position: fixed;
            inset: 0;
            background: var(--overlay);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .cart-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .cart-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 1.2rem;
        }
        .close-cart {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: var(--text-primary);
            transition: 0.2s;
        }
        .close-cart:hover {
            color: var(--danger);
        }
        .cart-items-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .cart-empty {
            text-align: center;
            color: var(--text-secondary);
            margin-top: 40px;
        }
        .cart-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-primary);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            border: 1px solid var(--border);
            animation: fadeInUp 0.3s ease;
        }
        .cart-footer {
            border-top: 1px solid var(--border);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-secondary);
            gap: 12px;
        }
        .total-amount {
            font-weight: 700;
            font-size: 1.1rem;
        }
        .checkout-btn {
            background: var(--accent);
            border: none;
            color: var(--btn-text);
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .checkout-btn:active {
            transform: scale(0.95);
        }

        /* ====== فوتر ====== */
        .site-footer {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border);
            padding: 32px 20px 20px;
            margin-top: 32px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 24px;
            max-width: 700px;
            margin: 0 auto 24px;
        }
        .footer-col h4 {
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--accent);
            font-size: 0.95rem;
        }
        .footer-col a {
            display: block;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            margin-bottom: 8px;
            transition: 0.2s;
        }
        .footer-col a:hover {
            color: var(--accent);
            transform: translateX(-4px);
        }
        .footer-social {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 16px;
        }
        .footer-social a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            transition: 0.2s;
        }
        .footer-social a:hover {
            background: var(--accent);
            border-color: var(--accent);
            transform: translateY(-4px);
        }
        .footer-social svg {
            width: 20px;
            height: 20px;
            fill: var(--text-primary);
        }
        .footer-social a:hover svg {
            fill: var(--btn-text);
        }
        .footer-bottom {
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-secondary);
            border-top: 1px solid var(--border);
            padding-top: 16px;
        }

        @media (max-width: 400px) {
            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .hamburger-panel {
                width: 75%;
                max-width: none;
            }
            .swiper-button-next,
            .swiper-button-prev {
                display: none;
            }
        }

        .login-email {
            direction: ltr;
            text-align: left;
        }
        .login-email::placeholder {
            text-align: right;
        }
    </style>
</head>

<body>

    <!-- ====== لودینگ ====== -->
    <div id="loadingOverlay">
        <div class="particles-container">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>

        <div class="loading-logo">
            <svg viewBox="0 0 600 400" xmlns="http://www.w3.org/2000/svg">
                <rect width="600" height="400" fill="transparent" />
                <circle cx="300" cy="200" r="150" fill="none" stroke="var(--accent)" stroke-width="2" opacity="0.15" />
                <circle cx="300" cy="200" r="132" fill="none" stroke="var(--accent)" stroke-width="1.5" opacity="0.25" />
                <circle cx="300" cy="200" r="105" fill="none" stroke="var(--accent)" stroke-width="1" opacity="0.35" />
                <ellipse cx="175" cy="200" rx="22" ry="18" fill="var(--accent)" opacity="0.6" />
                <ellipse cx="425" cy="200" rx="22" ry="18" fill="var(--accent)" opacity="0.6" />
                <path d="M165 195 C175 190, 180 190, 187 200 C180 210, 175 210, 165 205" fill="none" stroke="var(--bg-primary)" stroke-width="3" stroke-linecap="round" />
                <path d="M415 195 C425 190, 430 190, 437 200 C430 210, 425 210, 415 205" fill="none" stroke="var(--bg-primary)" stroke-width="3" stroke-linecap="round" />
                <text x="300" y="80" text-anchor="middle" fill="var(--accent)" font-size="28" font-weight="700" letter-spacing="4">COFFEE</text>
                <text x="300" y="340" text-anchor="middle" fill="var(--accent)" font-size="28" font-weight="700" letter-spacing="4">COFFEE</text>
                <path d="M245 215 h110 c0 35 -20 58 -55 58 s-55 -23 -55 -58z" fill="var(--accent-light)" opacity="0.3" />
                <path d="M355 225 c20 0 35 10 35 22 s-15 22 -35 22" fill="none" stroke="var(--accent-light)" stroke-width="2" opacity="0.5" />
                <path d="M235 245 h120" stroke="var(--accent-light)" stroke-width="1.5" opacity="0.3" />
                <path d="M260 279 h70" stroke="var(--accent-light)" stroke-width="1.5" opacity="0.3" />
                <path d="M270 210 c0 -18 20 -28 20 -45" stroke="var(--accent)" stroke-width="2" opacity="0.4" fill="none" stroke-linecap="round" />
                <path d="M300 210 c0 -18 20 -28 20 -45" stroke="var(--accent)" stroke-width="2" opacity="0.4" fill="none" stroke-linecap="round" />
                <path d="M325 210 c0 -18 20 -28 20 -45" stroke="var(--accent)" stroke-width="2" opacity="0.4" fill="none" stroke-linecap="round" />
                <path d="M252 217 h96 c0 28 -18 45 -48 45 s-48 -17 -48 -45z" fill="var(--accent)" opacity="0.7" />
                <text x="300" y="118" text-anchor="middle" fill="var(--accent)" font-size="11" font-weight="300" letter-spacing="8" opacity="0.6">CAFE</text>
                <text x="300" y="302" text-anchor="middle" fill="var(--accent)" font-size="11" font-weight="300" letter-spacing="8" opacity="0.6">CAFE</text>
            </svg>
        </div>

        <div class="spinner-wrapper">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
        </div>

        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <span class="progress-text" id="progressText">0%</span>
        </div>

        <div class="loading-text">در حال آماده‌سازی...</div>
    </div>

    <!-- ====== خوش‌آمدگویی ====== -->
    <div class="welcome-overlay" id="welcomeOverlay">
        <div class="welcome-box">
            <div class="welcome-box-top">
                <h2>به کافه وی ای خوش آمدید</h2>
            </div>
            <hr class="welcome-box-divider">
            <div class="welcome-box-bottom">
                <button class="btn-close-welcome" id="closeWelcomeBtn">بستن</button>
            </div>
        </div>
    </div>

    <!-- ====== توست خوش‌آمدگویی ====== -->
    <div class="toast-welcome" id="welcomeToast">
        <span id="welcomeMessage"></span>
        <button class="toast-close-btn" id="toastCloseBtn">&times;</button>
        <div class="toast-timer-bar" id="toastTimerBar" style="width:0%;"></div>
    </div>

    <!-- ====== هدر ====== -->
    <header class="header">
        <div class="header-right">
            <button class="hamburger-btn" id="hamburgerBtn">
                <span></span><span></span><span></span>
            </button>
            <div class="logo">کافه وی ای</div>
        </div>
        <div class="header-left">
            <button class="theme-toggle" id="themeToggle"><span id="themeIcon">&#9788;</span></button>
            <div class="cart-icon-wrapper">
                <div class="cart-circle" id="openCartBtn">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1" />
                        <circle cx="20" cy="21" r="1" />
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                    </svg>
                </div>
                <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
            </div>
        </div>
    </header>

    <!-- ====== منوی همبرگری ====== -->
    <div class="hamburger-overlay" id="hamburgerOverlay"></div>
    <nav class="hamburger-panel" id="hamburgerPanel">
        <h2>منو</h2>
        <hr>
        
        <?php if (isLoggedIn()): ?>
            <!-- ====== کاربر لاگین شده ====== -->
            <div style="padding:12px 16px;background:var(--bg-primary);border-radius:var(--radius-sm);margin-bottom:8px;">
                <div style="font-weight:700;color:var(--accent);">
                    <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                </div>
                <div style="font-size:0.8rem;color:var(--text-secondary);">
                    <?php echo htmlspecialchars($_SESSION['user']['email']); ?>
                </div>
                <div style="font-size:0.7rem;color:var(--success);margin-top:4px;">
                    ✅ وارد شده‌اید
                </div>
            </div>
            
            <div class="hamburger-item" id="hamburgerLogout" style="color:var(--danger);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                خروج از حساب
            </div>
        <?php else: ?>
            <!-- ====== کاربر مهمان ====== -->
            <div class="hamburger-item" id="hamburgerProfile">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                ورود / ثبت‌نام
            </div>
        <?php endif; ?>
        
        <hr>
        <a href="blog.html" class="hamburger-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
            </svg>
            مقالات
        </a>
        <hr>
        <a href="contact.html" class="hamburger-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
            ارتباط با ما
        </a>
    </nav>

    <!-- ====== محتوای اصلی ====== -->
    <main>
        <div class="category-scroll" id="categoryScroll">
            <div class="category-chip active" data-category="all">همه</div>
            <div class="category-chip" data-category="بارگرم">بارگرم</div>
            <div class="category-chip" data-category="بارسرد">بارسرد</div>
            <div class="category-chip" data-category="کیک">کیک</div>
            <div class="category-chip" data-category="شربت">شربت</div>
            <div class="category-chip" data-category="چای و دمنوش">چای و دمنوش</div>
            <div class="category-chip" data-category="شیک">شیک</div>
        </div>

        <!-- ====== اسلایدر با Swiper ====== -->
        <div class="slider-wrapper">
            <div class="slider-container swiper mySwiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <img src="/cafe_va/Screenshot_20260907_223553_Gallery.jpg" alt="کافه وی ای ۱">
                    </div>
                    <div class="swiper-slide">
                        <img src="/cafe_va/Screenshot_20260907_223556_Gallery.jpg" alt="کافه وی ای ۲">
                    </div>
                    <div class="swiper-slide">
                        <img src="/cafe_va/Screenshot_20260907_223606_Gallery.jpg" alt="کافه وی ای ۳">
                    </div>
                </div>
                <!-- دکمه‌های قبلی و بعدی -->
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <!-- دات‌های پایین -->
                <div class="swiper-pagination"></div>
            </div>
        </div>

        <!-- ====== محصولات از دیتابیس ====== -->
        <div class="menu-grid" id="menuGrid">
            <?php if (empty($products)): ?>
                <div style="grid-column:1/-1;text-align:center;padding:40px 0;color:var(--text-secondary);">
                    هیچ محصولی یافت نشد
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="menu-item" data-id="<?php echo $product['id']; ?>" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>)">
                        <?php if ($product['image']): ?>
                            <div class="item-image">
                                <img src="/cafe_va/assets/uploads/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            </div>
                        <?php else: ?>
                            <div class="item-image">☕</div>
                        <?php endif; ?>
                        <div class="item-title"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="item-price"><?php echo formatPrice($product['price']); ?> تومان</div>
                        <div class="item-category-tag"><?php echo $product['category']; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ====== دکمه مدیریت (فقط برای mobin) ====== -->
        <?php if ($isSpecialUser): ?>
        <div style="max-width:700px;margin:0 auto;padding:0 16px;margin-top:20px;">
            <button onclick="openSpecialAdmin()" style="width:100%;padding:14px;background:var(--accent);color:white;border:none;border-radius:var(--radius-sm);font-weight:600;font-size:1rem;cursor:pointer;box-shadow:var(--shadow-card);">
                🛠️ مدیریت محصولات
            </button>
        </div>
        <?php endif; ?>
    </main>

    <!-- ====== فوتر ====== -->
    <footer class="site-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>کافه وی ای</h4>
                <a href="about.html">درباره ما</a>
                <a href="contact.html">ارتباط با ما</a>
                <a href="blog.html">مقالات</a>
            </div>
            <div class="footer-col">
                <h4>خدمات مشتریان</h4>
                <a href="#">سوالات متداول</a>
                <a href="#">شرایط استفاده</a>
                <a href="#">حریم خصوصی</a>
            </div>
            <div class="footer-col">
                <h4>نماد اعتماد</h4>
                <a href="#">اینماد</a>
                <a href="#">مجوز کسب و کار</a>
            </div>
        </div>
        <div class="footer-social">
            <a href="#" title="اینستاگرام">
                <svg viewBox="0 0 24 24">
                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke="white" stroke-width="2" />
                </svg>
            </a>
            <a href="#" title="ایتا">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <text x="12" y="16" text-anchor="middle" fill="white" font-size="10" font-weight="bold">E</text>
                </svg>
            </a>
            <a href="#" title="سروش">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <text x="12" y="16" text-anchor="middle" fill="white" font-size="10" font-weight="bold">S</text>
                </svg>
            </a>
        </div>
        <div class="footer-bottom">
            تمامی حقوق برای کافه وی ای محفوظ است
        </div>
    </footer>

    <!-- ====== مودال لاگین ====== -->
    <div class="modal-overlay" id="loginModal">
        <div class="modal">
            <button class="modal-close" onclick="closeLoginModal()">&times;</button>
            <div class="modal-title" id="loginModalTitle">ورود به حساب</div>
            
            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label>ایمیل</label>
                    <input type="email" id="loginEmail" class="login-email" placeholder="example@gmail.com" required>
                </div>
                <div class="form-group">
                    <label>رمز عبور</label>
                    <input type="password" id="loginPassword" placeholder="******" required>
                </div>
                <button type="submit" class="btn btn-primary">ورود</button>
            </form>

            <div class="auth-switch">
                حساب کاربری ندارید؟ <a onclick="switchToSignup()">ثبت‌نام</a>
            </div>
        </div>
    </div>

    <!-- ====== مودال ثبت‌نام ====== -->
    <div class="modal-overlay" id="signupModal">
        <div class="modal">
            <button class="modal-close" onclick="closeSignupModal()">&times;</button>
            <div class="modal-title">ثبت‌نام</div>
            
            <form id="signupForm" onsubmit="handleSignup(event)">
                <div class="form-group">
                    <label>نام و نام خانوادگی</label>
                    <input type="text" id="signupName" placeholder="مثال: علی محمدی" required>
                </div>
                <div class="form-group">
                    <label>ایمیل</label>
                    <input type="email" id="signupEmail" class="login-email" placeholder="example@gmail.com" required>
                </div>
                <div class="form-group">
                    <label>رمز عبور</label>
                    <input type="password" id="signupPassword" placeholder="حداقل 6 کاراکتر" minlength="6" required>
                </div>
                <button type="submit" class="btn btn-primary">ثبت‌نام</button>
            </form>

            <div class="auth-switch">
                قبلاً ثبت‌نام کردید؟ <a onclick="switchToLogin()">ورود</a>
            </div>
        </div>
    </div>

    <!-- ====== مودال پنل مدیریت ویژه (mobin) ====== -->
    <div class="special-admin-overlay" id="specialAdminOverlay">
        <div class="special-admin-panel">
            <h2>🛠️ مدیریت محصولات</h2>
            <div id="specialAdminItems">
                <?php foreach ($products as $product): ?>
                    <div class="admin-item" id="admin-item-<?php echo $product['id']; ?>">
                        <div class="item-info">
                            <span class="name"><?php echo htmlspecialchars($product['name']); ?></span>
                            <span class="details"><?php echo $product['category']; ?> | <?php echo formatPrice($product['price']); ?></span>
                        </div>
                        <div class="admin-actions">
                            <button class="edit-btn" onclick="editProduct(<?php echo $product['id']; ?>)">✏️</button>
                            <button class="delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="add-btn" onclick="addNewProduct()">➕ افزودن محصول جدید</button>
            <button class="close-admin-btn" onclick="closeSpecialAdmin()">بستن</button>
        </div>
    </div>

    <!-- ====== مودال افزودن/ویرایش محصول ====== -->
    <div class="modal-overlay" id="productModal">
        <div class="modal">
            <button class="modal-close" onclick="closeProductModal()">&times;</button>
            <div class="modal-title" id="productModalTitle">افزودن محصول</div>
            <form id="productForm" enctype="multipart/form-data">
                <input type="hidden" id="editProductId" name="id">
                <div class="form-group">
                    <label>نام محصول</label>
                    <input type="text" id="productName" name="name" required>
                </div>
                <div class="form-group">
                    <label>قیمت (تومان)</label>
                    <input type="number" id="productPrice" name="price" required>
                </div>
                <div class="form-group">
                    <label>دسته‌بندی</label>
                    <select id="productCategory" name="category">
                        <option value="بارگرم">بارگرم</option>
                        <option value="بارسرد">بارسرد</option>
                        <option value="کیک">کیک</option>
                        <option value="شربت">شربت</option>
                        <option value="چای و دمنوش">چای و دمنوش</option>
                        <option value="شیک">شیک</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>عکس محصول</label>
                    <input type="file" id="productImage" name="image" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">ذخیره</button>
            </form>
        </div>
    </div>

    <!-- ====== سبد خرید ====== -->
    <div class="cart-overlay" id="cartOverlay"></div>
    <aside class="cart-panel" id="cartPanel">
        <div class="cart-header">
            <span>سبد خرید</span>
            <button class="close-cart" id="closeCartBtn">&times;</button>
        </div>
        <div class="cart-items-list" id="cartItemsContainer">
            <div class="cart-empty">سبد خرید خالی است</div>
        </div>
        <div class="cart-footer">
            <div class="total-amount">جمع کل: <span id="totalPrice">۰</span> تومان</div>
            <button class="checkout-btn" id="checkoutBtn">پرداخت</button>
        </div>
    </aside>

    <!-- ====== اسکریپت‌ها ====== -->
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <script>
        // ==========================================
        // راه‌اندازی Swiper
        // ==========================================
        const swiper = new Swiper('.mySwiper', {
            loop: true,
            autoplay: {
                delay: 2000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            effect: 'slide',
            speed: 500,
            grabCursor: true,
            watchSlidesProgress: true,
        });

        // ==========================================
        // مدیریت لودینگ
        // ==========================================
        (function() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');

            if (sessionStorage.getItem('loadingShown')) {
                loadingOverlay.style.display = 'none';
                return;
            }

            let progress = 0;
            const interval = setInterval(() => {
                progress += 2;
                if (progress > 100) progress = 100;
                progressFill.style.width = progress + '%';
                progressText.textContent = progress + '%';
                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(() => {
                        loadingOverlay.classList.add('hidden');
                        sessionStorage.setItem('loadingShown', 'true');
                    }, 300);
                }
            }, 50);
        })();

        // ==========================================
        // مدیریت خوش‌آمدگویی
        // ==========================================
        (function() {
            const welcomeOverlay = document.getElementById('welcomeOverlay');
            const closeWelcomeBtn = document.getElementById('closeWelcomeBtn');

            if (sessionStorage.getItem('welcomeShown')) {
                welcomeOverlay.classList.add('hidden');
            }

            closeWelcomeBtn.addEventListener('click', () => {
                welcomeOverlay.classList.add('hidden');
                sessionStorage.setItem('welcomeShown', 'true');
            });
        })();

        // ==========================================
        // مدیریت توست
        // ==========================================
        function showToast(message) {
            const toast = document.getElementById('welcomeToast');
            const msg = document.getElementById('welcomeMessage');
            const bar = document.getElementById('toastTimerBar');
            
            msg.textContent = message;
            toast.classList.add('show');
            
            let width = 100;
            bar.style.width = '100%';
            
            const interval = setInterval(() => {
                width -= 1;
                bar.style.width = width + '%';
                if (width <= 0) {
                    clearInterval(interval);
                    toast.classList.remove('show');
                }
            }, 60);
        }

        document.getElementById('toastCloseBtn').addEventListener('click', () => {
            document.getElementById('welcomeToast').classList.remove('show');
        });

        // ==========================================
        // مدیریت منو همبرگری
        // ==========================================
        document.getElementById('hamburgerBtn').addEventListener('click', () => {
            document.getElementById('hamburgerPanel').classList.toggle('open');
            document.getElementById('hamburgerOverlay').classList.toggle('show');
        });

        document.getElementById('hamburgerOverlay').addEventListener('click', () => {
            document.getElementById('hamburgerPanel').classList.remove('open');
            document.getElementById('hamburgerOverlay').classList.remove('show');
        });

        // ====== مدیریت کلیک روی حساب کاربری (برای کاربران مهمان) ======
        const hamburgerProfile = document.getElementById('hamburgerProfile');
        if (hamburgerProfile) {
            hamburgerProfile.addEventListener('click', function() {
                document.getElementById('hamburgerPanel').classList.remove('open');
                document.getElementById('hamburgerOverlay').classList.remove('show');
                openLoginModal();
            });
        }

        // ====== مدیریت خروج از حساب ======
        const hamburgerLogout = document.getElementById('hamburgerLogout');
        if (hamburgerLogout) {
            hamburgerLogout.addEventListener('click', async function() {
                if (!confirm('آیا از خروج از حساب مطمئنید؟')) return;
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'logout');
                    
                    const response = await fetch('/cafe_va/api/auth.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showToast('👋 خروج با موفقیت انجام شد');
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        alert('❌ ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('خطا در ارتباط با سرور');
                }
            });
        }

        // ==========================================
        // مدیریت مودال لاگین
        // ==========================================
        function openLoginModal() {
            document.getElementById('loginModal').classList.add('active');
            document.getElementById('loginModalTitle').textContent = 'ورود به حساب';
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('loginForm').reset();
        }

        function closeLoginModal() {
            document.getElementById('loginModal').classList.remove('active');
        }

        function switchToSignup() {
            document.getElementById('loginModal').classList.remove('active');
            document.getElementById('signupModal').classList.add('active');
            document.getElementById('signupForm').reset();
        }

        function switchToLogin() {
            document.getElementById('signupModal').classList.remove('active');
            document.getElementById('loginModal').classList.add('active');
            document.getElementById('loginForm').reset();
        }

        function closeSignupModal() {
            document.getElementById('signupModal').classList.remove('active');
        }

        // ==========================================
        // مدیریت ثبت‌نام
        // ==========================================
        async function handleSignup(e) {
            e.preventDefault();
            
            const name = document.getElementById('signupName').value.trim();
            const email = document.getElementById('signupEmail').value.trim();
            const password = document.getElementById('signupPassword').value;

            if (!name || !email || !password) {
                alert('❌ لطفاً همه فیلدها را پر کنید');
                return;
            }

            if (password.length < 6) {
                alert('❌ رمز عبور باید حداقل 6 کاراکتر باشد');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'signup');
                formData.append('name', name);
                formData.append('email', email);
                formData.append('password', password);

                const response = await fetch('/cafe_va/api/auth.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server error:', text);
                    throw new Error('خطای سرور (کد ' + response.status + ')');
                }

                const result = await response.json();

                if (result.success) {
                    closeSignupModal();
                    showToast('✅ ثبت‌نام با موفقیت انجام شد! خوش آمدید ' + result.user.name);
                    
                    setTimeout(() => {
                        location.reload();
                    }, 800);
                } else {
                    alert('❌ ' + (result.message || 'خطا در ثبت‌نام'));
                }
            } catch (error) {
                console.error('Error in signup:', error);
                alert('❌ خطا در ارتباط با سرور: ' + error.message);
            }
        }

        // ==========================================
        // مدیریت ورود
        // ==========================================
        async function handleLogin(e) {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;

            if (!email || !password) {
                alert('❌ لطفاً ایمیل و رمز عبور را وارد کنید');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'login');
                formData.append('email', email);
                formData.append('password', password);

                const response = await fetch('/cafe_va/api/auth.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server error:', text);
                    throw new Error('خطای سرور (کد ' + response.status + ')');
                }

                const result = await response.json();

                if (result.success) {
                    closeLoginModal();
                    showToast('👋 خوش آمدید ' + result.user.name);
                    
                    setTimeout(() => {
                        location.reload();
                    }, 800);
                } else {
                    alert('❌ ' + (result.message || 'خطا در ورود'));
                }
            } catch (error) {
                console.error('Error in login:', error);
                alert('❌ خطا در ارتباط با سرور: ' + error.message);
            }
        }

        // ==========================================
        // مدیریت پنل ویژه (mobin)
        // ==========================================
        function openSpecialAdmin() {
            document.getElementById('specialAdminOverlay').classList.add('active');
        }

        function closeSpecialAdmin() {
            document.getElementById('specialAdminOverlay').classList.remove('active');
        }

        // ==========================================
        // مدیریت محصولات (CRUD)
        // ==========================================

        function addNewProduct() {
            document.getElementById('productModalTitle').textContent = '➕ افزودن محصول جدید';
            document.getElementById('editProductId').value = '';
            document.getElementById('productName').value = '';
            document.getElementById('productPrice').value = '';
            document.getElementById('productCategory').value = 'بارگرم';
            document.getElementById('productImage').value = '';
            document.getElementById('productModal').classList.add('active');
        }

        async function editProduct(id) {
            try {
                const response = await fetch('/cafe_va/api/products.php?action=get&id=' + id);
                
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server error response:', text);
                    throw new Error('خطای سرور (کد ' + response.status + ')');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    const product = result.product;
                    document.getElementById('productModalTitle').textContent = '✏️ ویرایش محصول';
                    document.getElementById('editProductId').value = product.id || '';
                    document.getElementById('productName').value = product.name || '';
                    document.getElementById('productPrice').value = product.price || '';
                    document.getElementById('productCategory').value = product.category || 'بارگرم';
                    document.getElementById('productModal').classList.add('active');
                } else {
                    alert('❌ ' + (result.message || 'خطا در دریافت اطلاعات محصول'));
                }
            } catch (error) {
                console.error('Error in editProduct:', error);
                alert('❌ خطا در دریافت اطلاعات محصول: ' + error.message);
            }
        }

        async function deleteProduct(id) {
            if (!confirm('آیا از حذف این محصول مطمئنید؟')) return;
            
            try {
                const response = await fetch('/cafe_va/api/products.php?action=delete&id=' + id);
                
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server error response:', text);
                    throw new Error('خطای سرور (کد ' + response.status + ')');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + (result.message || 'محصول حذف شد'));
                    location.reload();
                } else {
                    alert('❌ ' + (result.message || 'خطا در حذف محصول'));
                }
            } catch (error) {
                console.error('Error in deleteProduct:', error);
                alert('❌ خطا در حذف محصول: ' + error.message);
            }
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.remove('active');
        }

        // ====== ارسال فرم محصول ======
        document.getElementById('productForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('editProductId').value;
            const name = document.getElementById('productName').value.trim();
            const price = document.getElementById('productPrice').value.trim();
            const category = document.getElementById('productCategory').value;
            const imageInput = document.getElementById('productImage');
            
            if (!name) {
                alert('❌ لطفاً نام محصول را وارد کنید');
                document.getElementById('productName').focus();
                return;
            }
            
            if (!price || isNaN(price) || parseInt(price) <= 0) {
                alert('❌ لطفاً قیمت معتبر وارد کنید');
                document.getElementById('productPrice').focus();
                return;
            }
            
            const formData = new FormData();
            formData.append('name', name);
            formData.append('price', price);
            formData.append('category', category);
            
            if (id) {
                formData.append('id', id);
            }
            
            if (imageInput.files && imageInput.files[0]) {
                formData.append('image', imageInput.files[0]);
            }
            
            try {
                const action = id ? 'edit' : 'add';
                const response = await fetch('/cafe_va/api/products.php?action=' + action, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server error response:', text);
                    throw new Error('خطای سرور (کد ' + response.status + ')');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    closeProductModal();
                    location.reload();
                } else {
                    alert('❌ ' + (result.message || 'خطا در ذخیره محصول'));
                }
            } catch (error) {
                console.error('Error in product form submission:', error);
                alert('❌ خطا در ارتباط با سرور: ' + error.message);
            }
        });

        // ==========================================
        // مدیریت سبد خرید
        // ==========================================
        let cart = [];
        const cartBadge = document.getElementById('cartBadge');
        const cartPanel = document.getElementById('cartPanel');
        const cartOverlay = document.getElementById('cartOverlay');
        const cartItemsContainer = document.getElementById('cartItemsContainer');
        const totalPriceSpan = document.getElementById('totalPrice');

        window.addToCart = function(id, name, price) {
            const exist = cart.find(i => i.id === id);
            if (exist) {
                exist.quantity++;
            } else {
                cart.push({ id, name, price, quantity: 1 });
            }
            updateCartUI();
            
            const circle = document.querySelector('.cart-circle');
            circle.style.animation = 'none';
            circle.offsetHeight;
            circle.style.animation = 'bounceIn 0.4s ease';
        };

        function updateCartUI() {
            const totalItems = cart.reduce((s, i) => s + i.quantity, 0);
            cartBadge.textContent = totalItems;
            cartBadge.style.display = totalItems ? 'flex' : 'none';

            if (cart.length === 0) {
                cartItemsContainer.innerHTML = '<div class="cart-empty">سبد خرید خالی است</div>';
            } else {
                cartItemsContainer.innerHTML = cart.map(item => `
                    <div class="cart-row">
                        <div><strong>${item.name}</strong><br><small>${item.price.toLocaleString()} تومان</small></div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <button onclick="changeQuantity('${item.id}', -1)" style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:50%;width:30px;height:30px;cursor:pointer;">-</button>
                            <span>${item.quantity}</span>
                            <button onclick="changeQuantity('${item.id}', 1)" style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:50%;width:30px;height:30px;cursor:pointer;">+</button>
                            <button onclick="removeFromCart('${item.id}')" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:1.2rem;">&#128465;</button>
                        </div>
                    </div>
                `).join('');
            }

            const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);
            totalPriceSpan.textContent = total.toLocaleString();
        }

        window.changeQuantity = function(id, delta) {
            const item = cart.find(i => i.id === id);
            if (item) {
                item.quantity += delta;
                if (item.quantity <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
                updateCartUI();
            }
        };

        window.removeFromCart = function(id) {
            cart = cart.filter(i => i.id !== id);
            updateCartUI();
        };

        document.getElementById('openCartBtn').addEventListener('click', () => {
            cartPanel.classList.add('open');
            cartOverlay.classList.add('show');
        });

        document.getElementById('closeCartBtn').addEventListener('click', () => {
            cartPanel.classList.remove('open');
            cartOverlay.classList.remove('show');
        });

        cartOverlay.addEventListener('click', () => {
            cartPanel.classList.remove('open');
            cartOverlay.classList.remove('show');
        });

        // ==========================================
        // مدیریت تم
        // ==========================================
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark');
            document.getElementById('themeIcon').innerHTML = document.body.classList.contains('dark') ? '&#9790;' : '&#9788;';
            localStorage.setItem('cafeTheme', document.body.classList.contains('dark') ? 'dark' : 'light');
        });

        if (localStorage.getItem('cafeTheme') === 'dark') {
            document.body.classList.add('dark');
            document.getElementById('themeIcon').innerHTML = '&#9790;';
        }

        // ==========================================
        // مدیریت دسته‌بندی
        // ==========================================
        document.querySelectorAll('.category-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                document.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                const category = this.dataset.category;
                const items = document.querySelectorAll('.menu-item');
                items.forEach(item => {
                    const tag = item.querySelector('.item-category-tag');
                    if (category === 'all') {
                        item.style.display = '';
                    } else {
                        item.style.display = tag && tag.textContent === category ? '' : 'none';
                    }
                });
            });
        });

        // بستن مودال‌ها با کلیک روی پس‌زمینه
        document.querySelectorAll('.modal-overlay, .special-admin-overlay').forEach(el => {
            el.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>

</body>
</html>