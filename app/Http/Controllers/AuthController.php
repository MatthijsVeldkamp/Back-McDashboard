<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Generate avatar based on initials
        $this->generateAvatar($user);

        return response()->json([
            'message' => 'User registered successfully'
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required'
        ]);

        // Find user by username first
        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Ongeldige inloggegevens'
            ], 401);
        }

        // Create token and authenticate user
        $token = $user->createToken('auth_token')->plainTextToken;
        Auth::login($user);

        // Return full user data with token
        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'profile_picture' => $user->profile_picture ? Storage::url($user->profile_picture) : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'roles' => $user->roles->map(function($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description
                    ];
                })
            ],
            'message' => 'Login successful',
            'status' => 'authenticated'
        ]);
    }

    /**
     * Generate avatar based on user's initials using DiceBear API
     */
    private function generateAvatar(User $user)
    {
        try {
            $initials = strtoupper(substr($user->username, 0, 2));
            $filename = "{$user->username}_{$user->id}.png";
            $path = "profile_pictures/{$filename}";

            // Use DiceBear API to generate avatar
            $response = Http::get("https://api.dicebear.com/7.x/initials/svg", [
                'seed' => $initials,
                'backgroundColor' => 'b6e3f4',
                'textColor' => 'ffffff',
                'size' => 200
            ]);

            if ($response->successful()) {
                // Convert SVG to PNG using a simple API
                $svgToPngResponse = Http::post('https://api.svg2png.com/v1', [
                    'svg' => $response->body(),
                    'width' => 200,
                    'height' => 200
                ]);

                if ($svgToPngResponse->successful()) {
                    // Save the PNG image
                    Storage::disk('public')->put($path, $svgToPngResponse->body());
                    
                    // Update user's profile picture path
                    $user->profile_picture = $path;
                    $user->save();
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to generate avatar: ' . $e->getMessage());
        }
    }
}
