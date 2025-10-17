<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Laravel Blog API",
 *      description="API Documentation for Blog"
 * )
 */
class AuthController extends Controller
{
    /**
     * Handle user registration request.
     * - This function will be used to handle user registration.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     operationId="registerUser",
     *     tags={"Auth"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","confirm_password"},
     *             @OA\Property(property="name", type="string", example="Parth Parmar"),
     *             @OA\Property(property="email", type="string", format="email", example="parth@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful registration",
     *        @OA\JsonContent(
     *            @OA\Property(property="success", type="boolean", example=true),
     *            @OA\Property(property="message", type="string", example="User registered successfully"),
     *            @OA\Property(property="data", type="object",
     *              @OA\Property(property="access_token", type="string", example="1|qwertyuiopasdfghjklzxcvbnm1234567890"),
     *              @OA\Property(property="token_type", type="string", example="Bearer"),
     *              @OA\Property(property="user", type="object", 
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Parth Parmar"),
     *                 @OA\Property(property="email", type="string", format="email", example="parth@example.com"),
    *                  @OA\Property(property="role", type="string", example="author"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
     *                @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"))
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *        @OA\JsonContent(  
     *           @OA\Property(property="success", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="Validation Error"),
     *           @OA\Property(property="errors", type="object",
     *              @OA\Property(property="email", type="array", 
     *                  @OA\Items(type="string", example="The email has already been taken.")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function register(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return apiResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 'User registered successfully', 200);

    }

    /**
     * Handle user login request.
     * - This function will be used to handle user login.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     operationId="loginUser",
     *     tags={"Auth"},
     *     summary="Login a user",
     *     description="Authenticate a user and return an access token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="parth@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *        @OA\JsonContent(
     *            @OA\Property(property="success", type="boolean", example=true),
     *            @OA\Property(property="message", type="string", example="User registered successfully"),
     *            @OA\Property(property="data", type="object",
     *              @OA\Property(property="access_token", type="string", example="1|qwertyuiopasdfghjklzxcvbnm1234567890"),
     *              @OA\Property(property="token_type", type="string", example="Bearer"),
     *              @OA\Property(property="user", type="object", 
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Parth Parmar"),
     *                 @OA\Property(property="email", type="string", format="email", example="parth@example.com"),
    *                  @OA\Property(property="role", type="string", example="admin"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
     *                @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"))
     *              ),
     *          )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return apiResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 'User logged in successfully', 200);

    }

   /**
    * Handle user logout request.
    * - This function will be used to handle user logout.
    * 
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\JsonResponse
    */

   /**
    * @OA\Post(
    *     path="/api/v1/logout",
    *     operationId="logoutUser",
    *     tags={"Auth"},
    *     summary="Logout a user",
    *     description="Revoke the authenticated user's access token",
    *     security={{"sanctum": {}}},
    *     @OA\Response(
    *         response=200,
    *         description="Successful logout",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="message", type="string", example="User logged out successfully")
    *         )
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Unauthorized",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=false),
    *             @OA\Property(property="message", type="string", example="Unauthenticated")
    *         )
    *     )
    * )
    */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return apiResponse(null, 'User logged out successfully', 200);
    }

    /**
     * Get the authenticated user's details.
     * - This function will return the details of the authenticated user.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @OA\Get(
     *     path="/api/v1/user",
     *     operationId="getCurrentUser",
     *     tags={"Auth"},
     *     summary="Get current authenticated user details",
     *     description="Retrieve details of the authenticated user",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful retrieval of user details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User details fetched successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Parth Parmar"),
     *                 @OA\Property(property="email", type="string", format="email", example="parth@example.com"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
     *                @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
     *            )
     *        )
     *    ),
     *    @OA\Response(
     *        response=401,
     *        description="Unauthorized",
     *       @OA\JsonContent(
     *            @OA\Property(property="success", type="boolean", example=false),
     *           @OA\Property(property="message", type="string", example="Unauthenticated")
     *       )
     *   )
     * )
     */
    public function getCurrentUserDetails(Request $request)
    {
        return apiResponse($request->user(), 'User details fetched successfully', 200);
    }
}
