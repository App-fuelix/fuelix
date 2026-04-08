<?php
/**
 * Complete password reset flow test
 * This simulates the full flow including getting the token
 */

$baseUrl = 'http://localhost:8000/api';
$testEmail = 'test@example.com';
$newPassword = 'newpassword123';

echo "=== Complete Password Reset Flow Test ===\n\n";

// Step 1: Request password reset
echo "Step 1: Requesting password reset...\n";
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

echo "Response: {$response}\n";

if ($httpCode !== 200) {
    die("✗ Failed at step 1\n");
}
echo "✓ Reset email sent\n\n";

// Step 2: Get token from database (simulating getting it from email)
echo "Step 2: Retrieving token from database...\n";
exec('php artisan tinker --execute="echo DB::table(\'password_reset_tokens\')->where(\'email\', \'' . $testEmail . '\')->value(\'token\');"', $output, $returnCode);
$token = trim(implode('', $output));

if (empty($token)) {
    die("✗ No token found in database\n");
}
echo "✓ Token retrieved: " . substr($token, 0, 20) . "...\n\n";

// Step 3: Reset password with token
echo "Step 3: Resetting password with token...\n";
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
echo "Response: {$response}\n";

if ($httpCode === 200) {
    echo "✓ Password reset successful!\n\n";
    
    // Step 4: Test login with new password
    echo "Step 4: Testing login with new password...\n";
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
        echo "✓ Login successful with new password!\n";
        echo "Token: " . substr($data['token'], 0, 30) . "...\n";
        echo "\n=== ALL TESTS PASSED ===\n";
    } else {
        echo "✗ Login failed\n";
        echo "Response: {$response}\n";
    }
} else {
    echo "✗ Password reset failed\n";
    $data = json_decode($response, true);
    print_r($data);
}
