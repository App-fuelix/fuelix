<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\FirestoreService;
use App\Services\FirestoreUserService;

Route::get('/', function () {
    return redirect(Auth::check() ? '/dashboard' : '/login');
});

Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::post('/login', function (Request $request, FirestoreUserService $firestoreUsers) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    try {
        $firestoreUser = $firestoreUsers->verifyCredentials($credentials['email'], $credentials['password']);
    } catch (Throwable) {
        return back()
            ->withErrors(['email' => 'Firestore is unavailable. Please try again later.'])
            ->onlyInput('email');
    }

    if (! $firestoreUser) {
        return back()
            ->withErrors(['email' => 'Email or password incorrect.'])
            ->onlyInput('email');
    }

    $role = strtolower(trim((string) ($firestoreUser['role'] ?? '')));
    if ($role !== 'admin') {
        return back()
            ->withErrors(['email' => 'Access denied. This account is not an admin.'])
            ->onlyInput('email');
    }

    $user = User::firstOrNew(['email' => strtolower($firestoreUser['email'])]);
    $user->name = $firestoreUser['name'] ?? $user->name ?? 'FueliX Admin';
    $user->password = $firestoreUser['password'];
    $user->save();

    Auth::login($user, $request->boolean('remember'));
    $request->session()->regenerate();

    return redirect()->intended('/dashboard');
})->middleware('guest');

Route::get('/register', function () {
    return redirect('/login')->withErrors([
        'email' => 'Admin accounts must already exist in Firestore.',
    ]);
})->middleware('guest');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (FirestoreService $firestore) {
        $users = [];
        $transactions = [];

        try {
            $users = $firestore->list('users');

            foreach ($users as $user) {
                if (strtolower((string) ($user['role'] ?? 'user')) === 'admin') {
                    continue;
                }

                foreach ($firestore->subList('users', (string) $user['id'], 'transactions') as $transaction) {
                    $transactions[] = [
                        ...$transaction,
                        'user_name' => $user['name'] ?? 'Unknown user',
                        'user_email' => $user['email'] ?? '',
                    ];
                }
            }
        } catch (Throwable) {
            session()->flash('dashboard_error', 'Unable to load dashboard data from Firestore.');
        }

        usort($transactions, fn ($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));

        $clientUsers = array_values(array_filter(
            $users,
            fn ($user) => strtolower((string) ($user['role'] ?? 'user')) !== 'admin'
        ));

        $monthly = [];
        foreach ($transactions as $transaction) {
            $timestamp = strtotime((string) ($transaction['date'] ?? ''));
            if (! $timestamp) {
                continue;
            }

            $month = date('M', $timestamp);
            $monthly[$month] = ($monthly[$month] ?? 0) + (float) ($transaction['quantity_liters'] ?? 0);
        }

        return view('dashboard', [
            'stats' => [
                'users' => count($clientUsers),
                'transactions' => count($transactions),
                'consumption' => array_sum(array_map(fn ($item) => (float) ($item['quantity_liters'] ?? 0), $transactions)),
                'expenses' => array_sum(array_map(fn ($item) => (float) ($item['amount'] ?? 0), $transactions)),
            ],
            'recentTransactions' => array_slice($transactions, 0, 6),
            'chartLabels' => array_keys($monthly),
            'chartData' => array_values($monthly),
        ]);
    });

    Route::get('/users', function (FirestoreService $firestore) {
        try {
            $users = $firestore->list('users');
        } catch (Throwable) {
            $users = [];
            session()->flash('users_error', 'Unable to load users from Firestore.');
        }

        return view('users', ['users' => $users]);
    });

    Route::get('/users/{id}', function (FirestoreService $firestore, string $id) {
        $user = null;
        $transactions = [];
        $fuelCards = [];
        $availablePlans = [];

        try {
            $user = $firestore->get('users', $id);
            if ($user && strtolower((string) ($user['role'] ?? 'user')) !== 'admin') {
                $transactions = $firestore->subList('users', $id, 'transactions');
                $fuelCards = $firestore->subList('users', $id, 'fuel_cards');
            }
            
            // Récupérer les plans disponibles
            $availablePlans = $firestore->list('card_plans');
            usort($availablePlans, fn($a, $b) => ($a['tier_level'] ?? 0) <=> ($b['tier_level'] ?? 0));
        } catch (Throwable) {
            session()->flash('user_error', 'Unable to load user from Firestore.');
        }

        return view('user-details', [
            'firestoreUser' => $user,
            'transactions' => $transactions,
            'fuelCards' => $fuelCards,
            'availablePlans' => $availablePlans,
        ]);
    });

    Route::post('/users/{id}', function (Request $request, FirestoreService $firestore, string $id) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $user = $firestore->get('users', $id);
            if (! $user) {
                return back()->withErrors(['user' => 'User not found in Firestore.']);
            }
            if (strtolower((string) ($user['role'] ?? 'user')) === 'admin') {
                return back()->withErrors(['user' => 'Admin users cannot be edited from this screen.']);
            }

            $firestore->update('users', $id, [
                ...$data,
                'role' => 'user',
            ]);
        } catch (Throwable) {
            return back()->withErrors(['user' => 'Unable to update Firestore user.']);
        }

        return back()->with('user_success', 'User updated successfully.');
    });

    Route::post('/seed-card-plans', function (FirestoreService $firestore) {
        try {
            $plans = [
                [
                    'id' => 'bronze',
                    'name' => 'Bronze Card',
                    'description' => 'Basic fuel access',
                    'color' => '#CD7F32',
                    'tier_level' => 1,
                    'authorized_products' => json_encode(['fuel']),
                    'is_active' => true,
                ],
                [
                    'id' => 'silver',
                    'name' => 'Silver Card',
                    'description' => 'Fuel + Car wash',
                    'color' => '#C0C0C0',
                    'tier_level' => 2,
                    'authorized_products' => json_encode(['fuel', 'carwash']),
                    'is_active' => true,
                ],
                [
                    'id' => 'gold',
                    'name' => 'Gold Card',
                    'description' => 'All services included',
                    'color' => '#FFD700',
                    'tier_level' => 3,
                    'authorized_products' => json_encode(['fuel', 'carwash', 'lubricants']),
                    'is_active' => true,
                ],
            ];

            foreach ($plans as $planData) {
                $planId = $planData['id'];
                unset($planData['id']);
                
                $existing = $firestore->get('card_plans', $planId);
                if (!$existing) {
                    $firestore->create('card_plans', $planData);
                }
            }

            return back()->with('user_success', 'Card plans seeded successfully.');
        } catch (Throwable $e) {
            return back()->withErrors(['user' => 'Unable to seed card plans: ' . $e->getMessage()]);
        }
    });

    Route::put('/users/{id}/card-level', function (Request $request, FirestoreService $firestore, string $id) {
        $request->validate([
            'plan_id' => ['required', 'string'],
        ]);

        try {
            $user = $firestore->get('users', $id);
            if (!$user) {
                return back()->withErrors(['user' => 'User not found.']);
            }
            if (strtolower((string) ($user['role'] ?? 'user')) === 'admin') {
                return back()->withErrors(['user' => 'Cannot change admin card level.']);
            }

            // Récupérer la carte de l'utilisateur
            $cards = $firestore->subList('users', $id, 'fuel_cards');
            if (empty($cards)) {
                return back()->withErrors(['user' => 'User has no fuel card.']);
            }

            $card = $cards[0];
            
            // Récupérer le plan sélectionné
            $plan = $firestore->get('card_plans', $request->plan_id);
            if (!$plan) {
                return back()->withErrors(['user' => 'Card plan not found.']);
            }

            // Mettre à jour la carte
            $firestore->subUpdate('users', $id, 'fuel_cards', $card['id'], [
                'card_plan_id' => $plan['id'],
                'card_plan_name' => $plan['name'],
                'color' => $plan['color'],
                'authorized_products' => $plan['authorized_products'],
            ]);

            return back()->with('user_success', "Card level updated to {$plan['name']} successfully.");
        } catch (Throwable $e) {
            return back()->withErrors(['user' => 'Unable to update card level: ' . $e->getMessage()]);
        }
    });

    Route::post('/users/{id}/toggle', function (FirestoreService $firestore, string $id) {
        try {
            $user = $firestore->get('users', $id);
            if (! $user) {
                return back()->withErrors(['user' => 'User not found in Firestore.']);
            }
            if (strtolower((string) ($user['role'] ?? 'user')) === 'admin') {
                return back()->withErrors(['user' => 'Admin users cannot be deactivated.']);
            }

            $isActive = (bool) ($user['is_active'] ?? strtolower((string) ($user['status'] ?? 'active')) === 'active');
            $firestore->update('users', $id, [
                'is_active' => ! $isActive,
                'status' => $isActive ? 'Inactive' : 'Active',
            ]);
        } catch (Throwable) {
            return back()->withErrors(['user' => 'Unable to update user status.']);
        }

        return back()->with('users_success', 'User status updated.');
    });

    Route::delete('/users/{id}', function (FirestoreService $firestore, string $id) {
        try {
            $user = $firestore->get('users', $id);
            if (! $user) {
                return back()->withErrors(['user' => 'User not found in Firestore.']);
            }
            if (strtolower((string) ($user['role'] ?? 'user')) === 'admin') {
                return back()->withErrors(['user' => 'Admin users cannot be deleted.']);
            }

            $firestore->delete('users', $id);
        } catch (Throwable) {
            return back()->withErrors(['user' => 'Unable to delete Firestore user.']);
        }

        return redirect('/users')->with('users_success', 'User deleted from Firestore.');
    });

    Route::get('/fuel-card', function () {
        return redirect('/dashboard');
    });

    Route::get('/history', function (FirestoreService $firestore) {
        $transactions = [];

        try {
            foreach ($firestore->list('users') as $user) {
                if (strtolower((string) ($user['role'] ?? 'user')) === 'admin') {
                    continue;
                }

                foreach ($firestore->subList('users', (string) $user['id'], 'transactions') as $transaction) {
                    $transactions[] = [
                        ...$transaction,
                        'user_name' => $user['name'] ?? 'Unknown user',
                    ];
                }
            }
        } catch (Throwable) {
            session()->flash('history_error', 'Unable to load transactions from Firestore.');
        }

        usort($transactions, fn ($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));

        return view('history', ['transactions' => $transactions]);
    });

    Route::get('/transactions/{id}', function (FirestoreService $firestore, string $id) {
        $transaction = null;

        try {
            foreach ($firestore->list('users') as $user) {
                if (strtolower((string) ($user['role'] ?? 'user')) === 'admin') {
                    continue;
                }

                foreach ($firestore->subList('users', (string) $user['id'], 'transactions') as $item) {
                    $itemId = (string) ($item['id'] ?? $item['transaction_id'] ?? '');

                    if ($itemId === $id) {
                        $transaction = [
                            ...$item,
                            'user_name' => $user['name'] ?? 'Unknown user',
                            'user_email' => $user['email'] ?? '',
                        ];
                        break 2;
                    }
                }
            }
        } catch (Throwable) {
            session()->flash('transaction_error', 'Unable to load transaction from Firestore.');
        }

        return view('transaction-details', [
            'transactionId' => $id,
            'transaction' => $transaction,
        ]);
    });

    Route::get('/analytics', function (FirestoreService $firestore) {
        $transactions = [];
        $topUsers = [];
        $monthlyLiters = [];
        $monthlyExpenses = [];
        $clientCount = 0;

        try {
            foreach ($firestore->list('users') as $user) {
                if (strtolower((string) ($user['role'] ?? 'user')) === 'admin') {
                    continue;
                }

                $clientCount++;
                $userTotal = 0.0;
                foreach ($firestore->subList('users', (string) $user['id'], 'transactions') as $transaction) {
                    $liters = (float) ($transaction['quantity_liters'] ?? 0);
                    $amount = (float) ($transaction['amount'] ?? 0);
                    $timestamp = strtotime((string) ($transaction['date'] ?? ''));

                    $transactions[] = $transaction;
                    $userTotal += $liters;

                    if ($timestamp) {
                        $monthKey = date('Y-m', $timestamp);
                        $monthlyLiters[$monthKey] = ($monthlyLiters[$monthKey] ?? 0) + $liters;
                        $monthlyExpenses[$monthKey] = ($monthlyExpenses[$monthKey] ?? 0) + $amount;
                    }
                }

                if ($userTotal > 0) {
                    $topUsers[$user['name'] ?? 'Unknown user'] = $userTotal;
                }
            }
        } catch (Throwable) {
            session()->flash('analytics_error', 'Unable to load analytics from Firestore.');
        }

        ksort($monthlyLiters);
        ksort($monthlyExpenses);
        arsort($topUsers);

        $fuelLabels = array_map(fn ($month) => date('M Y', strtotime($month . '-01')), array_keys($monthlyLiters));
        $expenseLabels = array_map(fn ($month) => date('M Y', strtotime($month . '-01')), array_keys($monthlyExpenses));

        return view('analytics', [
            'stats' => [
                'users' => $clientCount,
                'transactions' => count($transactions),
                'consumption' => array_sum(array_map(fn ($item) => (float) ($item['quantity_liters'] ?? 0), $transactions)),
                'expenses' => array_sum(array_map(fn ($item) => (float) ($item['amount'] ?? 0), $transactions)),
            ],
            'fuelLabels' => $fuelLabels,
            'fuelData' => array_values($monthlyLiters),
            'expenseLabels' => $expenseLabels,
            'expenseData' => array_values($monthlyExpenses),
            'topUserLabels' => array_slice(array_keys($topUsers), 0, 5),
            'topUserData' => array_slice(array_values($topUsers), 0, 5),
            'distributionData' => [
                count(array_filter($transactions, fn ($item) => (float) ($item['quantity_liters'] ?? 0) <= 25)),
                count(array_filter($transactions, fn ($item) => (float) ($item['quantity_liters'] ?? 0) > 25 && (float) ($item['quantity_liters'] ?? 0) <= 45)),
                count(array_filter($transactions, fn ($item) => (float) ($item['quantity_liters'] ?? 0) > 45)),
            ],
        ]);
    });

    Route::get('/settings', function () {
        return view('settings');
    });

    Route::post('/settings', function (Request $request) {
        $data = $request->validate([
            'language' => ['required', 'in:English,French'],
            'currency' => ['required', 'in:TND,USD,EUR'],
            'refresh_interval' => ['required', 'in:5 min,10 min,15 min,30 min'],
            'email_notifications' => ['nullable', 'boolean'],
        ]);

        session([
            'admin_settings' => [
                'language' => $data['language'],
                'currency' => $data['currency'],
                'refresh_interval' => $data['refresh_interval'],
                'email_notifications' => $request->boolean('email_notifications'),
            ],
        ]);

        return back()->with('settings_success', 'Settings saved for this admin session.');
    });

    Route::get('/profile', function () {
        return view('profile');
    });

    Route::post('/profile', function (Request $request, FirestoreUserService $firestoreUsers) {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = $request->user();

        try {
            $firestoreUser = $firestoreUsers->findByEmail($user->email);

            if (! $firestoreUser) {
                return back()->withErrors(['profile' => 'Admin account was not found in Firestore.']);
            }

            $updated = $firestoreUsers->updateUser($firestoreUser['id'], [
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'role' => 'admin',
            ]);
        } catch (Throwable) {
            return back()->withErrors(['profile' => 'Unable to update Firestore profile.']);
        }

        $user->name = $updated['name'] ?? $data['name'];
        $user->email = $updated['email'] ?? strtolower($data['email']);
        $user->save();

        return back()->with('profile_success', 'Profile updated successfully.');
    });

    Route::post('/profile/password', function (Request $request, FirestoreUserService $firestoreUsers) {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        try {
            $firestoreUser = $firestoreUsers->findByEmail($user->email);

            if (! $firestoreUser || ! Hash::check($data['current_password'], (string) ($firestoreUser['password'] ?? ''))) {
                return back()->withErrors(['password' => 'Current password is incorrect.']);
            }

            $hash = Hash::make($data['password']);
            $firestoreUsers->updateUser($firestoreUser['id'], [
                'password' => $hash,
                'role' => 'admin',
            ]);
        } catch (Throwable) {
            return back()->withErrors(['password' => 'Unable to update Firestore password.']);
        }

        $user->password = $hash;
        $user->save();

        return back()->with('password_success', 'Password updated successfully.');
    });
});

Route::get('/reset-password', function () {
    return view('reset-password');
})->name('password.reset.form');

Route::get('/test-email', function () {
    Mail::raw('Test email from Fuelix - Mailtrap is working!', function ($message) {
        $message->to('test@example.com')
                ->subject('Mailtrap Test - Success');
    });
    
    return 'Test email sent! Check your Mailtrap inbox.';
});
