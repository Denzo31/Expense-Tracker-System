<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
include 'db/config.php';

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $conn->real_escape_string($_POST["category"]);
    $amount = floatval($_POST["amount"]);
    $description = $conn->real_escape_string($_POST["description"]);
    $date = $conn->real_escape_string($_POST["date"]);
    $user_id = $_SESSION["user_id"];
    
    if ($amount > 0 && $category && $date) {
        $sql = "INSERT INTO expenses (user_id, category, amount, description, date) VALUES ('$user_id', '$category', '$amount', '$description', '$date')";
        if ($conn->query($sql)) {
            $message = "🎉 Expense tracked successfully! Great job managing your finances.";
            $success = true;
        } else {
            $message = "Oops! Something went wrong. Please try again.";
            $success = false;
        }
    } else {
        $message = "Please fill in all the required details to continue.";
        $success = false;
    }
}

// Fetch categories from database
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = [];
if ($categories_result && $categories_result->num_rows > 0) {
    while($cat = $categories_result->fetch_assoc()) {
        $categories[] = $cat;
    }
} else {
    // Default categories if none exist in database
    $categories = [
        ['name' => 'Food & Dining', 'icon' => '🍕', 'color' => '#FF5733'],
        ['name' => 'Transport', 'icon' => '🚗', 'color' => '#33A8FF'],
        ['name' => 'Shopping', 'icon' => '🛒', 'color' => '#33FF57'],
        ['name' => 'Bills & Utilities', 'icon' => '📄', 'color' => '#FF33A8'],
        ['name' => 'Health & Care', 'icon' => '⚕️', 'color' => '#A833FF'],
        ['name' => 'Entertainment', 'icon' => '🎬', 'color' => '#FFBD33'],
        ['name' => 'Education', 'icon' => '📚', 'color' => '#FF8C33'],
        ['name' => 'Travel', 'icon' => '✈️', 'color' => '#33FFE6'],
        ['name' => 'Savings', 'icon' => '💰', 'color' => '#85FF33'],
        ['name' => 'Insurance', 'icon' => '🛡️', 'color' => '#FF3385'],
        ['name' => 'Personal Care', 'icon' => '💄', 'color' => '#FF5733'],
        ['name' => 'Other', 'icon' => '📦', 'color' => '#999999']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Spending | Expense Tracker</title>
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
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        .floating-shape:nth-child(1) {
            width: 100px;
            height: 100px;
            top: 15%;
            left: 15%;
            animation-delay: 0s;
        }

        .floating-shape:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 70%;
            right: 10%;
            animation-delay: 3s;
        }

        .floating-shape:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 6s;
        }

        .form-container {
            max-width: 500px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.25);
            padding: 50px 40px;
            text-align: center;
            animation: slideInUp 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #4facfe);
            animation: shimmer 3s ease-in-out infinite;
        }

        .header-section {
            margin-bottom: 35px;
            animation: fadeInDown 0.8s ease-out 0.2s both;
        }

        .emoji {
            font-size: 3.5rem;
            margin-bottom: 15px;
            animation: bounce 2s infinite, glow 3s ease-in-out infinite alternate;
            display: inline-block;
        }

        h2 {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            font-size: 2.6rem;
            font-weight: 700;
        }

        .subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .welcome-tip {
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(79, 172, 254, 0.1));
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 35px;
            border: 1px solid rgba(102, 126, 234, 0.2);
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .tip-text {
            color: #667eea;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.4;
        }

        .form-container form {
            display: flex;
            flex-direction: column;
            gap: 25px;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .section-title {
            text-align: left;
            color: #555;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .category-section {
            margin-bottom: 10px;
        }

        /* Enhanced category grid with better scrolling */
        .category-container {
            position: relative;
            margin-bottom: 10px;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 15px;
            max-height: 200px;
            overflow-y: auto;
            padding-right: 5px;
            scrollbar-width: thin;
            scrollbar-color: rgba(102, 126, 234, 0.3) transparent;
        }

        .category-grid::-webkit-scrollbar {
            width: 6px;
        }

        .category-grid::-webkit-scrollbar-track {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
        }

        .category-grid::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.3);
            border-radius: 10px;
        }

        .category-grid::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.5);
        }

        .category-option {
            background: rgba(248, 249, 255, 0.8);
            border: 2px solid rgba(102, 126, 234, 0.1);
            border-radius: 16px;
            padding: 16px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .category-option:hover {
            border-color: #667eea;
            background: #fff;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }

        .category-option.selected {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: #fff;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .category-option .category-emoji {
            font-size: 1.8rem;
            margin-bottom: 8px;
            display: block;
            animation: none;
        }

        .category-option .category-name {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .category-scroll-hint {
            text-align: center;
            color: #888;
            font-size: 0.8rem;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .category-count {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .input-group {
            position: relative;
            text-align: left;
        }

        .input-label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border-radius: 50px;
            border: 2px solid rgba(102, 126, 234, 0.1);
            font-size: 1rem;
            background: rgba(248, 249, 255, 0.8);
            transition: all 0.3s ease;
            outline: none;
            font-family: inherit;
        }

        .input-group textarea {
            border-radius: 20px;
            padding: 16px 20px;
            resize: vertical;
            min-height: 80px;
        }

        .input-group input:focus,
        .input-group textarea:focus {
            border-color: #667eea;
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }

        .input-group input::placeholder,
        .input-group textarea::placeholder {
            color: #999;
            transition: all 0.3s ease;
        }

        .input-group input:focus::placeholder,
        .input-group textarea:focus::placeholder {
            color: transparent;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            color: #667eea;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .textarea-group .input-icon {
            top: 45px;
        }

        .input-group input:focus + .input-icon {
            transform: translateY(-50%) scale(1.1);
            color: #764ba2;
        }

        .amount-helper {
            font-size: 0.85rem;
            color: #888;
            margin-top: 5px;
            text-align: left;
        }

        button[type="submit"] {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 18px 0;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            margin-top: 15px;
        }

        button[type="submit"]::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        button[type="submit"]:hover::before {
            left: 100%;
        }

        button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        .msg {
            font-size: 1rem;
            margin: 10px 0 0 0;
            min-height: 20px;
            padding: 14px 18px;
            border-radius: 16px;
            animation: slideInUp 0.5s ease-in-out;
            text-align: center;
            font-weight: 500;
        }

        .msg.success {
            color: #2e7d32;
            background: rgba(46, 125, 50, 0.1);
            border: 2px solid rgba(46, 125, 50, 0.2);
        }

        .msg.error {
            color: #d32f2f;
            background: rgba(211, 47, 47, 0.1);
            border: 2px solid rgba(211, 47, 47, 0.2);
        }

        .msg:empty {
            display: none;
        }

        .back-link {
            margin-top: 30px;
            color: #666;
            font-size: 1rem;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }

        .back-link a:hover::after {
            width: 100%;
        }

        .back-link a:hover {
            transform: translateY(-1px);
        }

        .progress-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 25px;
            animation: fadeInUp 0.8s ease-out 0.7s both;
        }

        .progress-step {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .progress-step.active {
            background: #667eea;
            transform: scale(1.2);
        }

        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes glow {
            0% { filter: drop-shadow(0 0 5px rgba(102, 126, 234, 0.5)); }
            100% { filter: drop-shadow(0 0 15px rgba(102, 126, 234, 0.8)); }
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

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
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

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .form-container {
                padding: 40px 25px;
                margin: 20px;
            }
            h2 {
                font-size: 2.2rem;
            }
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                max-height: 250px;
            }
            .category-option {
                padding: 12px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>

    <div class="form-container">
        <div class="header-section">
            <div class="emoji">💸</div>
            <h2>Track Your Spending</h2>
            <p class="subtitle">Every expense recorded is a step toward financial clarity</p>
        </div>

        <div class="welcome-tip">
            <div class="tip-text">
                Recording your expenses helps you understand your spending patterns and make smarter financial decisions!
            </div>
        </div>
        
        <form method="post" action="" id="expenseForm">
            <div class="category-section">
                <div class="section-title">
                 What did you spend on?
                    <div class="category-count"></div>
                </div>
                <div class="category-container">
                    <div class="category-grid" id="categoryGrid">
                        <?php foreach($categories as $category): ?>
                            <div class="category-option" data-category="<?= htmlspecialchars($category['name']) ?>">
                                <div class="category-emoji"><?= $category['icon'] ?></div>
                                <div class="category-name"><?= htmlspecialchars($category['name']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if(count($categories) > 6): ?>
                        <div class="category-scroll-hint">
                            <span>⬆⬇</span> Scroll to see all categories
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <input type="hidden" name="category" id="selectedCategory" required>
            
            <div class="section-title">
                <span>💰</span> How much did you spend?
            </div>
            <div class="input-group">
                <input type="number" name="amount" id="amount" placeholder="Enter amount" min="0.01" step="0.01" required>
                <div class="amount-helper">Enter the exact amount you spent (e.g., 2500 RWF)</div>
            </div>
            
            <div class="section-title">
                <span>📝</span> Tell us more (optional)
            </div>
            <div class="input-group textarea-group">
                <textarea name="description" id="description" placeholder="Add details like location, what you bought, or any notes..." rows="3"></textarea>
            </div>
            
            <div class="section-title">
                 When did this happen?
            </div>
            <div class="input-group">
                <input type="date" name="date" id="date" required>
                <div class="input-icon">📅</div>
            </div>
            
            <button type="submit"> Record This Expense</button>
            
            <?php if($message): ?>
                <div class="msg <?= isset($success) && $success ? 'success' : 'error' ?>"><?= $message ?></div>
            <?php endif; ?>
        </form>
        
        <p class="back-link">
            <a href="dashboard.php">
                <span>←</span> Back to Your Dashboard
            </a>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('expenseForm');
            const inputs = form.querySelectorAll('input, textarea');
            const categoryOptions = document.querySelectorAll('.category-option');
            const selectedCategoryInput = document.getElementById('selectedCategory');
            const dateInput = document.getElementById('date');
            const progressSteps = document.querySelectorAll('.progress-step');
            const categoryGrid = document.getElementById('categoryGrid');
            
            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            dateInput.value = today;
            
            // Add smooth scrolling indicator for category grid
            if (categoryOptions.length > 6) {
                categoryGrid.addEventListener('scroll', function() {
                    const scrollHint = document.querySelector('.category-scroll-hint');
                    if (scrollHint) {
                        scrollHint.style.opacity = this.scrollTop > 0 ? '0.5' : '1';
                    }
                });
            }
            
            // Category selection with welcoming feedback
            categoryOptions.forEach(option => {
                option.addEventListener('click', function() {
                    categoryOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedCategoryInput.value = this.dataset.category;
                    
                    // Update progress
                    progressSteps[1].classList.add('active');
                    
                    // Add selection animation
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'translateY(-3px)';
                    }, 100);
                    
                    // Show encouraging message
                    showTempMessage(`Great choice! ${this.dataset.category} selected 👍`, 'success');
                });
            });
            
            // Amount input validation with encouraging feedback
            const amountInput = document.getElementById('amount');
            amountInput.addEventListener('input', function() {
                if (this.value && parseFloat(this.value) > 0) {
                    progressSteps[2].classList.add('active');
                    showTempMessage('Perfect! Amount recorded 💰', 'success');
                }
            });
            
            // Add real-time validation with welcoming messages
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateInput(this);
                });
                
                input.addEventListener('input', function() {
                    if (this.classList.contains('error')) {
                        validateInput(this);
                    }
                });
            });
            
            // Form submission with encouraging animation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate category selection
                if (!selectedCategoryInput.value) {
                    showError('Please select a category first - it helps organize your spending! 😊');
                    isValid = false;
                }
                
                inputs.forEach(input => {
                    if (!validateInput(input)) {
                        isValid = false;
                    }
                });
                
                if (isValid) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<span style="animation: spin 1s linear infinite;">⏳</span> Recording your expense...';
                    submitBtn.style.background = 'linear-gradient(45deg, #4caf50, #45a049)';
                    
                    // Show success animation
                    progressSteps.forEach(step => step.classList.add('active'));
                }
            });
            
            function validateInput(input) {
                const value = input.value.trim();
                let isValid = true;
                
                // Remove previous error styling
                input.style.borderColor = '';
                input.style.background = '';
                
                if (input.type === 'number') {
                    if (!value || parseFloat(value) <= 0) {
                        showInputError(input, 'Please enter a valid amount');
                        isValid = false;
                    }
                } else if (input.type === 'date') {
                    if (!value) {
                        showInputError(input, 'Please select when this expense occurred');
                        isValid = false;
                    }
                }
                
                if (isValid) {
                    input.classList.remove('error');
                    input.style.borderColor = '#4caf50';
                    input.style.background = 'rgba(76, 175, 80, 0.05)';
                }
                
                return isValid;
            }
            
            function showInputError(input, message) {
                input.classList.add('error');
                input.style.borderColor = '#ff6b6b';
                input.style.background = 'rgba(255, 107, 107, 0.05)';
                input.style.animation = 'shake 0.5s ease-in-out';
                
                setTimeout(() => {
                    input.style.animation = '';
                }, 500);
            }
            
            function showError(message) {
                showTempMessage(message, 'error');
            }
            
            function showTempMessage(message, type) {
                // Remove existing temp messages
                const existingMsg = document.querySelector('.temp-msg');
                if (existingMsg) existingMsg.remove();
                
                const messageDiv = document.createElement('div');
                messageDiv.className = `msg ${type} temp-msg`;
                messageDiv.textContent = message;
                messageDiv.style.animation = 'slideInUp 0.5s ease-in-out';
                
                const submitBtn = form.querySelector('button[type="submit"]');
                form.insertBefore(messageDiv, submitBtn);
                
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.style.animation = 'fadeInUp 0.3s ease-in-out reverse';
                        setTimeout(() => messageDiv.remove(), 300);
                    }
                }, 2500);
            }
        });

        // Add spinning animation for loading state
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>