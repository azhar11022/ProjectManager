<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Member;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:seed-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed central and tenant databases with complete data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Seeding all databases...');

        // Step 1: Seed central database
        $this->seedCentralDatabase();

        // Step 2: Create tenant databases and run migrations
        $this->createTenantDatabases();

        // Step 3: Seed tenant databases with ALL data
        $this->seedTenantDatabases();

        $this->info('🎉 All databases seeded successfully!');
        $this->printLoginCredentials();

        return 0;
    }

    private function seedCentralDatabase()
    {
        $this->info('📦 Seeding central database...');

        // Clear existing data
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Tenant::truncate();
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create users
        $users = [
            [
                'name' => 'azhar',
                'email' => 'azhar@example.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'ali',
                'email' => 'ali@example.com',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $azhar = User::where('email', 'azhar@example.com')->first();
        $ali = User::where('email', 'ali@example.com')->first();

        // Create tenants
        $tenants = [
            [
                'name' => 'Tech Corp',
                'domain' => 'tech',
                'database_name' => 'db_tech',
                'is_active' => true,
                'user_id' => $azhar->id,
            ],
            [
                'name' => 'Market Pro',
                'domain' => 'market',
                'database_name' => 'db_market',
                'is_active' => true,
                'user_id' => $ali->id,
            ],
        ];

        foreach ($tenants as $tenantData) {
            Tenant::create($tenantData);
        }

        $this->info('✅ Central database seeded: 2 users, 2 tenants');
    }

    private function createTenantDatabases()
    {
        $this->info('🏗️ Creating tenant databases...');

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("   Processing: {$tenant->name} ({$tenant->database_name})");

            try {
                // Create database if not exists
                DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$tenant->database_name}`");

                // Switch to tenant database
                DB::purge('tenant');
                config(['database.connections.tenant.database' => $tenant->database_name]);
                DB::reconnect('tenant');

                // Run migrations for tenant
                $this->call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);

                $this->info("   ✅ Database created and migrated: {$tenant->database_name}");

            } catch (\Exception $e) {
                $this->error("   ❌ Failed: " . $e->getMessage());
            }
        }
    }

    private function seedTenantDatabases()
    {
        $this->info('🌱 Seeding tenant databases with all data...');

        // Get tenants from CENTRAL database
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("\n   📦 Seeding: {$tenant->name}");

            try {
                // Get user from CENTRAL database BEFORE switching
                $user = User::find($tenant->user_id);

                if (!$user) {
                    $this->error("   ❌ User not found for tenant: {$tenant->name}");
                    continue;
                }

                // Switch to tenant database
                DB::purge('tenant');
                config(['database.connections.tenant.database' => $tenant->database_name]);
                DB::reconnect('tenant');

                // Set as default connection
                config(['database.default' => 'tenant']);
                DB::setDefaultConnection('tenant');

                // Clear existing data
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                Member::truncate();
                Project::truncate();
                Task::truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                // 1. Create admin member (use password from central user)
                Member::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $user->password, // Use same password hash
                    'tenant_id' => $tenant->id,
                    'role' => 'admin',
                ]);

                $this->info("   ✅ Created admin member: {$user->email}");

                // 2. Create 2 member users (as per requirements)
                for ($i = 1; $i <= 2; $i++) {
                    Member::create([
                        'name' => "Member {$i}",
                        'email' => "member{$i}@{$tenant->domain}.com",
                        'password' => Hash::make('password123'),
                        'tenant_id' => $tenant->id,
                        'role' => 'member',
                    ]);
                }

                $this->info("   ✅ Created 2 member users");

                // 3. Create 2 projects (as per requirements)
                $projects = [];
                for ($i = 1; $i <= 2; $i++) {
                    $project = Project::create([
                        'name' => "{$tenant->name} Project {$i}",
                        'tenant_id' => $tenant->id,
                    ]);
                    $projects[] = $project;
                }

                $this->info("   ✅ Created 2 projects");

                // 4. Create 5 tasks per project (as per requirements)
                $taskCount = 0;
                foreach ($projects as $project) {
                    for ($i = 1; $i <= 5; $i++) {
                        Task::create([
                            'name' => "Task {$i} - {$project->name}",
                            'duration' => rand(1, 10) . ' days',
                            'project_id' => $project->id,
                        ]);
                        $taskCount++;
                    }
                }

                $this->info("   ✅ Created {$taskCount} tasks (5 per project)");

                // Summary for this tenant
                $this->info("   📊 Summary for {$tenant->name}:");
                $this->info("      • Members: " . Member::count());
                $this->info("      • Projects: " . Project::count());
                $this->info("      • Tasks: " . Task::count());

            } catch (\Exception $e) {
                $this->error("   ❌ Failed to seed tenant: " . $e->getMessage());
                $this->error("   Stack: " . $e->getTraceAsString());
            }

            // Switch back to central database for next iteration
            DB::purge('mysql');
            DB::reconnect('mysql');
            config(['database.default' => 'mysql']);
            DB::setDefaultConnection('mysql');
        }
    }

    private function printLoginCredentials()
    {
        $this->info("\n🔐 LOGIN CREDENTIALS:");
        $this->info("====================");

        // Get tenants from CENTRAL database
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("\n📋 Tenant: {$tenant->name}");
            $this->info("   Domain: {$tenant->domain}");
            $this->info("   Database: {$tenant->database_name}");
            $this->info("   Users:");

            // Get user from central DB
            $user = User::find($tenant->user_id);
            if ($user) {
                $this->info("      👑 Admin: {$user->email} / password123");
            }

            // Show member users (we know these from our seeding logic)
            $this->info("      👤 Member: member1@{$tenant->domain}.com / password123");
            $this->info("      👤 Member: member2@{$tenant->domain}.com / password123");
        }

        $this->info("\n🌐 Central Database Users:");
        $this->info("   👤 azhar@example.com / password123");
        $this->info("   👤 ali@example.com / password123");
    }
}
