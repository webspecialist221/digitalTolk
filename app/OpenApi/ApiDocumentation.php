<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Digital Tolk Translation API',
    description: 'OpenAPI documentation for the versioned Laravel API.'
)]
#[OA\Server(url: '/')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token'
)]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    required: ['id', 'name', 'email'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-06-24T10:00:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-06-24T10:00:00Z'),
    ]
)]
#[OA\Schema(
    schema: 'AuthTokenResponse',
    type: 'object',
    required: ['user', 'token', 'token_type'],
    properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'token', type: 'string', example: '1|plain-text-sanctum-token'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
    ]
)]
#[OA\Schema(
    schema: 'Locale',
    type: 'object',
    required: ['id', 'code', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'en'),
        new OA\Property(property: 'name', type: 'string', example: 'English'),
    ]
)]
#[OA\Schema(
    schema: 'Tag',
    type: 'object',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'mobile'),
    ]
)]
#[OA\Schema(
    schema: 'Translation',
    type: 'object',
    required: ['id', 'translation_key', 'content', 'locale', 'tags', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'translation_key', type: 'string', example: 'welcome_message'),
        new OA\Property(property: 'content', type: 'string', example: 'Welcome'),
        new OA\Property(property: 'locale', ref: '#/components/schemas/Locale'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Tag')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-06-24T10:00:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-06-24T10:00:00Z'),
    ]
)]
#[OA\Schema(
    schema: 'TranslationStoreRequest',
    type: 'object',
    required: ['translation_key', 'locale', 'content'],
    properties: [
        new OA\Property(property: 'translation_key', type: 'string', maxLength: 255, example: 'welcome_message'),
        new OA\Property(property: 'locale', type: 'string', maxLength: 10, example: 'en'),
        new OA\Property(property: 'content', type: 'string', example: 'Welcome'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            nullable: true,
            items: new OA\Items(type: 'string', maxLength: 100, example: 'mobile'),
            example: ['mobile', 'web']
        ),
    ]
)]
#[OA\Schema(
    schema: 'TranslationUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'translation_key', type: 'string', maxLength: 255, example: 'welcome_message'),
        new OA\Property(property: 'locale', type: 'string', maxLength: 10, example: 'fr'),
        new OA\Property(property: 'content', type: 'string', example: 'Bienvenue'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            nullable: true,
            items: new OA\Items(type: 'string', maxLength: 100, example: 'web'),
            example: ['desktop', 'web']
        ),
    ]
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Success'),
        new OA\Property(property: 'data', type: 'object', nullable: true, example: ['id' => 1]),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Error'),
        new OA\Property(property: 'errors', type: 'object', nullable: true, example: ['message' => ['Something went wrong.']]),
    ]
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    type: 'object',
    required: ['success', 'message', 'errors'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: [
                'email' => ['The email field is required.'],
                'password' => ['The password field is required.'],
            ]
        ),
    ]
)]
final class ApiDocumentation
{
}
