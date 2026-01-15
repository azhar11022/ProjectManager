<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use Laravel\Sanctum\Sanctum;

class ApiCheckTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_api_flow()
    {
        // 1. Register User & Tenant
        $userData = [
            'name' => 'Test Admin',
            'email' => 'admin@company.com',
            'password' => 'password',
            'company_name' => 'Company One',
            'company_short_code' => 'companyone',
        ];

        $response = $this->postJson('/api/register', $userData);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'access_token',
                'domain'
            ]);

        // 2. Login
        $loginData = [
            'email' => 'admin@company.com',
            'password' => 'password',
        ];

        $response = $this->postJson('/api/login', $loginData);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'role',
                'database'
            ]);
        
        $token = $response->json('access_token');

        // 3. Create Member
        $memberData = [
            'name' => 'New Member',
            'email' => 'member', // will be appended with domain
            'password' => 'password',
            'role' => 'member'
        ];

        $response = $this->withToken($token)->postJson('/api/members', $memberData);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'member' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'created_at',
                    'updated_at'
                ]
            ]);

        // 4. Create Project
        $projectData = [
            'name' => 'New Project'
        ];

        $response = $this->withToken($token)->postJson('/api/projects', $projectData);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'project' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $projectId = $response->json('project.id');

        // 5. Create Task
        $taskData = [
            'name' => 'New Task',
            'duration' => 5
        ];

        // Route: /projects/{pid}/tasks
        $response = $this->withToken($token)->postJson("/api/projects/{$projectId}/tasks", $taskData);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'task' => [
                    'id',
                    'name',
                    'duration',
                    'project_id',
                    'created_at',
                    'updated_at'
                ]
            ]);
        
        $taskId = $response->json('task.id');

        // 6. Verify Project with Tasks
        $response = $this->withToken($token)->getJson("/api/projects/{$projectId}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'project' => [
                    'id',
                    'name',
                    'tasks' => [
                        '*' => [
                            'id', 'name', 'duration'
                        ]
                    ]
                ]
            ]);
    }
}
