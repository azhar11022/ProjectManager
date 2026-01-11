<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class TenantManager
{
    public static function switchToTenant($dbName)
    {
        // Change the database name in the 'tenant' connection config
        Config::set('database.connections.tenant.database', $dbName);

        // Purge and reconnect to apply changes
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Set 'tenant' as the default connection for all models
        DB::setDefaultConnection('tenant');
    }

    // This helps your partner! It creates the DB if it's missing.
    // public static function createTenantDatabase($dbName)
    // {
    //     DB::statement("CREATE DATABASE IF NOT EXISTS `$dbName` ");
    // }
}
