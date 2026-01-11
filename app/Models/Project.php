<?php

namespace App\Models;

use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $connection = 'tenant';

    protected $fillable = ['name','tenant_id'];

    public function db():BelongsTo{
        return $this->belongsTo(Tenant::class);
    }
    public function tasks():HasMany{
        return $this->hasMany(Task::class);
    }
}
