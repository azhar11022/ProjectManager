<?php
// app/Models/Member.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
class Member extends Authenticatable
{
    use Notifiable;
    // ADD THIS LINE - Very important!
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Create a token for this member
     * Format: "tenant_id|member_id|random_token"
     */
    public function createToken(string $name, array $abilities = ['*'])
    {
        // Get tenant_id from member
        $tenantId = $this->tenant_id;

        if (!$tenantId) {
            throw new \Exception('Member has no tenant_id assigned');
        }

        // Generate random token part
        $randomToken = Str::random(40);

        // Create token format: tenant_id|member_id|random_token
        $plainTextToken = $tenantId . '|' . $this->id . '|' . $randomToken;

        // Hash the full token for storage
        $hashedToken = hash('sha256', $plainTextToken);

        // Token name is just the member ID
        $tokenName = (string) $this->id;

        $token = $this->tokens()->create([
            'name' => $tokenName,
            'token' => $hashedToken,
            'abilities' => $abilities,
            'tokenable_type' => self::class,
            'tokenable_id' => $this->id,
            'expires_at' => now()->addDays(30),
        ]);

        return (object) [
            'accessToken' => $token,
            'plainTextToken' => $plainTextToken, // Returns: "1|123|abc123..."
        ];
    }

    /**
     * Parse token to extract tenant_id, member_id, and actual token
     * Format: "tenant_id|member_id|actual_token"
     */
    public static function parseToken(string $token): ?array
    {
        // Count the number of | separators
        $separatorCount = substr_count($token, '|');

        if ($separatorCount < 2) {
            // Try old format: "member_id|token" for backward compatibility
            if ($separatorCount === 1) {
                $parts = explode('|', $token, 2);
                if (is_numeric($parts[0]) && !empty($parts[1])) {
                    return [
                        'tenant_id' => null,
                        'member_id' => (int) $parts[0],
                        'actual_token' => $parts[1],
                        'full_token_for_hash' => $token
                    ];
                }
            }
            return null;
        }

        $parts = explode('|', $token, 3);

        // Format: [tenant_id, member_id, actual_token]
        if (!is_numeric($parts[0]) || !is_numeric($parts[1]) || empty($parts[2])) {
            return null;
        }

        return [
            'tenant_id' => (int) $parts[0],
            'member_id' => (int) $parts[1],
            'actual_token' => $parts[2],
            'full_token_for_hash' => $token
        ];
    }

    /**
     * Extract only tenant_id from token
     */
    public static function extractTenantIdFromToken(string $token): ?int
    {
        $parsed = self::parseToken($token);
        return $parsed['tenant_id'] ?? null;
    }

    /**
     * Extract only member_id from token
     */
    public static function extractMemberIdFromToken(string $token): ?int
    {
        $parsed = self::parseToken($token);
        return $parsed['member_id'] ?? null;
    }

    /**
     * Find member by token (assumes already connected to correct tenant DB)
     * IMPORTANT: You must be connected to the right tenant DB before calling this!
     */
    public static function findByToken(string $token): ?Member
    {
        $parsed = self::parseToken($token);

        if (!$parsed) {
            return null;
        }

        $memberId = $parsed['member_id'];
        $fullToken = $parsed['full_token_for_hash'];
        $hashedToken = hash('sha256', $fullToken);

        // Find the token in the current tenant database
        $tokenInstance = PersonalAccessToken::where('token', $hashedToken)
            ->where('tokenable_id', $memberId)
            ->where('tokenable_type', self::class)
            ->first();

        if (!$tokenInstance) {
            return null;
        }

        // Check if token is expired
        if ($tokenInstance->expires_at && $tokenInstance->expires_at->isPast()) {
            return null;
        }

        // Get the member
        $member = self::find($memberId);

        if ($member && $tokenInstance) {
            // Verify tenant_id matches
            if ($parsed['tenant_id'] && $member->tenant_id != $parsed['tenant_id']) {
                return null;
            }

            // Update last used timestamp
            $tokenInstance->update(['last_used_at' => now()]);
        }

        return $member;
    }

    /**
     * Get all tokens for this member
     */
    public function tokens()
    {
        return $this->morphMany(\App\Models\PersonalAccessToken::class, 'tokenable');
    }

    /**
     * Get the current access token
     */
    public function currentAccessToken()
    {
        return $this->accessToken ?? null;
    }

    /**
     * Tenant relationship
     */

    /**
     * Helper method to create API token response with standard format
     */
    public function createApiToken(string $name = 'api-token')
    {
        $tokenResult = $this->createToken($name);

        return [
            'access_token' => $tokenResult->plainTextToken, // "tenant_id|member_id|token"
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->accessToken->expires_at?->toISOString(),
            'member' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'tenant_id' => $this->tenant_id,
            ]
        ];
    }

    /**
     * Revoke all tokens for this member
     */
    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }

    /**
     * Revoke a specific token by the plain text token
     */
    public function revokeToken(string $plainTextToken): bool
    {
        $parsed = self::parseToken($plainTextToken);

        if (!$parsed || $parsed['member_id'] !== $this->id) {
            return false;
        }

        $hashedToken = hash('sha256', $plainTextToken);

        return $this->tokens()
            ->where('token', $hashedToken)
            ->delete() > 0;
    }

    /**
     * Check if a token is valid for this member
     */
    public function isValidToken(string $plainTextToken): bool
    {
        $parsed = self::parseToken($plainTextToken);

        if (!$parsed || $parsed['member_id'] !== $this->id) {
            return false;
        }

        $hashedToken = hash('sha256', $plainTextToken);

        return $this->tokens()
            ->where('token', $hashedToken)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Validate token format without database query
     */
    public static function validateTokenFormat(string $token): bool
    {
        return self::parseToken($token) !== null;
    }

    /**
     * Get token parts for debugging
     */
    public static function debugToken(string $token): array
    {
        $parsed = self::parseToken($token);

        if (!$parsed) {
            return ['valid' => false, 'error' => 'Invalid token format'];
        }

        return [
            'valid' => true,
            'tenant_id' => $parsed['tenant_id'],
            'member_id' => $parsed['member_id'],
            'token_length' => strlen($parsed['actual_token']),
            'format' => $parsed['tenant_id'] ? 'tenant|member|token' : 'member|token'
        ];
    }
}
