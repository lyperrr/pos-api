<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
