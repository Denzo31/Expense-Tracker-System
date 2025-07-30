<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
include 'db/config.php';
$user_id = $_SESSION["user_id"];

$message = "";
$success = false;

// Handle budget creation/update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $conn->real_escape_string($_POST["category"]);
    $amount = floatval($_POST["amount"]);
    $month = intval($_POST["month"]);
    $year = intval($_POST["year"]);
    
    if ($amount > 0 && $category && $month && $year) {
        // Check if budget already exists for this category/month/year
        $check_sql = "SELECT id FROM budgets WHERE user_id='$user_id' AND category='$category' AND month='$month' AND year='$year'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update existing budget
            $budget_id = $check_result->fetch_assoc()['id'];
            $sql = "UPDATE budgets SET amount='$amount' WHERE id='$budget_id'";
            if ($conn->query($sql)) {
                $message = "🎯 Budget updated successfully!";
                $success = true;
            } else {
                $message = "Error updating budget. Please try again.";
                $success = false;
            }
        } else {
            // Create new budget
            $sql = "INSERT INTO budgets (user_id, category, amount, month, year) VALUES ('$user_id', '$category', '$amount', '$month', '$year')";
            if ($conn->query($sql)) {
                $message = "🎯 Budget set successfully!";
                $success = true;
            } else {
                $message = "Error creating budget. Please try again.";
                $success = false;
            }
        }
    } else {
        $message = "Please fill in all required fields.";
        $success = false;
    }
}

// Get current month and year
$current_month = date('n');
$current_year = date('Y');

// Fetch categories from database
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");

// Fetch existing budgets for current month/year
$budgets_result = $conn->query("SELECT * FROM budgets WHERE user_id='$user_id' AND year='$current_year'");
$budgets = [];
if ($budgets_result) {
    while ($row = $budgets_result->fetch_assoc()) {
        $budgets[$row['category']] = $row;
    }
}

// Calculate spending for current month by category
$spending_result = $conn->query("SELECT category, SUM(amount) as total FROM expenses 
                                WHERE user_id='$user_id' AND MONTH(date)='$current_month' AND YEAR(date)='$current_year' 
                                GROUP BY category");
$spending = [];
if ($spending_result) {
    while ($row = $spending_result->fetch_assoc()) {
        $spending[$row['category']] = $row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Manager | Expense Tracker</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.25);
            padding: 40px;
            animation: slideInUp 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #4facfe);
            animation: shimmer 3s ease-in-out infinite;
        }

        h1 {
            text-align: center;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .subtitle {
            text-align: center;
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 40px;
        }

        .budget-form {
            background: #f8fafc;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .form-title {
            font-size: 1.4rem;
            color: #4a5568;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
            max-width: 200px;
            margin: 0 auto;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .message {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: 500;
        }

        .message.success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .budget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .budget-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            position: relative;
        }

        .budget-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .budget-category {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .category-icon {
            font-size: 1.8rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .category-name {
            font-weight: 600;
            color: #4a5568;
            font-size: 1.2rem;
        }

        .budget-amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4a5568;
            margin-bottom: 5px;
        }

        .budget-spent {
            color: #718096;
            margin-bottom: 15px;
        }

        .progress-bar {
            height: 10px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            color: #718096;
            font-size: 0.9rem;
        }

        .actions {
            display: flex;
            justify-content: center;
            margin-top: 40px;
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
            margin: 0 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #f8fafc;
            color: #667eea;
            border: 2px solid #e2e8f0;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: #f8fafc;
            border-radius: 16px;
            margin-top: 30px;
        }

        .empty-icon {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 15px;
        }

        .empty-title {
            font-size: 1.3rem;
            color: #4a5568;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .empty-message {
            color: #718096;
            margin-bottom: 20px;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
                margin: 20px 10px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .budget-form {
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .budget-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-bullseye" style="margin-right: 15px;"></i>Budget Manager</h1>
        <p class="subtitle">Set and track your spending limits for better financial control</p>
        
        <?php if($message): ?>
            <div class="message <?= $success ? 'success' : 'error' ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="budget-form">
            <h2 class="form-title"><i class="fas fa-plus-circle"></i> Create or Update Budget</h2>
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" required>
                            <option value="">Select Category</option>
                            <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                                <?php while($cat = $categories_result->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($cat['name']) ?>"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="Food & Dining">🍕 Food & Dining</option>
                                <option value="Transport">🚗 Transport</option>
                                <option value="Shopping">🛒 Shopping</option>
                                <option value="Bills & Utilities">📄 Bills & Utilities</option>
                                <option value="Health & Care">⚕️ Health & Care</option>
                                <option value="Entertainment">🎬 Entertainment</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Budget Amount</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="0" required placeholder="Enter amount">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="month">Month</label>
                        <select name="month" id="month" required>
                            <option value="1" <?= $current_month == 1 ? 'selected' : '' ?>>January</option>
                            <option value="2" <?= $current_month == 2 ? 'selected' : '' ?>>February</option>
                            <option value="3" <?= $current_month == 3 ? 'selected' : '' ?>>March</option>
                            <option value="4" <?= $current_month == 4 ? 'selected' : '' ?>>April</option>
                            <option value="5" <?= $current_month == 5 ? 'selected' : '' ?>>May</option>
                            <option value="6" <?= $current_month == 6 ? 'selected' : '' ?>>June</option>
                            <option value="7" <?= $current_month == 7 ? 'selected' : '' ?>>July</option>
                            <option value="8" <?= $current_month == 8 ? 'selected' : '' ?>>August</option>
                            <option value="9" <?= $current_month == 9 ? 'selected' : '' ?>>September</option>
                            <option value="10" <?= $current_month == 10 ? 'selected' : '' ?>>October</option>
                            <option value="11" <?= $current_month == 11 ? 'selected' : '' ?>>November</option>
                            <option value="12" <?= $current_month == 12 ? 'selected' : '' ?>>December</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <select name="year" id="year" required>
                            <?php for($y = $current_year - 1; $y <= $current_year + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $current_year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <button type="submit">Save Budget</button>
            </form>
        </div>
        
        <h2 class="form-title"><i class="fas fa-chart-pie"></i> Your Current Budgets</h2>
        
        <?php if (count($budgets) > 0): ?>
            <div class="budget-grid">
                <?php 
                // Reset the categories result pointer
                $categories_result->data_seek(0);
                
                // Create a map of category icons
                $category_icons = [];
                $category_colors = [];
                if ($categories_result && $categories_result->num_rows > 0) {
                    while($cat = $categories_result->fetch_assoc()) {
                        $category_icons[$cat['name']] = $cat['icon'];
                        $category_colors[$cat['name']] = $cat['color'];
                    }
                } else {
                    // Default icons if categories table is empty
                    $category_icons = [
                        'Food & Dining' => '🍕',
                        'Transport' => '🚗',
                        'Shopping' => '🛒',
                        'Bills & Utilities' => '📄',
                        'Health & Care' => '⚕️',
                        'Entertainment' => '🎬'
                    ];
                    $category_colors = [
                        'Food & Dining' => '#FF5733',
                        'Transport' => '#33A8FF',
                        'Shopping' => '#33FF57',
                        'Bills & Utilities' => '#FF33A8',
                        'Health & Care' => '#A833FF',
                        'Entertainment' => '#FFBD33'
                    ];
                }
                
                foreach($budgets as $category => $budget): 
                    $spent = isset($spending[$category]) ? $spending[$category] : 0;
                    $percentage = $budget['amount'] > 0 ? ($spent / $budget['amount']) * 100 : 0;
                    $percentage = min(100, $percentage); // Cap at 100%
                    
                    $icon = isset($category_icons[$category]) ? $category_icons[$category] : '📊';
                    $color = isset($category_colors[$category]) ? $category_colors[$category] : '#667eea';
                    
                    $status_color = $percentage < 70 ? '#10b981' : ($percentage < 90 ? '#f59e0b' : '#ef4444');
                ?>
                    <div class="budget-card">
                        <div class="budget-category">
                            <div class="category-icon" style="background-color: <?= $color ?>20; color: <?= $color ?>;">
                                <?= $icon ?>
                            </div>
                            <div class="category-name"><?= htmlspecialchars($category) ?></div>
                        </div>
                        <div class="budget-amount"> <?= number_format($budget['amount'], 2) ?></div>
                        <div class="budget-spent">Spent:  <?= number_format($spent, 2) ?></div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $percentage ?>%; background-color: <?= $status_color ?>;"></div>
                        </div>
                        <div class="progress-text">
                            <div><?= round($percentage) ?>% used</div>
                            <div> <?= number_format($budget['amount'] - $spent, 2) ?> left</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-piggy-bank"></i></div>
                <div class="empty-title">No budgets set yet</div>
                <div class="empty-message">Create your first budget above to start tracking your spending limits</div>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="reports.php" class="btn btn-secondary">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-fill form when editing an existing budget
            const categorySelect = document.getElementById('category');
            categorySelect.addEventListener('change', function() {
                const selectedCategory = this.value;
                const budgets = <?= json_encode($budgets) ?>;
                
                if (budgets[selectedCategory]) {
                    document.getElementById('amount').value = budgets[selectedCategory].amount;
                } else {
                    document.getElementById('amount').value = '';
                }
            });
            
            // Animate progress bars on load
            const progressBars = document.querySelectorAll('.progress-fill');
            setTimeout(() => {
                progressBars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 100);
                });
            }, 300);
        });
    </script>
</body>
</html> 