<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    // fetch members for tenant
    public function index(){
        $user = auth()->user();
        $tenant = $user->tenant_id;
        $members = Member::where('tenant_id',$tenant)->get();
        return response()->json([
            'members'=>$members
        ],201);
    }

    // create new member
    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|string|in:admin,member',
        ]);
        $user = auth()->user();
        $tenant = $user->tenant_id;

        $tenantTable = Tenant::find($tenant);

        $request->email = $request->email."@".$tenantTable->domain.".com";
        $member = Member::where('email',$request->email)->first();
        if($member){
            return response()->json([
                'message'=>'Email already exists'
            ],409);
        }

        $role = $user->role;
        if($role != 'admin'){
            return response()->json([
                'message'=>'Project not found'
            ],404);
        }
        $member = Member::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'tenant_id'=>$tenant,
            'role'=>$request->role ?? 'member'
        ]);
        return response()->json([
            'message'=>'Member created successfully',
            'member'=>$member
        ],201);
    }


    public function show($id){

        $user = auth()->user();
        $tenant = $user->tenant_id;
        if($user->role != 'admin' && $user->id != $id){
            return response()->json([
                'message'=>'Project not found'
            ],404);
        }
        $member = Member::where('tenant_id',$tenant)->where('id',$id)->first();
        if(!$member){
            return response()->json([
                'message'=>'Member not found'
            ],404);
        }
        return response()->json([
            'member'=>$member
        ],201);
    }


    public function update(Request $request, $id){

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|max:255,'.$id,
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'sometimes|string|in:admin,member',
        ]);

        $user = auth()->user();
        $tenant = $user->tenant_id;
        $tenantTable = Tenant::find($tenant);

        $request->email = $request->email ? $request->email."@".$tenantTable->domain.".com" : null;
        $member = Member::where('email',$request->email)->where('id','!=',$id)->first();

        if($member){
            return response()->json([
                'message'=>'Email already exists'
            ],409);
        }
        if($user->role != 'admin' && $user->id != $id){
            return response()->json([
                'message'=>'Project not found'
            ],404);
        }
        $member = Member::where('tenant_id',$tenant)->where('id',$id)->first();
        if(!$member){
            return response()->json([
                'message'=>'Member not found'
            ],404);
        }
        $member->name = $request->name ?? $member->name;
        if($user->role == 'admin'){
            $member->email = $request->email;
        }
        if($request->password){
            $member->password = Hash::make($request->password);
        }
        if($user->role == 'admin' && $request->role){
            $member->role = $request->role;
        }
        $member->save();
        return response()->json([
            'message'=>'Member updated successfully',
            'member'=>$member
        ],201);
    }


    public function destroy($id){

        $user = auth()->user();
        $tenant = $user->tenant_id;
        $role = $user->role;
        if($role != 'admin'){
            return response()->json([
                'message'=>'Project not found'
            ],404);
        }
        $member = Member::where('tenant_id',$tenant)->where('id',$id)->first();
        if(!$member){
            return response()->json([
                'message'=>'Member not found'
            ],404);
        }
        $member->delete();
        return response()->json([
            'message'=>'Member deleted successfully'
        ],201);
    }
}
