<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Member;
use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Services\TenantManager;
use Illuminate\Support\Facades\DB;
use App\Models\PersonalAccessToken;

class ValidateMemberToken
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Get token from request
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthorized - No token provided'], 401);
        }

        // 2. Parse token to extract member_id and get actual token for hashing
        // Token format: "tenant_id|member_id|actual_token"
        $parsed = $this->parseToken($token);

        if (!$parsed) {
            return response()->json(['message' => 'Invalid token format'], 401);
        }
        $tenantId = $parsed['tenant_id'];
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return response()->json(['message' => 'Invalid tenant'], 401);
        }
        TenantManager::switchToTenant($tenant->database_name);

        $memberId = $parsed['member_id'];
        $fullTokenForHash = $parsed['full_token_for_hash'];

        // 3. Hash the full token for database comparison
        $hashedToken = hash('sha256', $fullTokenForHash);

        // 4. Find token in CURRENT database (tenant DB)
        $memberToken = PersonalAccessToken::where('token', $hashedToken)
            ->where('tokenable_id', $memberId)
            ->where('tokenable_type', Member::class)
            ->first();

        if (!$memberToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        // 5. Check if token is expired
        if ($memberToken->expires_at && $memberToken->expires_at->isPast()) {
            $memberToken->delete();
            return response()->json(['message' => 'Token expired'], 401);
        }

        // 6. Update last used timestamp
        $memberToken->update(['last_used_at' => now()]);

        // 7. Get the member and attach to request
        $member = Member::find($memberId);

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 401);
        }

        // Set authenticated user
        auth()->setUser($member);
        $request->setUserResolver(fn () => $member);

        // 8. Store member info for later use
        $request->merge([
            'current_member' => $member,
            'member_id' => $memberId,
            'tenant_id' => $member->tenant_id,
        ]);

        // 9. Continue the request
        $response = $next($request);

        // 10. Add simple database headers to response (optional)
        $response->headers->set('X-DB-Name', DB::connection()->getDatabaseName());
        $response->headers->set('X-Tenant-ID', $tenantId);
        $response->headers->set('X-Member-ID', $memberId);

        return $response;
    }

    /**
     * Parse token to extract tenant_id, member_id, and actual token
     * Format: "tenant_id|member_id|actual_token"
     */
    private function parseToken(string $token): ?array
    {
        // Count the number of | separators
        $separatorCount = substr_count($token, '|');

        if ($separatorCount === 2) {
            // New format: "tenant_id|member_id|actual_token"
            $parts = explode('|', $token, 3);

            if (count($parts) !== 3 || !is_numeric($parts[0]) || !is_numeric($parts[1]) || empty($parts[2])) {
                return null;
            }

            return [
                'tenant_id' => (int) $parts[0],
                'member_id' => (int) $parts[1],
                'actual_token' => $parts[2],
                'full_token_for_hash' => $token
            ];
        }

        // Plain token (no | separators) - not supported in new system
        return null;
    }
}
