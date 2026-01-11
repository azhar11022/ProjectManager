<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    Public function show($pid,$id){
        // Show task details along with its project
        $user = auth()->user();
        $tenant = $user->tenant_id;
        // Verify project belongs to tenant
        $project = Project::where('tenant_id',$tenant)->where('id',$pid)->first();
        if(!$project){
            return response()->json(['
            message'=>'Project not found'
        ],404);
        }
        // Fetch task
        $task = Task::where('project_id',$pid)->where('id',$id)->first();
        if(!$task){
            return response()->json([
                'message'=>'Task not found'
            ],404);
        }
        return response()->json([
            'project'=>$project,
            'task'=>$task,
        ],201);
    }

    public function store(Request $request,$pid){

        $request->validate([
            'name' => 'required|string|max:255',
            'duration' => 'required|string'
        ]);
        // verify the use is admin
        $user = auth()->user();
        $role = $user->role;
        if($role != 'admin'){
            return response()->json(['message'=>'Unauthorized'],403);
        }
        // create project
        $task = Task::create([
            'name'=>$request->name,
            'duration'=>$request->duration,
            'project_id'=>$pid,
        ]);
        return response()->json(['message'=>'Task created successfully','task'=>$task],201);
    }
    public function update(Request $request, $pid,$id){
        // Update task details
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'duration' => 'sometimes|string'
        ]);
        // verify the use is admin
        $user = auth()->user();
        $role = $user->role;
        if($role != 'admin'){
            return response()->json(['message'=>'Unauthorized'],403);
        }
        // fetch task
        $task = Task::where('project_id',$pid)->where('id',$id)->first();
        if(!$task){
            return response()->json(['message'=>'Task not found'],404);
        }
        // update task
        $task->update($request->only(['name','duration']));
        return response()->json([
            'message'=>'Task updated successfully',
            'task'=>$task
        ],201);
    }
    public function destroy($pid,$id){

        $user = auth()->user();
        $role = $user->role;
        if($role != 'admin'){
            return response()->json([
                'message'=>'Unauthorized'
            ],403);
        }
        $task = Task::where('project_id',$pid)->where('id',$id)->first();
        if(!$task){
            return response()->json([
                'message'=>'Task not found'
            ],404);
        }
        $task->delete();
        return response()->json([
            'message'=>'Task deleted successfully'
        ],201);
    }
}
