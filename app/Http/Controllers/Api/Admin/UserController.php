<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return User::latest()
            ->paginate(10);
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        if ($request->user()->id === $user->id && $request->role === 'user') {
            return response()->json([
                'message' => 'You cannot remove your own admin role'
            ], 422);
        }

        $user->update([
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $user,
        ]);
    }
}