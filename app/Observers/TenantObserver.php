<?php

namespace App\Observers;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class TenantObserver
{
    public function created(Tenant $tenant)
    {
        $dbName = $tenant->database_name;

        // Create DB
        if (!app()->environment('testing')) {
            DB::statement("CREATE DATABASE IF NOT EXISTS `$dbName` ");
            
            // Set Connection
            Config::set('database.connections.tenant.database', $dbName);
            DB::purge('tenant');
        }

        // Run Migrations with absolute path
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);
    }
}
