<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{


    // fetch projects for tenant
    public function index(){
        $user = auth()->user();
        $tenant = $user->tenant_id;
        $projects = Project::where('tenant_id',$tenant)->get();
        return response()->json([
            'projects'=>$projects
        ],201);
    }

    // create new project
    public function store(ProjectRequest $request){
        $request->validated();
        $user = auth()->user();
        $role = $user->role;
        if($role != 'admin'){
            return response()->json([
                'message'=>'Unauthorized'
            ],403);
        }
        $tenant = $user->tenant_id;
        $project = Project::create([
            'name'=>$request->name,
            'tenant_id'=>$tenant
        ]);
        return response()->json([
            'message'=>'Project created successfully',
            'project'=>$project
        ],201);
    }

    // fetch single project with tasks
    public function show($id){

        $user = auth()->user();
        $tenant = $user->tenant_id;
        $project = Project::where('tenant_id',$tenant)->where('id',$id)->first();
        if(!$project){
            return response()->json([
                'message'=>'Project not found'
            ],404);
        }
        return response()->json([
            'project'=>$project->toArray(),
            'tasks'=>$project->tasks
        ],201);
    }

    // update project details
    public function update(ProjectRequest $request, $id){

        $request->validated();
        $user = auth()->user();
        $tenant = $user->tenant_id;
        $role = $user->role;
        if($role != 'admin'){
            return response()->json([
                'message'=>'Project not found'
            ],404);
        }
        $project = Project::where('tenant_id',$tenant)->where('id',$id)->first();
        if(!$project){
            return response()->json([
                'message'=>'Project not found'
            ],404);
        }
        $project->update([
            'name'=>$request->name
        ]);
        return response()->json([
            'message'=>'Project updated successfully',
            'project'=>$project
        ],201);
    }

    // delete project
    public function destroy($id){

        $user = auth()->user();
        $tenant = $user->tenant_id;
        $role = $user->role;
        if($role != 'admin'){
            return response()->json([
                'message'=>'Project not found'
            ],404);
        }
        $project = Project::where('tenant_id',$tenant)->where('id',$id)->first();
        if(!$project){
            return response()->json(['
            message'=>'Project not found'
        ],404);
        }
        $project->delete();
        return response()->json([
            'message'=>'Project deleted successfully'
        ],201);
    }
}
