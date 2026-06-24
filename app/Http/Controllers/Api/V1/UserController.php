<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexUserRequest;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    public function index(IndexUserRequest $request): JsonResponse
    {
        $users = $this->userService->paginate((int) $request->validated('per_page', 15));

        return $this->successResponse(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return $this->successResponse(new UserResource($user), 'Created', 201);
    }

    public function show(int $user): JsonResponse
    {
        return $this->successResponse(new UserResource($this->userService->findOrFail($user)));
    }

    public function update(UpdateUserRequest $request, int $user): JsonResponse
    {
        $updatedUser = $this->userService->update(
            $this->userService->findOrFail($user),
            $request->validated()
        );

        return $this->successResponse(new UserResource($updatedUser));
    }

    public function destroy(int $user): JsonResponse
    {
        $this->userService->delete($this->userService->findOrFail($user));

        return $this->successResponse(null, 'Deleted');
    }
}
