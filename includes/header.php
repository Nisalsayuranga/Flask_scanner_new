<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flask Scanner Pro - Rupasinghe Trust</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Light Mode Variables */
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #3730a3;
            --bg-body: #f1f5f9;
            --bg-sidebar: #0f172a;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --text-white: #ffffff;
            --border: #e2e8f0;
            --accent: #0ea5e9;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --sidebar-text: #94a3b8;
            --sidebar-active: #ffffff;
            --sidebar-hover: rgba(255, 255, 255, 0.05);
        }

        /* Dark Mode Overrides */
        body.dark-mode {
            --bg-body: #0b0f1a;
            --bg-card: #161c2d;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Sidebar - Fixed professional look */
        .sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            z-index: 100;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            margin-bottom: 2rem;
        }

        .logo i { color: var(--accent); font-size: 1.5rem; }
        .logo span { color: white; font-size: 1.25rem; font-weight: 700; letter-spacing: 1px; }

        .nav-links { list-style: none; display: flex; flex-direction: column; gap: 0.25rem; }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.8rem 1rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-item a:hover { background: var(--sidebar-hover); color: white; }

        .nav-item.active a {
            background: rgba(79, 70, 229, 0.15);
            color: white;
            font-weight: 600;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
        }

        /* Theme Toggle */
        .theme-switch-wrapper {
            margin-top: 1rem;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .theme-switch-wrapper span { font-size: 0.8rem; color: #94a3b8; font-weight: 600; }

        .theme-toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .theme-toggle-btn:hover { background: var(--primary); }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 2rem 3rem;
            max-width: calc(100vw - 260px);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .welcome h1 { font-size: 1.75rem; font-weight: 700; color: var(--text-main); letter-spacing: -0.02em; }
        .welcome p { color: var(--text-muted); font-size: 0.95rem; }

        /* Global Form Styles - Professional Look */
        input[type="text"], 
        input[type="number"], 
        input[type="date"], 
        input[type="email"], 
        select, 
        textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: var(--bg-card);
            border: 1px solid #cbd5e1; /* Darker border for better visibility */
            border-radius: 10px;
            color: var(--text-main);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background-color: var(--bg-card);
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 1.25rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .stat-header { display: flex; justify-content: space-between; align-items: center; }
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .stat-info h3 { font-size: 1.75rem; font-weight: 700; color: var(--text-main); }
        .stat-info p { color: var(--text-muted); font-size: 0.85rem; font-weight: 500; }

        .btn { padding: 0.6rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }

        .table-container { 
            overflow-x: auto; 
            border-radius: 10px; 
            border: 1px solid var(--border);
            width: 100%;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: var(--bg-card);
            table-layout: auto;
        }
        th { 
            padding: 1rem; 
            background: rgba(0,0,0,0.02); 
            color: var(--text-muted); 
            font-weight: 600; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            border-bottom: 1px solid var(--border);
            text-align: left; /* Strict alignment */
        }
        td { 
            padding: 1.25rem 1rem; 
            border-bottom: 1px solid var(--border); 
            font-size: 0.9rem; 
            color: var(--text-main);
            text-align: left; /* Strict alignment */
            vertical-align: middle;
        }
        tr:hover td { background: rgba(0,0,0,0.01); }

        .badge { padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }

        body.dark-mode .badge-success { background: rgba(21, 128, 61, 0.2); }
        body.dark-mode .badge-warning { background: rgba(180, 83, 9, 0.2); }
        body.dark-mode .badge-danger { background: rgba(185, 28, 28, 0.2); }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-main);
            cursor: pointer;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px;
            }
            .sidebar.active {
                transform: translateX(0);
                box-shadow: 10px 0 20px rgba(0,0,0,0.5);
            }
            .main-content {
                margin-left: 0;
                max-width: 100vw;
                padding: 1rem;
            }
            .mobile-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .welcome h1 { font-size: 1.4rem; }
            .actions { width: 100%; display: flex; flex-direction: column; gap: 0.5rem; }
            .btn { width: 100%; justify-content: center; }
            
            /* Overriding inline grid styles for mobile */
            div[style*="grid-template-columns: repeat(3, 1fr)"],
            div[style*="grid-template-columns: repeat(2, 1fr)"] {
                grid-template-columns: 1fr !important;
            }
            div[style*="grid-column: span 3;"],
            div[style*="grid-column: span 2;"] {
                grid-column: span 1 !important;
            }
            
            embed { height: 400px !important; }
            
            /* Overlay for sidebar */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 90;
                backdrop-filter: blur(3px);
            }
            .sidebar-overlay.active {
                display: block;
            }
        }
    </style>
    
    <script>
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            document.documentElement.classList.add('dark-mode');
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('dark-mode');
                const icon = document.querySelector('.theme-toggle-btn i');
                if(icon) icon.classList.replace('fa-moon', 'fa-sun');
            });
        }

        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            let theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
            localStorage.setItem('theme', theme);
            const icon = document.querySelector('.theme-toggle-btn i');
            if (theme === 'dark') icon.classList.replace('fa-moon', 'fa-sun');
            else icon.classList.replace('fa-sun', 'fa-moon');
        }

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.sidebar-overlay').classList.toggle('active');
        }
    </script>
</head>
<body>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-bolt"></i>
            <span>FLASK SCANNER</span>
        </div>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            </li>
            <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'records.php' && ($_GET['status'] ?? '') == 'pending') ? 'active' : ''; ?>">
                <a href="records.php?status=pending"><i class="fas fa-clock"></i> Pending Records</a>
            </li>
            <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'records.php' && ($_GET['status'] ?? '') == 'completed') ? 'active' : ''; ?>">
                <a href="records.php?status=completed"><i class="fas fa-check-circle"></i> Completed Records</a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'scan.php' ? 'active' : ''; ?>">
                <a href="scan.php"><i class="fas fa-expand"></i> Single Scan</a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'batch_scan.php' ? 'active' : ''; ?>">
                <a href="batch_scan.php"><i class="fas fa-layer-group"></i> Batch Scan</a>
            </li>
            <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'records.php' && !isset($_GET['status'])) ? 'active' : ''; ?>">
                <a href="records.php"><i class="fas fa-database"></i> All Records</a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            </li>
        </ul>
        
        <div style="margin-top: auto; padding-top: 1rem;">
            <!-- Theme Toggle -->
            <div class="theme-switch-wrapper">
                <span>Theme</span>
                <button class="theme-toggle-btn" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(255, 255, 255, 0.04); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05);">
                <p style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.5rem; font-weight: 600;">API STATUS</p>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                    <span style="font-size: 0.85rem; color: white; font-weight: 500;">
                        <?php 
                        if (!isset($pdo)) {
                            require_once __DIR__ . '/db.php';
                        }
                        $key_count = $pdo->query("SELECT COUNT(*) FROM api_usage")->fetchColumn();
                        echo $key_count . " Keys Active"; 
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <button class="mobile-menu-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars" style="margin-right: 8px;"></i> Menu
        </button>
