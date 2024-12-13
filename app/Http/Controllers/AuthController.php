<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterationRequest;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\ApiResponses;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    use ApiResponses;

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
        $validatedData = $request->validated();

        $role = Role::where('name', $validatedData['role'])->firstOrFail();

        $user = User::create([
            'role_id' => $role->id,
            'firstName' => $validatedData['firstName'],
            'lastName' => $validatedData['lastName'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'sex' => $validatedData['sex'],
            'address' => $validatedData['address'],
            'password' => Hash::make($validatedData['password']),
            'verified' => $validatedData['verified'] ?? false,
        ]);

        if ($role->name === 'supplier') {
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
            ]);
            $user->company_id = $company->id;
            $user->save();
        }

        $accessToken = JWTAuth::fromUser($user);

        return response()->json(compact('accessToken'), 201);
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
        $credentials = $request->validated();

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid email or password'], 401);
        }

        return response()->json(['accessToken' => $token]);
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
        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid token'], 400);
        }

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $role = Role::find($user->role_id);
        $user->role = $role ? $role->name : null;
        unset($user->role_id);

        return response()->json($user);
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
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }
}