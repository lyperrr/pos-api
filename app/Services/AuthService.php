<?php

namespace App\Services;

use App\Mail\ResetPasswordMail;
use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new Business Owner (Tenant, Outlet, Owner Role, & Owner User).
     */
    public function registerOwner(array $data): array
    {
        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Email sudah terdaftar. Gunakan email lain.'],
            ]);
        }

        return DB::transaction(function () use ($data) {
            // 1. Create Tenant with Trial & Subscription defaults
            $tenant = Tenant::create([
                'business_name' => $data['business_name'],
                'business_type' => $data['business_type'] ?? 'retail',
                'subscription_status' => 'trial',
                'subscription_plan' => $data['subscription_plan'] ?? 'starter',
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'trial_ends_at' => now()->addDays(14),
                'max_outlets' => 1,
                'max_users' => 3,
            ]);

            // 2. Create Default Main Outlet
            $outlet = Outlet::create([
                'tenant_id' => $tenant->id,
                'name' => $data['outlet_name'] ?? 'Outlet Utama',
                'address' => $data['outlet_address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
            ]);

            // 3. Create Default Owner Role
            $role = Role::create([
                'tenant_id' => $tenant->id,
                'name' => 'Owner',
                'is_system_default' => true,
            ]);

            // Attach all available system permissions to Owner role
            $allPermissionIds = Permission::pluck('id')->toArray();
            if (! empty($allPermissionIds)) {
                $role->permissions()->sync($allPermissionIds);
            }

            // 4. Create Owner User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'outlet_id' => $outlet->id,
                'role_id' => $role->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
            ]);

            // 5. Generate Sanctum API Token
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user->load(['tenant', 'outlet', 'role.permissions']),
                'token' => $token,
            ];
        });
    }

    /**
     * Authenticate User with Email and Password.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password yang Anda masukkan salah.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda sedang dinonaktifkan. Silakan hubungi admin.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user->load(['tenant', 'outlet', 'role.permissions']),
            'token' => $token,
        ];
    }

    /**
     * Generate password reset token and send reset link email.
     */
    public function sendPasswordResetLink(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Alamat email tersebut tidak terdaftar di sistem NIRA POS.'],
            ]);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $origin = request()->header('Origin') ?? request()->header('Referer');
        if ($origin) {
            $parsed = parse_url($origin);
            $scheme = $parsed['scheme'] ?? 'http';
            $host = $parsed['host'] ?? 'localhost';
            $port = isset($parsed['port']) ? ":{$parsed['port']}" : '';
            $baseUrl = "{$scheme}://{$host}{$port}";
        } else {
            $baseUrl = config('app.frontend_url', 'http://localhost:8003');
        }

        $resetUrl = rtrim($baseUrl, '/') . '/reset-password?token=' . $token . '&email=' . urlencode($email);

        // 0. Try Google Apps Script Webhook (Port 443 HTTPS - Gmail Native Delivery)
        $googleWebhookUrl = env('GOOGLE_MAIL_WEBHOOK_URL');
        if ($googleWebhookUrl) {
            try {
                $response = Http::post($googleWebhookUrl, [
                    'to' => $user->email,
                    'subject' => 'Reset Kata Sandi Akun NIRA POS',
                    'html' => (new ResetPasswordMail($user, $resetUrl))->render(),
                ]);

                if ($response->successful()) {
                    logger()->info("Email reset password successfully sent via Google Mail Webhook to {$email}");
                    return [
                        'email' => $email,
                        'token' => $token,
                        'reset_url' => $resetUrl,
                    ];
                } else {
                    logger()->warning("Google Mail Webhook Warning ({$response->status()}): " . $response->body());
                }
            } catch (\Throwable $e) {
                logger()->error("Google Mail Webhook Error: " . $e->getMessage());
            }
        }

        // 1. Try Brevo HTTP API (Port 443 HTTPS - Sends to ANY recipient)
        $brevoApiKey = env('BREVO_API_KEY');
        if ($brevoApiKey) {
            try {
                $response = Http::withHeaders([
                    'api-key' => $brevoApiKey,
                    'content-type' => 'application/json',
                    'accept' => 'application/json',
                ])->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => 'Nira POS',
                        'email' => 'nirapos.assistant@gmail.com',
                    ],
                    'to' => [
                        ['email' => $user->email, 'name' => $user->full_name],
                    ],
                    'subject' => 'Reset Kata Sandi Akun NIRA POS',
                    'htmlContent' => (new ResetPasswordMail($user, $resetUrl))->render(),
                ]);

                if ($response->successful()) {
                    logger()->info("Email reset password successfully sent via Brevo HTTP API to {$email}");
                    return [
                        'email' => $email,
                        'token' => $token,
                        'reset_url' => $resetUrl,
                    ];
                } else {
                    logger()->warning("Brevo API Warning ({$response->status()}): " . json_encode($response->json()));
                }
            } catch (\Throwable $e) {
                logger()->error("Brevo API Error: " . $e->getMessage());
            }
        }

        // 2. Try Resend HTTP API (Port 443 HTTPS)
        $resendApiKey = env('RESEND_API_KEY');
        if ($resendApiKey) {
            try {
                $response = Http::withToken($resendApiKey)->post('https://api.resend.com/emails', [
                    'from' => 'Nira POS <onboarding@resend.dev>',
                    'to' => [$user->email],
                    'subject' => 'Reset Kata Sandi Akun NIRA POS',
                    'html' => (new ResetPasswordMail($user, $resetUrl))->render(),
                ]);

                if ($response->successful()) {
                    logger()->info("Email reset password successfully sent via Resend HTTP API to {$user->email}");
                    return [
                        'email' => $email,
                        'token' => $token,
                        'reset_url' => $resetUrl,
                    ];
                } else {
                    logger()->warning("Resend API Warning ({$response->status()}): " . json_encode($response->json()));
                }
            } catch (\Throwable $e) {
                logger()->error("Resend API Error: " . $e->getMessage());
            }
        }

        // 2. Fallback to standard Mailer / SMTP / Local Log
        $mailer = config('mail.default');
        $smtpPass = config('mail.mailers.smtp.password');

        if ($mailer === 'smtp' && ($smtpPass === 'your-app-password' || empty($smtpPass))) {
            logger()->info("Password Reset Link for {$email}: {$resetUrl}");
        } else {
            try {
                Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));
            } catch (\Throwable $e) {
                logger()->error("Failed sending reset mail via SMTP: " . $e->getMessage());
                logger()->info("Password Reset Link for {$email}: {$resetUrl}");
            }
        }

        return [
            'email' => $email,
            'token' => $token,
            'reset_url' => $resetUrl,
        ];
    }

    /**
     * Verify if password reset token is valid.
     */
    public function verifyResetToken(string $email, string $token): bool
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $resetRecord || ! Hash::check($token, $resetRecord->token)) {
            return false;
        }

        if ($resetRecord->created_at && now()->diffInMinutes($resetRecord->created_at) > 15) {
            return false;
        }

        return true;
    }

    /**
     * Reset user's password using token.
     */
    public function resetPassword(array $data): void
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (! $resetRecord || ! Hash::check($data['token'], $resetRecord->token)) {
            throw ValidationException::withMessages([
                'email' => ['Token reset kata sandi tidak valid atau telah kadaluarsa.'],
            ]);
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Pengguna tidak ditemukan.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        // Revoke Sanctum tokens and delete reset token
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
    }

    /**
     * Logout and revoke Sanctum access token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Get authenticated user profile with relations.
     */
    public function me(User $user): User
    {
        return $user->load(['tenant', 'outlet', 'role.permissions']);
    }
}
