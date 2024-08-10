<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UserloginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Http\Requests\Auth\UserUpdateRequest;
use App\Http\Resources\Auth\UserResource;
use App\Models\Roles;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{

    public function login(UserloginRequest $request)
    {
        $data = $request->validated();
        $credentials = ['email' => $data['email'], 'password' => $data['password']];
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $userData = User::with('role')->where('email', $data['email'])->first();
        $token = JWTAuth::fromUser($userData);
        return response()->json([
            "message" => "Login Berhasil",
            "user" => new UserResource($userData),
            "token" => $token
        ]);
    }

    public function register(UserRegisterRequest $request)
    {
        $data = $request->validated();
        $roleUser = Roles::where('name', 'user')->first();
        if (!$roleUser) {
            return response()->json(['message' => 'Role user tidak ditemukan.'], 500);
        }
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'google_id' => '',
            'password' => Hash::make($data['password']),
            'role_id' => $roleUser->id,
        ]);
        $token = JWTAuth::fromUser($user);
        return response()->json([
            'message' => 'Registrasi berhasil',
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    public function getUser()
    {
        $user = auth()->user();
        $currentUser = User::with('role')->find($user->id);
        return response()->json([
            "message" => "berhasil get user",
            "data" => new UserResource($currentUser)
        ]);
    }
    
    public function updateUser(UserUpdateRequest $request)
    {
        $data = $request->validated();
        $currentUser = auth()->user();
        $userId = User::find($currentUser->id);
        $userId->name = $data['name'];
        $userId->save();
        return response()->json([
            "message" => "Berhasil Update Data",
            "user" => new UserResource($userId)
        ]);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
{
    try {
        $googleUser = Socialite::driver('google')->stateless()->user();
        $roleUser = Roles::where('name', 'user')->first();
        
        $user = User::updateOrCreate(
            ['email' => $googleUser->email],
            [
                'name' => $googleUser->name,
                'google_id' => $googleUser->id,
                'password' => bcrypt(rand(100000, 999999)),
                'role_id' => $roleUser->id,
            ]
        );

        $token = JWTAuth::fromUser($user);

        // Manually construct user data array
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => [
                'name' => $roleUser->name,
            ],
        ];
        $userJson = urlencode(json_encode($userData));
        return redirect()->away('http://localhost:5173/callback?token=' . urlencode($token) . '&user=' . $userJson);

    } catch (\Exception $e) {
        // Handle exceptions or errors
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}
