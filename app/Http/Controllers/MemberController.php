<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    // fetch members for tenant
    public function index()
    {
        $user = auth()->user();
        $tenant = $user->tenant_id;
        $members = Member::where('tenant_id', $tenant)->get();
        return MemberResource::collection($members);
    }

    // create new member
    public function store(StoreMemberRequest $request)
    {
        $user = auth()->user();

        // Authorization check
        if ($user->role != 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tenant = $user->tenant_id;
        $domain = auth()->user()->tenant;

        // Mutate email to include domain
        $email = $request->email . "@" . $domain . ".com";

        if (Member::where('email', $email)->exists()) {
            return response()->json(['message' => 'Email already exists'], 409);
        }

        $member = Member::create([
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenant,
            'role' => $request->role ?? 'member'
        ]);

        return response()->json([
            'message' => 'Member created successfully',
            'member' => new MemberResource($member)
        ], 201);
    }

    public function show($id)
    {
        $user = auth()->user();
        $tenant = $user->tenant_id;

        $member = Member::where('tenant_id', $tenant)->where('id', $id)->first();

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        if ($user->role != 'admin' && $user->id != $id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['member' => new MemberResource($member)], 200);
    }

    public function update(UpdateMemberRequest $request, $id)
    {
        $user = auth()->user();
        $tenant = $user->tenant_id;
        $domain = auth()->user()->tenant;

        $member = Member::where('tenant_id', $tenant)->where('id', $id)->first();

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        if ($user->role != 'admin' && $user->id != $id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Email check logic
        if ($request->filled('email')) {
            $email = $request->email . "@" . $domain . ".com";
            if (Member::where('email', $email)->where('id', '!=', $id)->exists()) {
                return response()->json(['message' => 'Email already exists'], 409);
            }
            if ($user->role == 'admin') {
                $member->email = $email;
            }
        }

        if ($request->filled('name')) {
            $member->name = $request->name;
        }

        if ($request->filled('password')) {
            $member->password = Hash::make($request->password);
        }

        if ($user->role == 'admin' && $request->filled('role')) {
            $member->role = $request->role;
        }

        $member->save();

        return response()->json([
            'message' => 'Member updated successfully',
            'member' => new MemberResource($member)
        ], 200);
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if ($user->role != 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tenant = $user->tenant_id;
        $member = Member::where('tenant_id', $tenant)->where('id', $id)->first();

        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        $member->delete();

        return response()->json(['message' => 'Member deleted successfully'], 200);
    }
}
