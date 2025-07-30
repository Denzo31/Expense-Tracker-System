<?php
include 'db/config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST["name"]);
    $email = $conn->real_escape_string($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $message = "Email already registered!";
    } else {
        $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
        if ($conn->query($sql)) {
            header("Location: login.php");
            exit();
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Expense Tracker</title>
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
            max-width: 450px;
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

        .emoji {
            font-size: 3rem;
            margin-bottom: 15px;
            animation: bounce 2s infinite, glow 3s ease-in-out infinite alternate;
            display: inline-block;
        }

        h2 {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            font-size: 2.4rem;
            font-weight: 700;
            animation: fadeInDown 0.8s ease-out 0.2s both;
        }

        .subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 35px;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .form-container form {
            display: flex;
            flex-direction: column;
            gap: 25px;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border-radius: 50px;
            border: 2px solid rgba(102, 126, 234, 0.1);
            font-size: 1rem;
            background: rgba(248, 249, 255, 0.8);
            transition: all 0.3s ease;
            outline: none;
        }

        .input-group input:focus {
            border-color: #667eea;
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }

        .input-group input::placeholder {
            color: #999;
            transition: all 0.3s ease;
        }

        .input-group input:focus::placeholder {
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
        }

        .input-group input:focus + .input-icon {
            transform: translateY(-50%) scale(1.1);
            color: #764ba2;
        }

        button[type="submit"] {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 16px 0;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
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
            color: #e53935;
            font-size: 1rem;
            margin: -10px 0 0 0;
            min-height: 20px;
            padding: 10px;
            border-radius: 12px;
            background: rgba(229, 57, 53, 0.1);
            border-left: 4px solid #e53935;
            animation: shake 0.5s ease-in-out;
        }

        .msg:empty {
            display: none;
        }

        .login-link {
            margin-top: 25px;
            color: #666;
            font-size: 1rem;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }

        .login-link a:hover::after {
            width: 100%;
        }

        .login-link a:hover {
            transform: translateY(-1px);
        }

        .back-home {
            position: absolute;
            top: 30px;
            left: 30px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
            animation: fadeInLeft 0.8s ease-out 1s both;
        }

        .back-home:hover {
            color: #fff;
            transform: translateX(-5px);
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

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
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
                font-size: 2rem;
            }
            .back-home {
                top: 20px;
                left: 20px;
                font-size: 0.9rem;
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

    <a href="index.php" class="back-home">← Back to Home</a>

    <div class="form-container">
        <div class="emoji">🚀</div>
        <h2>Create Account</h2>
        <p class="subtitle">Join thousands managing their finances smarter</p>
        
        <form method="post" action="" id="registerForm">
            <div class="input-group">
                <input type="text" name="name" id="name" placeholder="Full Name" required>
                <div class="input-icon">👤</div>
            </div>
            
            <div class="input-group">
                <input type="email" name="email" id="email" placeholder="Email Address" required>
                <div class="input-icon">📧</div>
            </div>
            
            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Create Password" required minlength="6">
                <div class="input-icon">🔒</div>
            </div>
            
            <button type="submit">Create My Account</button>
            
            <?php if($message): ?>
                <div class="msg"><?= $message ?></div>
            <?php endif; ?>
        </form>
        
        <p class="login-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const inputs = form.querySelectorAll('input');
            
            // Add real-time validation
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
            
            // Form submission with animation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!validateInput(input)) {
                        isValid = false;
                    }
                });
                
                if (isValid) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<span style="animation: spin 1s linear infinite;">⏳</span> Creating Account...';
                    submitBtn.style.background = 'linear-gradient(45deg, #4caf50, #45a049)';
                }
            });
            
            function validateInput(input) {
                const value = input.value.trim();
                let isValid = true;
                
                // Remove previous error styling
                input.style.borderColor = '';
                input.style.background = '';
                
                if (input.type === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        showInputError(input, 'Please enter a valid email address');
                        isValid = false;
                    }
                } else if (input.type === 'password') {
                    if (value.length < 6) {
                        showInputError(input, 'Password must be at least 6 characters');
                        isValid = false;
                    }
                } else if (input.name === 'name') {
                    if (value.length < 2) {
                        showInputError(input, 'Please enter your full name');
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
                input.style.borderColor = '#e53935';
                input.style.background = 'rgba(229, 57, 53, 0.05)';
                input.style.animation = 'shake 0.5s ease-in-out';
                
                // Remove animation after it completes
                setTimeout(() => {
                    input.style.animation = '';
                }, 500);
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