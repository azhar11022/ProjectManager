<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;

class ProjectController extends Controller
{
    // fetch projects for tenant
    public function index()
    {
        $user = auth()->user();
        $tenant = $user->tenant_id;
        $projects = Project::where('tenant_id', $tenant)->get();
        return ProjectResource::collection($projects);
    }

    // create new project
    public function store(StoreProjectRequest $request)
    {
        $user = auth()->user();
        $role = $user->role;
        
        if ($role != 'admin') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $tenant = $user->tenant_id;
        $project = Project::create([
            'name' => $request->name,
            'tenant_id' => $tenant
        ]);

        return response()->json([
            'message' => 'Project created successfully',
            'project' => new ProjectResource($project)
        ], 201);
    }

    // fetch single project with tasks
    public function show($id)
    {
        $user = auth()->user();
        $tenant = $user->tenant_id;
        $project = Project::with('tasks')->where('tenant_id', $tenant)->where('id', $id)->first();
        
        if (!$project) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }
        
        return response()->json([
            'project' => new ProjectResource($project),
        ], 200);
    }

    // update project details
    public function update(UpdateProjectRequest $request, $id)
    {
        $user = auth()->user();
        $tenant = $user->tenant_id;
        $role = $user->role;
        
        if ($role != 'admin') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $project = Project::where('tenant_id', $tenant)->where('id', $id)->first();
        
        if (!$project) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }
        
        $project->update([
            'name' => $request->name
        ]);
        
        return response()->json([
            'message' => 'Project updated successfully',
            'project' => new ProjectResource($project)
        ], 200);
    }

    // delete project
    public function destroy($id)
    {
        $user = auth()->user();
        $tenant = $user->tenant_id;
        $role = $user->role;
        
        if ($role != 'admin') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $project = Project::where('tenant_id', $tenant)->where('id', $id)->first();
        
        if (!$project) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }
        
        $project->delete();
        
        return response()->json([
            'message' => 'Project deleted successfully'
        ], 200);
    }
}
