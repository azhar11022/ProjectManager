<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function Register(RegisterUserRequest $request)
    {
        // 1. Create the User in Main DB (Landlord)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        // 2. Create the Tenant in Main DB
        // (Your TenantObserver will automatically create the DB and migrate tables now)
        $tenant = Tenant::create([
            'name' => $request->company_name,
            'domain' => $request->company_short_code,
            'database_name' => 'db_' . $request->company_short_code,
            'is_active' => true,
            'user_id' => $user->id
        ]);

        // 3. Switch connection to the newly created Tenant DB
        TenantManager::switchToTenant($tenant->database_name);

        // 4. Create the Member in Tenant DB with 'admin' role
        // This happens inside the new database (e.g., db_abc)
        $member = Member::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenant->id,
            'role' => 'admin', // The person who registers the company is the Admin
        ]);

        $token = $member->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Company and Admin registered successfully.',
            'access_token' => $token,
            'domain' => $tenant->domain
        ], 201);
    }

    public function login(LoginUserRequest $request)
    {
        // 1. Extract domain from email (e.g., 'abc' from 'ali@abc.com')
        $emailParts = explode('@', $request->email);
        if (count($emailParts) < 2) {
            return response()->json(['message' => 'Invalid email format'], 422);
        }

        $domainParts = explode('.', $emailParts[1]);
        $domain = $domainParts[0];

        // 2. Find the Tenant in the Main Landlord Database
        $tenant = Tenant::where('domain', $domain)->first();
        $user = null;

        if (!$tenant) {
            // if the tenant is not found, check in the main users table
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Invalid credentials.'], 401);
            }

            // If found in Users (Landlord), switch to THEIR tenant?
            $tenant = $user->tenant;
            if (!$tenant) {
                return response()->json(['message' => 'User has no tenant associated.'], 404);
            }

            TenantManager::switchToTenant($tenant->database_name);
            $user = Member::where('email', $request->email)->first();
        } else {
            // if the tenant is found, switch to tenant DB and check in members table
            TenantManager::switchToTenant($tenant->database_name);
            $user = Member::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Invalid credentials.'], 401);
            }
        }

        // 3. Check if tenant is not active
        if (!$tenant || !$tenant->is_active) {
            return response()->json(['message' => 'Company is inactive.'], 404);
        }

        // 4. Generate Token for the Member
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'role' => $user->role, // 'admin' or 'member'
            'database' => $tenant->database_name
        ]);
    }

    public function logout()
    {
        $member = auth()->user();
        if ($member) {
            $member->currentAccessToken()->delete();
            // return response()->json(['message' => 'Logged out successfully.']);
        }
        return response()->json(['message' => 'Logged out successfully.']);
    }
}
