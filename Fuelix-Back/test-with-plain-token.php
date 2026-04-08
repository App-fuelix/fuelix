<?php
/**
 * Test password reset with manual token input
 * 
 * Usage:
 * 1. Run: php test-with-plain-token.php request
 * 2. Check Mailtrap for the token in the URL
 * 3. Run: php test-with-plain-token.php reset YOUR_TOKEN_HERE
 */

$baseUrl = 'http://localhost:8000/api';
$testEmail = 'test@example.com';
$newPassword = 'newpassword123';

$action = $argv[1] ?? 'request';
$token = $argv[2] ?? null;

if ($action === 'request') {
    echo "=== Requesting Password Reset ===\n\n";
    
    $ch = curl_init("{$baseUrl}/forgot-password");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['email' => $testEmail])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Response Code: {$httpCode}\n";
    echo "Response: {$response}\n\n";
    
    if ($httpCode === 200) {
        echo "✓ Password reset email sent!\n\n";
        echo "Next steps:\n";
        echo "1. Check your Mailtrap inbox (sandbox.smtp.mailtrap.io)\n";
        echo "2. Open the email and find the reset link\n";
        echo "3. Copy the 'token' parameter from the URL\n";
        echo "4. Run: php test-with-plain-token.php reset YOUR_TOKEN\n";
    } else {
        echo "✗ Failed to send reset email\n";
    }
    
} elseif ($action === 'reset') {
    if (!$token) {
        die("Usage: php test-with-plain-token.php reset YOUR_TOKEN\n");
    }
    
    echo "=== Testing Password Reset ===\n\n";
    echo "Token: {$token}\n";
    echo "Email: {$testEmail}\n";
    echo "New Password: {$newPassword}\n\n";
    
    $ch = curl_init("{$baseUrl}/reset-password");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'token' => $token,
            'email' => $testEmail,
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Response Code: {$httpCode}\n";
    echo "Response: {$response}\n\n";
    
    if ($httpCode === 200) {
        echo "✓ Password reset successful!\n\n";
        
        // Test login
        echo "Testing login with new password...\n";
        $ch = curl_init("{$baseUrl}/login");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'email' => $testEmail,
                'password' => $newPassword
            ])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            echo "✓ Login successful!\n";
            echo "User: " . $data['user']['email'] . "\n";
            echo "Token: " . substr($data['token'], 0, 40) . "...\n";
        } else {
            echo "✗ Login failed: {$response}\n";
        }
    } else {
        echo "✗ Password reset failed\n";
    }
    
} else {
    echo "Usage:\n";
    echo "  php test-with-plain-token.php request          - Request password reset\n";
    echo "  php test-with-plain-token.php reset TOKEN      - Reset password with token\n";
}
