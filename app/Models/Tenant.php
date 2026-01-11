<?php

namespace App\Models;

use App\Models\User;
use App\Models\Member;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    protected $fillable = ['name', 'domain', 'database_name', 'is_active','user_id'];

    public function user():BelongsTo{
        return $this->belongsTo(User::class);
    }
    public function members():HasMany{
        return $this->hasMany(Member::class);
    }
    public function projects():HasMany{
        return $this->hasMany(Project::class);
    }
}
