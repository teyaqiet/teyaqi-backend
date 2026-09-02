<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOnboardingCategoriesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DailyChallengeOnboardingController extends Controller
{
    public function store(StoreOnboardingCategoriesRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // sync() ensures no duplicates and overwrites if they change their minds later
        $user->selectedCategories()->sync($request->validated()['category_ids']);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding categories saved successfully. Ready for the Daily Challenge!',
        ]);
    }
}