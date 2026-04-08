<?php
/**
 * Test script for password reset flow
 * Run with: php test-password-reset.php
 */

$baseUrl = 'http://localhost:8000/api';
$testEmail = 'test@example.com';

echo "=== Testing Password Reset Flow ===\n\n";

// Step 1: Request password reset
echo "Step 1: Requesting password reset for {$testEmail}...\n";

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
    echo "✓ Password reset email sent successfully!\n";
    echo "→ Check your Mailtrap inbox for the reset link\n";
    echo "→ The email will contain a token and link\n\n";
    
    echo "Step 2: Get the token from Mailtrap email, then test reset:\n";
    echo "You can manually test with this curl command:\n\n";
    echo "curl -X POST {$baseUrl}/reset-password \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -d '{\n";
    echo "    \"token\": \"YOUR_TOKEN_FROM_EMAIL\",\n";
    echo "    \"email\": \"{$testEmail}\",\n";
    echo "    \"password\": \"newpassword123\",\n";
    echo "    \"password_confirmation\": \"newpassword123\"\n";
    echo "  }'\n\n";
    
    // Try to get token from database
    echo "Checking database for reset token...\n";
    exec('php artisan tinker --execute="print_r(DB::table(\'password_reset_tokens\')->where(\'email\', \'' . $testEmail . '\')->first());"', $output);
    echo implode("\n", $output) . "\n";
    
} else {
    echo "✗ Failed to send password reset email\n";
    $data = json_decode($response, true);
    print_r($data);
}
