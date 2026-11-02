<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'UR Internship Portal', ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'University of Rwanda Internship Placement Portal', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .site-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo-area {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
        }
        .ur-logo-img {
            height: 45px;
            width: auto;
            object-fit: contain;
        }
        .site-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .site-nav a:not(.btn) {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s ease;
            padding: 0.3rem 0;
            position: relative;
        }
        .site-nav a:not(.btn):hover,
        .site-nav a.active {
            color: #12356B;
        }
        .site-nav a.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #D97706;
            border-radius: 2px;
        }
        .btn-nav-primary {
            background-color: #12356B;
            color: #ffffff !important;
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .btn-nav-primary:hover {
            background-color: #1e40af;
        }
        .btn-nav-outline {
            border: 1px solid #cbd5e1;
            color: #12356B !important;
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-nav-outline:hover {
            border-color: #12356B;
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <!-- Left Corner: Official UR Logo & Title -->
            <a href="index.php" class="logo-area">
                <img src="logo.png" alt="University of Rwanda Logo" class="ur-logo-img">
                <div>
                    <strong style="display: block; font-size: 1.05rem; color: #12356B; line-height: 1.2;">University of Rwanda</strong>
                    <span style="font-size: 0.8rem; color: #64748B; letter-spacing: 0.3px;">Year 4 Internship Portal</span>
                </div>
            </a>

            <!-- Navigation Bar -->
            <nav class="site-nav">
                <a href="index.php" class="<?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">Home</a>
                <a href="apply.php" class="<?= ($currentPage ?? '') === 'apply' ? 'active' : '' ?>">Apply</a>
                <a href="status.php" class="<?= ($currentPage ?? '') === 'status' ? 'active' : '' ?>">My Application</a>

                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="btn-nav-outline">Dashboard</a>
                    <a href="logout.php" style="color: #842029 !important; font-weight: 600;">Log Out</a>
                <?php else: ?>
                    <a href="login.php" class="btn-nav-primary">Sign In</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>