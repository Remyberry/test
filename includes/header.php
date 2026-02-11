<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection (centralized)
require_once 'db_connect.php';

// Check if user is accessing a protected page without being logged in
$public_pages = ['index.php', 'register.php', 'forgot_password.php', 'reset_password.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!in_array($current_page, $public_pages) && !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Set default page title
$page_title = $page_title ?? "EPMS - City College of Angeles";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Tailwind CSS -->
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    
    <style>
        body {
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
        }
        
        .sidebar {
            background-color: #2d5d2a;
            width: 280px;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 9999;
            overflow-y: auto;
        }
        
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
            padding: 20px;
        }
        
        .sidebar .logo-container {
            text-align: center;
            padding: 1.5rem 1rem;
            color: white;
        }
        
        .sidebar .logo-container img {
            width: 60px;
            height: auto;
            margin: 0 auto 0.75rem;
            display: block;
        }
        
        .sidebar .user-info {
            text-align: center;
            margin-bottom: 0.75rem;
            color: white;
        }
        
        .sidebar .user-role {
            display: inline-block;
            margin-top: 0.25rem;
        }
        
        .sidebar .nav-link {
            color: white;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            margin: 0.25rem 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar .nav-link.active {
            background-color: white;
            color: #2d5d2a;
            font-weight: 600;
        }
        
        .sidebar hr {
            border-color: rgba(255, 255, 255, 0.25);
            opacity: 1;
            margin: 1rem 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
                margin-bottom: 20px;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            body {
                flex-direction: column;
            }
        }
        
        /* Custom colors for the project */
        .btn-primary, .bg-primary {
            background-color: #2d5d2a !important;
            border-color: #2d5d2a !important;
        }
        
        .btn-primary:hover {
            background-color: #224221 !important;
            border-color: #224221 !important;
        }
        
        .text-primary {
            color: #2d5d2a !important;
        }
        
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            border: none;
        }
        
        /* Pulse animation for new notifications */
        .pulse-button {
            position: relative;
            animation: pulse 1.5s infinite;
            font-weight: bold;
        }
        
        .pulse-icon {
            animation: pulse-icon 1.5s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        
        @keyframes pulse-icon {
            0% {
                color: inherit;
            }
            50% {
                color: #dc3545;
                transform: scale(1.2);
            }
            100% {
                color: inherit;
            }
        }
        
        /* Badge animation for new items */
        .badge-new {
            animation: badge-pulse 1.5s infinite;
        }
        
        @keyframes badge-pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
<?php if (isset($_SESSION['user_id']) && !in_array($current_page, $public_pages)): ?>
    <!-- Include sidebar for authenticated users on non-public pages -->
    <?php include('sidebar.php'); ?>
    
    <div class="main-content">
        <!-- Page content will go here -->
<?php endif; ?> 