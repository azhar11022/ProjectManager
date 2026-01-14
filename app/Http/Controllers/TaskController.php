<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;

class TaskController extends Controller
{
    public function show($pid, $id)
    {
        // Show task details along with its project
        $user = auth()->user();
        $tenant = $user->tenant_id;

        // Verify project belongs to tenant
        $project = Project::where('tenant_id', $tenant)->where('id', $pid)->first();
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        // Fetch task
        $task = Task::where('project_id', $pid)->where('id', $id)->first();
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        return response()->json([
            'project' => new ProjectResource($project),
            'task' => new TaskResource($task),
        ], 200);
    }

    public function store(StoreTaskRequest $request, $pid)
    {
        // verify the use is admin
        $user = auth()->user();
        $role = $user->role;

        if ($role != 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify project existence for tenant (safety check)
        $tenant = $user->tenant_id;
        $project = Project::where('tenant_id', $tenant)->where('id', $pid)->exists();
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        // create task
        $task = Task::create([
            'name' => $request->name,
            'duration' => $request->duration,
            'project_id' => $pid,
        ]);

        return response()->json([
            'message' => 'Task created successfully',
            'task' => new TaskResource($task)
        ], 201);
    }

    public function update(UpdateTaskRequest $request, $pid, $id)
    {
        // verify the use is admin
        $user = auth()->user();
        $role = $user->role;

        if ($role != 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // fetch task
        $tenant = $user->tenant_id;
        $project = Project::where('tenant_id', $tenant)->where('id', $pid)->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $task = Task::where('project_id', $pid)->where('id', $id)->first();

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        // update task
        $task->update($request->only(['name', 'duration']));

        return response()->json([
            'message' => 'Task updated successfully',
            'task' => new TaskResource($task)
        ], 200);
    }

    public function destroy($pid, $id)
    {
        $user = auth()->user();
        $role = $user->role;

        if ($role != 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tenant = $user->tenant_id;
        $project = Project::where('tenant_id', $tenant)->where('id', $pid)->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $task = Task::where('project_id', $pid)->where('id', $id)->first();

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }
}
