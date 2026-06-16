<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereKeyNot($request->user()->id);

        if ($request->filled('search') && $request->filled('field')) {
            $alloedFields = [
                'name',
                'email',
                'role'
            ];

            if (in_array($request->field ,$alloedFields)) {
                $query->where($request->field,'like','%'.$request->search.'%');
            }
        }

        return $query
            ->latest()
            ->paginate($request->integer('per_page', 10));
    }

    public function store(Request $request) 
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',            
            'role' => 'required|in:admin,user',            
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user,
        ], 201);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot delete yourself'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    public function export(Request $request)
    {
        $users = User::whereKeyNot($request->user()->id)
            ->latest()
            ->get();
        
        $fileName = "users.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename{$fileName}",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                '#',
                'Name',
                'Email',
                'Role',
                'Created At'
            ]);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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