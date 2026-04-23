<?php

namespace App\Features\Profile\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Profile\Services\ProfileService;
use App\Features\Profile\Requests\UpdateProfileRequest;
use App\Features\Profile\Requests\ChangePasswordRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    protected $service;

    public function __construct(ProfileService $service)
    {
        $this->service = $service;
    }

    public function show(): JsonResponse
    {
        $data = $this->service->getProfile();
        return response()->json(['message' => 'Success', 'data' => $data]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $this->service->updateProfile($request->validated());
        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->service->changePassword(
                $request->current_password,
                $request->new_password
            );
            return response()->json(['message' => 'Password changed successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
