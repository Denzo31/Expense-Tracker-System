<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
include 'db/config.php';
$user_id = $_SESSION["user_id"];

// Fetch totals
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
$recent_expenses = $conn->query("SELECT category, amount, description, date FROM expenses WHERE user_id='$user_id' ORDER BY date DESC LIMIT 5");
$recent_income = $conn->query("SELECT source, amount, description, date FROM income WHERE user_id='$user_id' ORDER BY date DESC LIMIT 5");

// Get user's name if available
$user_result = $conn->query("SELECT name FROM users WHERE id='$user_id'");
$user_name = "there";
if ($user_result && $user_row = $user_result->fetch_assoc()) {
    $user_name = $user_row['name'] ? $user_row['name'] : "there";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Financial Journey | Expense Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .reports-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .reports-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .welcome-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .welcome-title {
            color: #4a5568;
            font-size: 2.2rem;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .welcome-subtitle {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 0;
            font-weight: 400;
        }
        
        .motivational-message {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
            position: relative;
        }
        
        .motivational-text {
            margin: 0;
            color: #4a5568;
            font-size: 1rem;
            line-height: 1.5;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--card-color);
        }
        
        .card-icon {
            font-size: 2rem;
            margin-bottom: 12px;
            color: var(--card-color);
        }
        
        .card-title {
            font-size: 1rem;
            color: #718096;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--card-color);
            margin-bottom: 4px;
        }
        
        .card-change {
            font-size: 0.85rem;
            color: #9ca3af;
        }
        
        .expense { --card-color: #ef4444; }
        .income { --card-color: #10b981; }
        .balance { --card-color: #8b5cf6; }
        
        .section {
            margin-bottom: 35px;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-icon {
            font-size: 1.3rem;
            margin-right: 10px;
            color: #667eea;
        }
        
        .section-title {
            color: #4a5568;
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }
        
        .transactions-table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #f1f5f9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 15px 12px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #4a5568;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .empty-message {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }
        
        .empty-suggestion {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f8fafc;
            color: #667eea;
            border: 2px solid #e2e8f0;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: #fff;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .reports-container {
                margin: 10px;
                padding: 25px;
            }
            
            .welcome-title {
                font-size: 1.8rem;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="reports-container">
        <div class="welcome-header">
            <h1 class="welcome-title">
                <i class="fas fa-chart-pie" style="color: #667eea; margin-right: 12px;"></i>
                Hello <?= htmlspecialchars($user_name) ?>!
            </h1>
            <p class="welcome-subtitle">Here's your financial overview - you're doing great!</p>
        </div>

        <?php
        $motivational_messages = [
            "Every step towards financial awareness is a step towards your goals!",
            "Tracking your finances is the first step to financial freedom!",
            "You're building great money habits - keep it up!",
            "Knowledge is power, especially when it comes to your finances!",
            "Small steps today lead to big achievements tomorrow!"
        ];
        $random_message = $motivational_messages[array_rand($motivational_messages)];
        ?>
        
        <div class="motivational-message">
            <p class="motivational-text">
                <i class="fas fa-lightbulb" style="color: #f59e0b; margin-right: 8px;"></i>
                <?= $random_message ?>
            </p>
        </div>

        <div class="summary-cards">
            <div class="card expense">
                <div class="card-icon"><i class="fas fa-arrow-down"></i></div>
                <div class="card-title">Total Expenses</div>
                <div class="card-value">RWF <?= number_format($total_expense, 2) ?></div>
                <div class="card-change">Money spent wisely</div>
            </div>
            <div class="card income">
                <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
                <div class="card-title">Total Income</div>
                <div class="card-value">RWF <?= number_format($total_income, 2) ?></div>
                <div class="card-change">Great earning!</div>
            </div>
            <div class="card balance">
                <div class="card-icon"><i class="fas fa-wallet"></i></div>
                <div class="card-title">Current Balance</div>
                <div class="card-value">RWF <?= number_format($balance, 2) ?></div>
                <div class="card-change"><?= $balance >= 0 ? 'Looking good!' : 'Room for improvement' ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <i class="fas fa-shopping-cart section-icon"></i>
                <h3 class="section-title">Recent Expenses</h3>
            </div>
            <?php if ($recent_expenses && $recent_expenses->num_rows > 0): ?>
            <div class="transactions-table">
                <table>
                    <tr>
                        <th><i class="fas fa-tag"></i> Category</th>
                        <th><i class="fas fa-money-bill"></i> Amount</th>
                        <th><i class="fas fa-info-circle"></i> Description</th>
                        <th><i class="fas fa-calendar"></i> Date</th>
                    </tr>
                    <?php while($row = $recent_expenses->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td style="color: #ef4444; font-weight: 600;">RWF <?= number_format($row['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= date('M j, Y', strtotime($row['date'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                <div class="empty-message">No expenses recorded yet</div>
                <div class="empty-suggestion">Start tracking your expenses to see them here!</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-header">
                <i class="fas fa-piggy-bank section-icon"></i>
                <h3 class="section-title">Recent Income</h3>
            </div>
            <?php if ($recent_income && $recent_income->num_rows > 0): ?>
            <div class="transactions-table">
                <table>
                    <tr>
                        <th><i class="fas fa-building"></i> Source</th>
                        <th><i class="fas fa-money-bill"></i> Amount</th>
                        <th><i class="fas fa-info-circle"></i> Description</th>
                        <th><i class="fas fa-calendar"></i> Date</th>
                    </tr>
                    <?php while($row = $recent_income->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['source']) ?></td>
                        <td style="color: #10b981; font-weight: 600;">RWF <?= number_format($row['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= date('M j, Y', strtotime($row['date'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-coins"></i></div>
                <div class="empty-message">No income recorded yet</div>
                <div class="empty-suggestion">Add your income sources to see them here!</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
            <a href="add_expense.php" class="btn btn-secondary">
                <i class="fas fa-plus"></i>
                Add Expense
            </a>
            <a href="add_income.php" class="btn btn-secondary">
                <i class="fas fa-plus-circle"></i>
                Add Income
            </a>
        </div>
    </div>
</body>
</html>