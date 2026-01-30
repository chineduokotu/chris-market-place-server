<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function switchRole(Request $request)
    {
        $user = $request->user();
        $newRole = $user->current_role === 'seeker' ? 'provider' : 'seeker';
        $user->update(['current_role' => $newRole]);

        return response()->json([
            'message' => 'Role switched successfully',
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
        ]);

        $request->user()->update($request->only(['name', 'phone', 'whatsapp_number']));

        return response()->json($request->user());
    }
}
