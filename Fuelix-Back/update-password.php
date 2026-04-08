<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'test@example.com')->first();
$user->password = Hash::make('eyaeyaeya');
$user->save();

echo "✓ Password updated for {$user->email}\n";
echo "New password: eyaeyaeya\n";
