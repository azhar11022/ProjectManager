<?php

namespace App\Models;

use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = ['name','duration','project_id'];

    public function project():BelongsTo{
        return $this->belongsTo(Project::class);
    }
}
