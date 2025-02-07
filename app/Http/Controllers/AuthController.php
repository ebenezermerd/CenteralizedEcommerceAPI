<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterationRequest;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\ApiResponses;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\LogActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Services\EmailVerificationService;

class AuthController extends Controller
{
    use ApiResponses;

    protected $emailVerificationService;
    protected $ACCESS_TOKEN_TTL;
    protected $REFRESH_TOKEN_TTL;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
        $this->ACCESS_TOKEN_TTL = 60;
        $this->REFRESH_TOKEN_TTL = 10080;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/sign-up",
     *     summary="Register user",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", enum={"customer", "supplier"}),
     *         example="customer",
     *         description="Role of the user. If 'supplier' is selected, additional company information fields are required."
     *     ),
     *     @OA\Parameter(
     *         name="firstName",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example="John"
     *     ),
     *     @OA\Parameter(
     *         name="lastName",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example="Doe"
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="email"),
     *         example="user@example.com"
     *     ),
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example="123-456-7890"
     *     ),
     *     @OA\Parameter(
     *         name="sex",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", enum={"male", "female"}),
     *         example="male"
     *     ),
     *     @OA\Parameter(
     *         name="address",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         example="123 Main St"
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="password"),
     *         example="password123"
     *     ),
     *     @OA\Parameter(
     *         name="password_confirmation",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="password"),
     *         example="password123"
     *     ),
     *     @OA\Parameter(
     *         name="companyName",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="Example Company",
     *         description="Required if role is 'supplier'."
     *     ),
     *     @OA\Parameter(
     *         name="description",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="Company description",
     *         description="Required if role is 'supplier'."
     *     ),
     *     @OA\Parameter(
     *         name="companyEmail",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="email"),
     *         example="company@example.com",
     *         description="Required if role is 'supplier'."
     *     ),
     *     @OA\Parameter(
     *         name="companyPhone",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="123-456-7890",
     *         description="Required if role is 'supplier'."
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="USA",
     *         description="Required if role is 'supplier'."
     *     ),
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="New York",
     *         description="Required if role is 'supplier'."
     *     ),
     *     @OA\Parameter(
     *         name="companyAddress",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="456 Business Rd",
     *         description="Required if role is 'supplier'."
     *     ),
     *     @OA\Parameter(
     *         name="agreement",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean"),
     *         example=true,
     *         description="Required if role is 'supplier'."
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successful registration",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="firstName", type="string", example="John"),
     *                 @OA\Property(property="lastName", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *         )
     *     )
     * )
     */
    public function register(RegisterationRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $user = User::create([
                'firstName' => $validatedData['firstName'],
                'lastName' => $validatedData['lastName'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'sex' => $validatedData['sex'],
                'address' => $validatedData['address'],
                'password' => Hash::make($validatedData['password']),
                'verified' => $validatedData['verified'] ?? false,
                'status' => 'pending'
            ]);

            // Assign role and its permissions using Spatie
            $user->assignRole($validatedData['role']);

            if ($validatedData['role'] === 'supplier') {
                if (!$validatedData['agreement']) {
                    return response()->json(['error' => 'You must agree to the terms!'], 400);
                }

                $company = Company::create([
                    'name' => $validatedData['companyName'],
                    'description' => $validatedData['description'],
                    'email' => $validatedData['companyEmail'],
                    'phone' => $validatedData['companyPhone'],
                    'country' => $validatedData['country'],
                    'city' => $validatedData['city'],
                    'address' => $validatedData['companyAddress'],
                    'agreement' => $validatedData['agreement'],
                    'owner_id' => $user->id,
                    'status' => 'pending'
                ]);
                $user->save();
            }

              // Send email verification
              $this->emailVerificationService->sendVerificationEmail($user);

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $validatedData['role'],
                'ip' => $request->ip()
            ]);

            return response()->json([
                'message' => 'Registration successful! Please verify your email.',
                // 'user' => new UserResource($user)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
                'ip' => $request->ip()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/sign-in",
     *     summary="Login user",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="email"),
     *         example="user@example.com"
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="password"),
     *         example="password123"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
    */
    public function login(LoginRequest $request)
    {
        try {

            $credentials = $request->validated();

            // Set TTL for access token to 60 minutes
            JWTAuth::factory()->setTTL($this->ACCESS_TOKEN_TTL);

            if (!$token = JWTAuth::attempt($credentials)) {
                Log::channel('telescope')->warning('Failed login attempt', [
                    'email' => $request->input('email'),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json(['error' => 'Invalid email or password'], 401);
            }

            $user = Auth::user();
            // Generate refresh token with 1 week TTL
            JWTAuth::factory()->setTTL($this->REFRESH_TOKEN_TTL);
            $refreshToken = JWTAuth::fromUser($user);
            // $refreshToken = auth()->refresh();

            // Check if user is verified
            if (!$user->verified) {
                // Send verification email
                $this->emailVerificationService->sendVerificationEmail($user);

                return response()->json([
                    'status' => 'verification_required',
                    'accessToken' => $token,
                    'refreshToken' => $refreshToken,
                    'message' => 'Please verify your email address. A verification code has been sent to your email.',
                    'user' => [
                        'email' => $user->email,
                        'role' => $user->getRoleNames()->first(),
                        'isVerified' => $user->verified,
                        'id' => $user->id
                    ]
                ], 203);
            }

            // Check if MFA is enabled
            if ($user->is_mfa_enabled) {
                $tempToken = JWTAuth::fromUser($user, ['exp' => now()->addMinutes(60)->timestamp]);
                Log::info('MFA required for user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip()
                ]);
                $this->emailVerificationService->sendMfaOtp($user);

                return response()->json([
                    'status' => 'mfa_required',
                    'mfaRequired' => true,
                    'message' => 'MFA verification required',
                    'tempToken' => $tempToken,
                    'user' => [
                        'email' => $user->email,
                        'id' => $user->id,
                        'role' => $user->getRoleNames()->first(),
                        'isVerified' => $user->verified
                    ]
                ], 200);
            }


            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'status' => 'success',
                'accessToken' => $token,
                'refreshToken' => $refreshToken,
                'user' => new UserResource($user),
                'role' => $user->getRoleNames()->first(),
                'mfaRequired' => $user->is_mfa_enabled,
                'expires_in' => 15 * 60, // 15 minutes in seconds
                'refresh_expires_in' => 10080 * 60 // 1 week in seconds
            ], 200);

        } catch (JWTException $e) {
            Log::error('JWT token creation failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            return response()->json(['message' => 'Authentication failed'], 500);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            return response()->json(['message' => 'Login failed'], 500);
        }
    }

    public function refresh()
    {
        try {
            $oldToken = JWTAuth::getToken();

            if (!$oldToken) {
                return response()->json(['error' => 'Refresh token not provided'], 401);
            }

            // Verify the refresh token
            $refreshToken = JWTAuth::setToken($oldToken);
            $payload = $refreshToken->getPayload();

            // Check if the token is actually a refresh token
            if (!isset($payload['refresh']) || !$payload['refresh']) {
                return response()->json(['error' => 'Invalid refresh token'], 401);
            }

            // Generate new access token with 60 minutes TTL
            JWTAuth::factory()->setTTL($this->ACCESS_TOKEN_TTL);
            $newToken = JWTAuth::fromUser(Auth::user());

            return response()->json([
                'status' => 'success',
                'accessToken' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => $this->ACCESS_TOKEN_TTL * 60, // 60 minutes in seconds
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Get authenticated user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="firstName", type="string", example="John"),
     *             @OA\Property(property="lastName", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", example="user@example.com")
     *         )
     *     )
     * )
     */
    public function getUser()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            return response()->json([
                'user' => new UserResource($user),
                'mfaEnabled' => $user->is_mfa_enabled,
                'mfaVerified' => !empty($user->mfa_verified_at)
            ]);

        } catch (JWTException $e) {
            Log::error('Token validation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Invalid token'], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        try {
            $user = Auth::user();
            Log::info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            Log::error('Logout error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Logout failed'], 500);
        }
    }
}
