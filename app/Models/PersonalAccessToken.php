<?php
// app/Models/PersonalAccessToken.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PersonalAccessToken extends Model
{
    protected $connection = 'tenant';

    protected $table = 'personal_access_tokens';

    protected $fillable = [
        'tokenable_type',
        'tokenable_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at'
    ];

    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the tokenable model (Member)
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Find the token instance matching the given token
     * Supports both formats:
     * 1. Original Sanctum format: "id|token" (for backward compatibility)
     * 2. Our custom format: "tenant_id|member_id|token"
     */
    public static function findToken($token)
    {
        if (empty($token)) {
            return null;
        }

        // Check if it's our custom format (contains two | separators)
        $separatorCount = substr_count($token, '|');

        if ($separatorCount === 2) {
            // Custom format: "tenant_id|member_id|actual_token"
            return static::findTokenByCustomFormat($token);
        }

        // Original Sanctum format: "id|token" or plain token
        if (strpos($token, '|') === false) {
            return static::where('token', hash('sha256', $token))->first();
        }

        [$id, $token] = explode('|', $token, 2);

        if ($instance = static::find($id)) {
            return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
        }

        return null;
    }

    /**
     * Find token using custom format: "tenant_id|member_id|actual_token"
     */
    private static function findTokenByCustomFormat($token)
    {
        // Split: tenant_id|member_id|actual_token
        $parts = explode('|', $token, 3);

        if (count($parts) !== 3 || !is_numeric($parts[0]) || !is_numeric($parts[1]) || empty($parts[2])) {
            return null;
        }

        $tenantId = (int) $parts[0];
        $memberId = (int) $parts[1];
        $actualToken = $parts[2];

        // Hash the full token for comparison
        $hashedToken = hash('sha256', $token);

        // Find token by hash and verify it belongs to the correct member
        $instance = static::where('token', $hashedToken)
            ->where('tokenable_id', $memberId)
            ->where('tokenable_type', \App\Models\Member::class)
            ->first();

        if (!$instance) {
            return null;
        }

        // Additional verification: check if member belongs to the correct tenant
        // This assumes you'll handle tenant switching elsewhere
        return $instance;
    }

    /**
     * Alternative method: Find token by member ID and token string
     * Useful when you already know the member ID
     */
    public static function findTokenForMember($memberId, $token)
    {
        // Parse token to check format
        $separatorCount = substr_count($token, '|');

        if ($separatorCount === 2) {
            // Custom format: extract actual token part
            $parts = explode('|', $token, 3);
            if (count($parts) === 3) {
                $actualToken = $parts[2];
                $hashedToken = hash('sha256', $token);
            } else {
                return null;
            }
        } else {
            // Plain token or id|token format
            $hashedToken = hash('sha256', $token);
        }

        return static::where('token', $hashedToken)
            ->where('tokenable_id', $memberId)
            ->where('tokenable_type', \App\Models\Member::class)
            ->first();
    }

    /**
     * Check if token is expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if token can perform specific ability
     */
    public function can($ability)
    {
        if (in_array('*', $this->abilities)) {
            return true;
        }

        return in_array($ability, $this->abilities);
    }

    /**
     * Check if token cannot perform specific ability
     */
    public function cant($ability)
    {
        return !$this->can($ability);
    }

    /**
     * Get the member ID (convenience method)
     */
    public function getMemberIdAttribute()
    {
        return $this->tokenable_id;
    }
}
