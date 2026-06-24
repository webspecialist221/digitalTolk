<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchTranslationRequest;
use App\Http\Requests\Api\V1\StoreTranslationRequest;
use App\Http\Requests\Api\V1\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationService $translationService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/translations',
        operationId: 'listTranslations',
        tags: ['Translations'],
        summary: 'List translations',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'locale', in: 'query', description: 'Filter by locale code', required: false, schema: new OA\Schema(type: 'string', example: 'en')),
            new OA\Parameter(name: 'tag', in: 'query', description: 'Filter by tag name', required: false, schema: new OA\Schema(type: 'string', example: 'mobile')),
            new OA\Parameter(name: 'key', in: 'query', description: 'Filter by translation key', required: false, schema: new OA\Schema(type: 'string', example: 'welcome')),
            new OA\Parameter(name: 'content', in: 'query', description: 'Search inside translation content', required: false, schema: new OA\Schema(type: 'string', example: 'Welcome')),
            new OA\Parameter(name: 'q', in: 'query', description: 'Free-text search across supported fields', required: false, schema: new OA\Schema(type: 'string', example: 'Welcome')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translations retrieved successfully',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Success',
                        'data' => [
                            'data' => [
                                [
                                    'id' => 1,
                                    'translation_key' => 'welcome_message',
                                    'content' => 'Welcome',
                                    'locale' => [
                                        'id' => 1,
                                        'code' => 'en',
                                        'name' => 'English',
                                    ],
                                    'tags' => [
                                        ['id' => 1, 'name' => 'web'],
                                    ],
                                    'created_at' => '2026-06-24T10:00:00Z',
                                    'updated_at' => '2026-06-24T10:00:00Z',
                                ],
                            ],
                            'links' => [
                                'first' => null,
                                'last' => null,
                                'prev' => null,
                                'next' => null,
                            ],
                            'meta' => [
                                'current_page' => 1,
                                'from' => 1,
                                'last_page' => 1,
                                'path' => 'http://localhost/api/v1/translations',
                                'per_page' => 20,
                                'to' => 1,
                                'total' => 1,
                            ],
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function index(SearchTranslationRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $translations = $this->translationService->list(
            $filters,
            (int) ($filters['per_page'] ?? 20)
        );

        return $this->successResponse(
            TranslationResource::collection($translations)
        );
    }

    #[OA\Post(
        path: '/api/v1/translations',
        operationId: 'storeTranslation',
        tags: ['Translations'],
        summary: 'Create a translation',
        security: [
            ['sanctum' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TranslationStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Translation created successfully',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Created',
                        'data' => [
                            'id' => 1,
                            'translation_key' => 'welcome_message',
                            'content' => 'Welcome',
                            'locale' => [
                                'id' => 1,
                                'code' => 'en',
                                'name' => 'English',
                            ],
                            'tags' => [
                                ['id' => 1, 'name' => 'web'],
                            ],
                            'created_at' => '2026-06-24T10:00:00Z',
                            'updated_at' => '2026-06-24T10:00:00Z',
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $translation = $this->translationService->create($request->validated());

        return $this->successResponse(
            new TranslationResource($translation),
            'Created',
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/translations/{translation}',
        operationId: 'showTranslation',
        tags: ['Translations'],
        summary: 'Show a translation',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'translation', in: 'path', required: true, description: 'Translation ID', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translation retrieved successfully',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Success',
                        'data' => [
                            'id' => 1,
                            'translation_key' => 'welcome_message',
                            'content' => 'Welcome',
                            'locale' => [
                                'id' => 1,
                                'code' => 'en',
                                'name' => 'English',
                            ],
                            'tags' => [
                                ['id' => 1, 'name' => 'web'],
                            ],
                            'created_at' => '2026-06-24T10:00:00Z',
                            'updated_at' => '2026-06-24T10:00:00Z',
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Translation not found',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ErrorResponse',
                    example: [
                        'success' => false,
                        'message' => 'Translation not found for identifier: 1',
                        'errors' => null,
                    ]
                )
            ),
        ]
    )]
    public function show(int $translation): JsonResponse
    {
        return $this->successResponse(
            new TranslationResource($this->translationService->findOrFail($translation))
        );
    }

    #[OA\Put(
        path: '/api/v1/translations/{translation}',
        operationId: 'updateTranslation',
        tags: ['Translations'],
        summary: 'Update a translation',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'translation', in: 'path', required: true, description: 'Translation ID', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TranslationUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translation updated successfully',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Success',
                        'data' => [
                            'id' => 1,
                            'translation_key' => 'welcome_message',
                            'content' => 'Bienvenue',
                            'locale' => [
                                'id' => 2,
                                'code' => 'fr',
                                'name' => 'French',
                            ],
                            'tags' => [
                                ['id' => 1, 'name' => 'web'],
                            ],
                            'created_at' => '2026-06-24T10:00:00Z',
                            'updated_at' => '2026-06-24T10:01:00Z',
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Translation not found',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ErrorResponse',
                    example: [
                        'success' => false,
                        'message' => 'Translation not found for identifier: 1',
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
    public function update(UpdateTranslationRequest $request, int $translation): JsonResponse
    {
        $updatedTranslation = $this->translationService->updateOrFail($translation, $request->validated());

        return $this->successResponse(new TranslationResource($updatedTranslation));
    }

    #[OA\Delete(
        path: '/api/v1/translations/{translation}',
        operationId: 'deleteTranslation',
        tags: ['Translations'],
        summary: 'Delete a translation',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'translation', in: 'path', required: true, description: 'Translation ID', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translation deleted successfully',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Deleted',
                        'data' => null,
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Translation not found',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ErrorResponse',
                    example: [
                        'success' => false,
                        'message' => 'Translation not found for identifier: 1',
                        'errors' => null,
                    ]
                )
            ),
        ]
    )]
    public function destroy(int $translation): JsonResponse
    {
        $this->translationService->deleteOrFail($translation);

        return $this->successResponse(null, 'Deleted');
    }

    #[OA\Get(
        path: '/api/v1/translations/search',
        operationId: 'searchTranslations',
        tags: ['Translations'],
        summary: 'Search translations',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'locale', in: 'query', description: 'Filter by locale code', required: false, schema: new OA\Schema(type: 'string', example: 'en')),
            new OA\Parameter(name: 'tag', in: 'query', description: 'Filter by tag name', required: false, schema: new OA\Schema(type: 'string', example: 'mobile')),
            new OA\Parameter(name: 'key', in: 'query', description: 'Filter by translation key', required: false, schema: new OA\Schema(type: 'string', example: 'welcome')),
            new OA\Parameter(name: 'content', in: 'query', description: 'Search inside translation content', required: false, schema: new OA\Schema(type: 'string', example: 'Welcome')),
            new OA\Parameter(name: 'q', in: 'query', description: 'Free-text search across supported fields', required: false, schema: new OA\Schema(type: 'string', example: 'Welcome')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SuccessResponse',
                    example: [
                        'success' => true,
                        'message' => 'Success',
                        'data' => [
                            'data' => [
                                [
                                    'id' => 1,
                                    'translation_key' => 'welcome_message',
                                    'content' => 'Welcome',
                                    'locale' => [
                                        'id' => 1,
                                        'code' => 'en',
                                        'name' => 'English',
                                    ],
                                    'tags' => [
                                        ['id' => 1, 'name' => 'web'],
                                    ],
                                    'created_at' => '2026-06-24T10:00:00Z',
                                    'updated_at' => '2026-06-24T10:00:00Z',
                                ],
                            ],
                            'links' => [
                                'first' => null,
                                'last' => null,
                                'prev' => null,
                                'next' => null,
                            ],
                            'meta' => [
                                'current_page' => 1,
                                'from' => 1,
                                'last_page' => 1,
                                'path' => 'http://localhost/api/v1/translations/search',
                                'per_page' => 20,
                                'to' => 1,
                                'total' => 1,
                            ],
                        ],
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function search(SearchTranslationRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return $this->successResponse(
            TranslationResource::collection(
                $this->translationService->search(
                    $filters,
                    (int) ($filters['per_page'] ?? 20)
                )
            )
        );
    }

    #[OA\Get(
        path: '/api/v1/translations/export/{locale}',
        operationId: 'exportTranslations',
        tags: ['Translations'],
        summary: 'Export translations for a locale',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'locale', in: 'path', required: true, description: 'Locale code', schema: new OA\Schema(type: 'string', example: 'en')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Locale export payload',
                content: new OA\JsonContent(
                    type: 'object',
                    example: [
                        'welcome_message' => 'Welcome',
                        'login' => 'Login',
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function export(string $localeCode): JsonResponse
    {
        return response()->json(
            $this->translationService->exportByLocale($localeCode)->all()
        );
    }
}
