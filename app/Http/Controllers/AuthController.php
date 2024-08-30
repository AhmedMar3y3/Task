<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\LoginUserRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/register",
 *     summary="Register a new user",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="name", type="string", example="John Doe"),
 *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
 *                 @OA\Property(property="password", type="string", example="password123"),
 *                 required={"name", "email", "password"}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="user", ref="#/components/schemas/UserResourceSchema"),
 *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to send welcome email",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to send welcome email.")
 *         )
 *     )
 * )
 */
class AuthController extends Controller
{
    use HttpResponses;

    public function register(StoreUserRequest $request)
    {
        $validatedData = $request->validated();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $token = $user->createToken('Api token of ' . $user->name)->plainTextToken;

        try {
            Mail::raw("Welcome to the application, $user->name!", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Welcome to the Application');
            });
        } catch (\Exception $e) {
            Log::error('Error sending welcome email: ' . $e->getMessage());
            return $this->error([], 'Failed to send welcome email.', 500);
        }

        return $this->Success([
            'user' => $user,
            'token' => $token
        ], 'User registered successfully', 201);
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="Login a user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="password", type="string", example="password123"),
     *                 required={"email", "password"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User logged in successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/UserResourceSchema"),
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     )
     * )
     */
    public function login(LoginUserRequest $request)
    {
        $validatedData = $request->validated();
        $user = User::where('email', $request->input('email'))->first();
    
        Log::info('Login attempt for: ' . $request->input('email'));
        if (!$user) {
            Log::info('User not found');
            return $this->error([], 'Invalid credentials', 401);
        } else if (!Hash::check($request->input('password'), $user->password)) {
            Log::info('Password does not match');
            return $this->error([], 'Invalid credentials', 401);
        }
    
        $token = $user->createToken('Api token of ' . $user->name)->plainTextToken;
    
        return $this->Success([
            'user' => $user,
            'token' => $token
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Logout a user",
     *     tags={"Auth"},
     *     @OA\Response(
     *         response=200,
     *         description="User logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You have successfully logged out and your token has been deleted.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        $user = Auth::user();
        
        if ($user) {
            $user->currentAccessToken()->delete();
            return $this->Success([], $user->name . ', you have successfully logged out and your token has been deleted.');
        }

        return $this->error([], 'User not authenticated', 401);
    }


    /**
 * @OA\Post(
 *     path="/forgot-password",
 *     summary="Request a password reset code",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
 *                 required={"email"}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password reset code sent successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Password reset code sent to your email.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User not found",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="User not found")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to send password reset email",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to send password reset email.")
 *         )
 *     )
 * )
 */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $code = mt_rand(100000, 999999);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $code, 'created_at' => now()]
        );

        try {
            Mail::raw("Your password reset code is: $code", function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Password Reset Code');
            });
        } catch (\Exception $e) {
            Log::error('Error sending password reset email: ' . $e->getMessage());
            return $this->error([], 'Failed to send password reset email.', 500);
        }

        return $this->Success([], 'Password reset code sent to your email.');
    }
/**
 * @OA\Post(
 *     path="/reset-password",
 *     summary="Reset a user's password",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
 *                 @OA\Property(property="code", type="string", example="123456"),
 *                 @OA\Property(property="password", type="string", example="newpassword123"),
 *                 required={"email", "code", "password"}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password reset successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Password reset successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid reset code or email",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Invalid reset code or email")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Failed to reset password",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Failed to reset password")
 *         )
 *     )
 * )
 */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'required|min:8'
        ]);

        $reset = DB::table('password_resets')->where('email', $request->email)->where('token', $request->code)->first();
    
        if (!$reset) {
            return $this->error([], 'Invalid reset code or email', 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->error([], 'User not found', 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();
        DB::table('password_resets')->where('email', $request->email)->delete();

        return $this->Success([], 'Password reset successfully');
    }
}
