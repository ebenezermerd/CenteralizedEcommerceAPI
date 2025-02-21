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
use App\Models\AddressBook;
use App\Services\EmailVerificationService;

/**
 * @group Authentication
 *
 * APIs for managing authentication
 */
class AuthController extends Controller
{
    use ApiResponses;

    protected $emailVerificationService;
    protected $ACCESS_TOKEN_TTL;
    protected $REFRESH_TOKEN_TTL;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
        $this->ACCESS_TOKEN_TTL = 2880; // 2 days in minutes
        $this->REFRESH_TOKEN_TTL = 10080; // 7 days in minutes
    }

    /**
     * Register a new user
     *
     * Create a new user account with the provided details.
     *
     * @param RegisterationRequest $request
     *
     * @bodyParam firstName string required The user's first name. Example: John
     * @bodyParam lastName string required The user's last name. Example: Doe
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password (min: 8 characters). Example: Secret123!
     * @bodyParam phone string required The user's phone number. Example: +1234567890
     * @bodyParam sex string required The user's gender (male/female). Example: male
     * @bodyParam address string required The user's address. Example: 123 Main St
     * @bodyParam role string required User role (customer/supplier). Example: customer
     * @bodyParam agreement boolean required when role is supplier Terms agreement flag. Example: true
     * @bodyParam companyName string required when role is supplier Company name. Example: Tech Corp
     * @bodyParam companyEmail string required when role is supplier Company email. Example: info@techcorp.com
     * @bodyParam companyPhone string required when role is supplier Company phone number. Example: +1234567890
     * @bodyParam country string required when role is supplier Company country. Example: USA
     * @bodyParam city string required when role is supplier Company city. Example: Los Angeles
     * @bodyParam companyAddress string required when role is supplier Company address. Example: 456 Tech St
     * @bodyParam description string required when role is supplier Company description. Example: Leading tech company
     *
     * @response 201 {
     *  "message": "Registration successful! Please login in and verify your email."
     * }
     * @response 400 {
     *  "error": "You must agree to the terms!"
     * }
     * @response 422 {
     *  "message": "The given data was invalid",
     *  "errors": {
     *    "email": ["The email has already been taken."]
     *  }
     * }
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

            $addressBook = AddressBook::create([
                'user_id' => $user->id,
                'name' => $user->firstName . ' ' . $user->lastName,
                'email' => $user->email,
                'phone_number' => $user->phone,
                'address_type' => 'other',
                'full_address' => $user->address,
                'is_primary' => true
            ]);

            //   // Send registration email
            $this->emailVerificationService->sendRegistrationEmail($user);


            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $validatedData['role'],
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Please login and verify your email.',
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
     * User Login
     *
     * Authenticate a user and retrieve tokens.
     *
     * @param LoginRequest $request
     *
     * @bodyParam email string required User's email address. Example: john@example.com
     * @bodyParam password string required User's password. Example: Secret123!
     *
     * @response 200 {
     *  "status": "success",
     *  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *  "refreshToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *  "user": {
     *    "id": "1",
     *    "firstName": "John",
     *    "lastName": "Doe",
     *    "email": "john@example.com",
     *    "role": "customer",
     *    "status": "active",
     *    "avatarUrl": "http://example.com/storage/avatar.jpg",
     *    "phoneNumber": "+1234567890",
     *    "address": "123 Main St",
     *    "country": "USA",
     *    "state": "California",
     *    "city": "Los Angeles",
     *    "sex": "male",
     *    "about": "About John Doe",
     *    "zipCode": "90001",
     *    "company": "Tech Corp",
     *    "isVerified": true,
     *    "created_at": "2023-01-01T00:00:00Z",
     *    "updated_at": "2023-01-01T00:00:00Z"
     *  },
     *  "expires_in": 900
     * }
     *
     * @response 201 {
     *   "status": "mfa_required",
     *   "mfaRequired": true,
     *   "message": "MFA verification required",
     *   "tempToken": "string",
     *   "user": {
     *     "email": "string",
     *     "id": "integer",
     *     "role": "string",
     *     "isVerified": "boolean"
     *   }
     * }
     *
     * @response 401 {
     *  "error": "Invalid email or password"
     * }
     * @response 203 {
     *  "status": "verification_required",
     *  "message": "Please verify your email address"
     * }
     *
     * @response 500 {
     *   "message": "Authentication failed"
     * }
     *
     * @response 501 {
     *   "message": "Login failed"
     * }
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

                return response()->json([
                    'success' => false,
                    'status' => 'invalid_credentials',
                    'message' => 'Invalid email or password'
                ], 401);
            }

            $user = Auth::user();
            // Generate refresh token with 1 week TTL
            JWTAuth::factory()->setTTL($this->REFRESH_TOKEN_TTL);
            $refreshToken = JWTAuth::fromUser($user);

            // Check if user is verified
            if (!$user->verified) {
                // Send verification email
                $this->emailVerificationService->sendVerificationEmail($user);

                return response()->json([
                    'success' => false,
                    'status' => 'verification_required',
                    'message' => 'Please verify your email address. A verification code has been sent to your email.',
                    'accessToken' => $token,
                    'refreshToken' => $refreshToken,
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
                    'success' => true,
                    'status' => 'mfa_required',
                    'message' => 'MFA verification required. A verification code has been sent to your email.',
                    'tempToken' => $tempToken,
                    'user' => [
                        'email' => $user->email,
                        'id' => $user->id,
                        'role' => $user->getRoleNames()->first(),
                        'isVerified' => $user->verified
                    ]
                ], 201);
            }

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Login successful',
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
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Authentication failed. Please try again later.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Login failed. Please try again later.'
            ], 501);
        }
    }

    /**
     * Refresh Token
     *
     * Get a new access token using refresh token.
     *
     * @authenticated
     *
     * @response {
     *  "status": "success",
     *  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *  "token_type": "bearer",
     *  "expires_in": 3600
     * }
     *
     * @response 401 {
     *  "error": "Could not refresh token"
     * }
     */
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
     * Get Authenticated User
     *
     * Retrieve the authenticated user's details.
     *
     * @authenticated
     *
     * @response {
     *  "user": {
     *    "id": "1",
     *    "firstName": "John",
     *    "lastName": "Doe",
     *    "email": "john@example.com",
     *    "role": "customer",
     *    "status": "active",
     *    "avatarUrl": "http://example.com/storage/avatar.jpg",
     *    "phoneNumber": "+1234567890",
     *    "address": "123 Main St",
     *    "country": "USA",
     *    "state": "California",
     *    "city": "Los Angeles",
     *    "sex": "male",
     *    "about": "About John Doe",
     *    "zipCode": "90001",
     *    "company": "Tech Corp",
     *    "isVerified": true,
     *    "created_at": "2023-01-01T00:00:00Z",
     *    "updated_at": "2023-01-01T00:00:00Z",
     *  },
     *  "mfaEnabled": false,
     *  "mfaVerified": false
     * }
     *
     * @response 401 {
     *  "message": "Invalid token"
     * }
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
     * Logout User
     *
     * Invalidate the current token and logout user.
     *
     * @authenticated
     *
     * @response {
     *  "message": "Successfully logged out"
     * }
     *
     * @response 500 {
     *  "error": "Logout failed"
     * }
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
