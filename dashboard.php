<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
include 'db/config.php';

// Fetch user data
$user_id = $_SESSION["user_id"];
$user_result = $conn->query("SELECT name, email FROM users WHERE id='$user_id'");
$user_data = $user_result->fetch_assoc();

// Fetch financial summary
$total_expense = 0;
$total_income = 0;
$balance = 0;

$expense_result = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE user_id='$user_id'");
if ($expense_result && $row = $expense_result->fetch_assoc()) {
    $total_expense = $row['total'] ? $row['total'] : 0;
}

$income_result = $conn->query("SELECT SUM(amount) as total FROM income WHERE user_id='$user_id'");
if ($income_result && $row = $income_result->fetch_assoc()) {
    $total_income = $row['total'] ? $row['total'] : 0;
}

$balance = $total_income - $total_expense;

// Fetch recent transactions
$recent_transactions = $conn->query("
    (SELECT 'expense' as type, category as category_source, amount, date, description 
     FROM expenses 
     WHERE user_id='$user_id')
    UNION
    (SELECT 'income' as type, source as category_source, amount, date, description 
     FROM income 
     WHERE user_id='$user_id')
    ORDER BY date DESC
    LIMIT 5
");

// Get current month's spending
$current_month = date('m');
$current_year = date('Y');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$month_expense = 0;
$month_expense_result = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE user_id='$user_id' AND date BETWEEN '$month_start' AND '$month_end'");
if ($month_expense_result && $row = $month_expense_result->fetch_assoc()) {
    $month_expense = $row['total'] ? $row['total'] : 0;
}

$month_income = 0;
$month_income_result = $conn->query("SELECT SUM(amount) as total FROM income WHERE user_id='$user_id' AND date BETWEEN '$month_start' AND '$month_end'");
if ($month_income_result && $row = $month_income_result->fetch_assoc()) {
    $month_income = $row['total'] ? $row['total'] : 0;
}

// Get user's first name for greeting
$first_name = explode(' ', $user_data['name'])[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Expense Tracker</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #4b6cb7 0%, #182848 100%);
            color: #fff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 5px 0 15px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .app-logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .app-logo i {
            font-size: 2rem;
            background: linear-gradient(45deg, #fff, #e3f2fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .user-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
        }

        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            margin-bottom: 15px;
            border: 3px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }

        .profile-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
            border-radius: 50%;
        }

        .user-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-email {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 20px;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .menu-title {
            padding: 10px 25px;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-item {
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: #fff;
        }

        .nav-item i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .logout-section {
            padding: 20px 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 12px 0;
        }

        .logout-btn:hover {
            color: #ff6b6b;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .greeting {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
        }

        .greeting span {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .date-display {
            color: #666;
            font-size: 1.1rem;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
        }

        .action-btn {
            padding: 10px 20px;
            background: #fff;
            border: none;
            border-radius: 50px;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .action-btn.primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: #fff;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 1.1rem;
            color: #666;
            font-weight: 600;
        }

        .card-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .card-icon.income {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .card-icon.expense {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .card-icon.balance {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .card-value.income {
            color: #10b981;
        }

        .card-value.expense {
            color: #ef4444;
        }

        .card-value.balance {
            color: #3b82f6;
        }

        .card-subtitle {
            font-size: 0.9rem;
            color: #888;
        }

        .dashboard-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .recent-transactions {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
        }

        .view-all {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .view-all:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .transaction-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .transaction-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 12px;
            background: #f9fafc;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .transaction-item:hover {
            background: #f0f4ff;
            transform: translateX(5px);
        }

        .transaction-item.expense {
            border-left-color: #ef4444;
        }

        .transaction-item.income {
            border-left-color: #10b981;
        }

        .transaction-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 15px;
        }

        .transaction-icon.expense {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .transaction-icon.income {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .transaction-details {
            flex: 1;
        }

        .transaction-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .transaction-date {
            font-size: 0.85rem;
            color: #888;
        }

        .transaction-amount {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .transaction-amount.expense {
            color: #ef4444;
        }

        .transaction-amount.income {
            color: #10b981;
        }

        .monthly-summary {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .summary-chart {
            margin-top: 20px;
            height: 200px;
        }

        .progress-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .progress-bar {
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 1s ease;
        }

        .progress-fill.income {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .progress-fill.expense {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .stat-card {
            background: #f9fafc;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-value.positive {
            color: #10b981;
        }

        .stat-value.negative {
            color: #ef4444;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #666;
        }

        /* Floating background elements */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .floating-shape {
            position: absolute;
            background: rgba(102, 126, 234, 0.03);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        .floating-shape:nth-child(1) {
            width: 300px;
            height: 300px;
            top: 10%;
            right: 10%;
            animation-delay: 0s;
        }

        .floating-shape:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: 10%;
            right: 20%;
            animation-delay: 3s;
        }

        .floating-shape:nth-child(3) {
            width: 150px;
            height: 150px;
            bottom: 30%;
            left: 30%;
            animation-delay: 6s;
        }

        /* Mobile toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: #fff;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: #667eea;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .dashboard-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .quick-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .greeting {
                font-size: 1.5rem;
            }
            
            .main-content {
                padding: 20px;
            }
        }

        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>

    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="app-logo">
                <i class="fas fa-wallet"></i>
                <span>Finance Tracker</span>
            </div>
            <div class="user-profile">
                <div class="profile-image">
                    <?= substr($user_data['name'], 0, 1) ?>
                </div>
                <div class="user-name"><?= htmlspecialchars($user_data['name']) ?></div>
                <div class="user-email"><?= htmlspecialchars($user_data['email']) ?></div>
            </div>
        </div>

        <div class="nav-menu">
            <div class="menu-title">MAIN MENU</div>
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="add_expense.php" class="nav-item">
                <i class="fas fa-minus-circle"></i>
                <span>Add Expense</span>
            </a>
            <a href="add_income.php" class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <span>Add Income</span>
            </a>
            <a href="reports.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>

            <div class="menu-title">MANAGEMENT</div>
            <a href="budget.php" class="nav-item">
                <i class="fas fa-bullseye"></i>
                <span>Budget Manager</span>
            </a>
            <a href="savings.php" class="nav-item">
                <i class="fas fa-piggy-bank"></i>
                <span>Savings Goals</span>
            </a>
        </div>

        <div class="logout-section">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div>
                <h1 class="greeting">Hello, <span><?= htmlspecialchars($first_name) ?></span>!</h1>
                <div class="date-display"><?= date('l, F j, Y') ?></div>
            </div>
            <div class="quick-actions">
                <a href="add_expense.php" class="action-btn">
                    <i class="fas fa-minus-circle"></i>
                    Expense
                </a>
                <a href="add_income.php" class="action-btn primary">
                    <i class="fas fa-plus-circle"></i>
                    Income
                </a>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-title">Total Income</div>
                    <div class="card-icon income">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="card-value income">RWF<?= number_format($total_income, 2) ?></div>
                <div class="card-subtitle">All time earnings</div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-title">Total Expenses</div>
                    <div class="card-icon expense">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
                <div class="card-value expense">RWF<?= number_format($total_expense, 2) ?></div>
                <div class="card-subtitle">All time spending</div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-title">Current Balance</div>
                    <div class="card-icon balance">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                </div>
                <div class="card-value balance">RWF<?= number_format($balance, 2) ?></div>
                <div class="card-subtitle">Available funds</div>
            </div>
        </div>

        <div class="dashboard-row">
            <div class="recent-transactions">
                <div class="section-header">
                    <div class="section-title">Recent Transactions</div>
                    <a href="reports.php" class="view-all">View All</a>
                </div>
                <div class="transaction-list">
                    <?php if ($recent_transactions && $recent_transactions->num_rows > 0): ?>
                        <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                            <div class="transaction-item <?= $transaction['type'] ?>">
                                <div class="transaction-icon <?= $transaction['type'] ?>">
                                    <?php if ($transaction['type'] == 'expense'): ?>
                                        <i class="fas fa-arrow-down"></i>
                                    <?php else: ?>
                                        <i class="fas fa-arrow-up"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="transaction-details">
                                    <div class="transaction-title"><?= htmlspecialchars($transaction['category_source']) ?></div>
                                    <div class="transaction-date"><?= date('M j, Y', strtotime($transaction['date'])) ?></div>
                                </div>
                                <div class="transaction-amount <?= $transaction['type'] ?>">
                                    <?php if ($transaction['type'] == 'expense'): ?>
                                        -RWF<?= number_format($transaction['amount'], 2) ?>
                                    <?php else: ?>
                                        +RWF<?= number_format($transaction['amount'], 2) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: #888;">
                            <i class="fas fa-receipt" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>No transactions yet. Start by adding your first expense or income.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="monthly-summary">
                <div class="section-header">
                    <div class="section-title">Monthly Summary</div>
                    <div style="font-size: 0.9rem; color: #888;"><?= date('F Y') ?></div>
                </div>
                
                <div class="progress-title">
                    <span>Income</span>
                    <span>RWF<?= number_format($month_income, 2) ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill income" style="width: 100%"></div>
                </div>
                
                <div class="progress-title">
                    <span>Expenses</span>
                    <span>RWF<?= number_format($month_expense, 2) ?></span>
                </div>
                <div class="progress-bar">
                    <?php 
                    $percentage = ($month_income > 0) ? min(100, ($month_expense / $month_income) * 100) : 0;
                    ?>
                    <div class="progress-fill expense" style="width: <?= $percentage ?>%"></div>
                </div>
                
                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-value <?= ($month_income - $month_expense >= 0) ? 'positive' : 'negative' ?>">
                            RWF<?= number_format($month_income - $month_expense, 2) ?>
                        </div>
                        <div class="stat-label">Monthly Balance</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php 
                            $saving_rate = ($month_income > 0) ? (($month_income - $month_expense) / $month_income) * 100 : 0;
                            echo number_format($saving_rate, 1) . '%';
                            ?>
                        </div>
                        <div class="stat-label">Saving Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const mobileToggle = document.getElementById('mobileToggle');
            const sidebar = document.getElementById('sidebar');
            
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && 
                    !sidebar.contains(e.target) && 
                    e.target !== mobileToggle) {
                    sidebar.classList.remove('active');
                }
            });
            
            // Animate elements on scroll with Intersection Observer
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe dashboard cards and transaction items
            document.querySelectorAll('.dashboard-card, .transaction-item, .monthly-summary').forEach(item => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                item.style.transition = 'all 0.5s ease';
                observer.observe(item);
            });
            
            // Add animation delay to transaction items
            document.querySelectorAll('.transaction-item').forEach((item, index) => {
                item.style.transitionDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>