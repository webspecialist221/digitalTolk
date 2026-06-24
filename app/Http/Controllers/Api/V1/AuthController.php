<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/register',
        operationId: 'registerUser',
        tags: ['Authentication'],
        summary: 'Register a new user and issue a Sanctum token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'Password123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', minLength: 8, example: 'Password123!'),
                ],
                example: [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'password' => 'Password123!',
                    'password_confirmation' => 'Password123!',
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registered successfully',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Registered',
                        'data' => [
                            'user' => [
                                'id' => 1,
                                'name' => 'John Doe',
                                'email' => 'john@example.com',
                                'email_verified_at' => null,
                                'created_at' => '2026-06-24T10:00:00Z',
                                'updated_at' => '2026-06-24T10:00:00Z',
                            ],
                            'token' => '1|plain-text-sanctum-token',
                            'token_type' => 'Bearer',
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => $result['token_type'],
        ], 'Registered', 201);
    }

    #[OA\Post(
        path: '/api/v1/login',
        operationId: 'loginUser',
        tags: ['Authentication'],
        summary: 'Authenticate a user and issue a Sanctum token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
                ],
                example: [
                    'email' => 'john@example.com',
                    'password' => 'Password123!',
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged in successfully',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Logged in',
                        'data' => [
                            'user' => [
                                'id' => 1,
                                'name' => 'John Doe',
                                'email' => 'john@example.com',
                                'email_verified_at' => null,
                                'created_at' => '2026-06-24T10:00:00Z',
                                'updated_at' => '2026-06-24T10:00:00Z',
                            ],
                            'token' => '1|plain-text-sanctum-token',
                            'token_type' => 'Bearer',
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ErrorResponse',
                    example: [
                        'success' => false,
                        'message' => 'Invalid credentials',
                        'errors' => null,
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if ($result === null) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => $result['token_type'],
        ], 'Logged in');
    }

    #[OA\Post(
        path: '/api/v1/logout',
        operationId: 'logoutUser',
        tags: ['Authentication'],
        summary: 'Revoke the current Sanctum token',
        security: [
            ['sanctum' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Logged out',
                        'data' => null,
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ErrorResponse',
                    example: [
                        'success' => false,
                        'message' => 'Unauthenticated.',
                        'errors' => null,
                    ]
                )
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $this->authService->logout($user);
        }

        return $this->successResponse(null, 'Logged out');
    }

    #[OA\Get(
        path: '/api/v1/me',
        operationId: 'meUser',
        tags: ['Authentication'],
        summary: 'Get the authenticated user',
        security: [
            ['sanctum' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Success',
                        'data' => [
                            'id' => 1,
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                            'email_verified_at' => null,
                            'created_at' => '2026-06-24T10:00:00Z',
                            'updated_at' => '2026-06-24T10:00:00Z',
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ErrorResponse',
                    example: [
                        'success' => false,
                        'message' => 'Unauthenticated.',
                        'errors' => null,
                    ]
                )
            ),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(new UserResource($this->authService->me($request->user())));
    }
}
