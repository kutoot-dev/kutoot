<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformTerms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformTermsController extends Controller
{
    public function index(): JsonResponse
    {
        $terms = PlatformTerms::latest()->paginate(15);

        return response()->json($terms);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'version' => 'required|string|unique:platform_terms,version',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_active')) {
            $data['published_at'] = now();
        }

        $terms = PlatformTerms::create($data);

        return response()->json(['data' => $terms], 201);
    }

    public function show(PlatformTerms $platformTerms): JsonResponse
    {
        return response()->json(['data' => $platformTerms]);
    }

    public function update(Request $request, PlatformTerms $platformTerms): JsonResponse
    {
        $data = $request->validate([
            'version' => 'sometimes|string|unique:platform_terms,version,'.$platformTerms->id,
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'is_active' => 'boolean',
        ]);

        if ($request->has('is_active') && $request->boolean('is_active') && ! $platformTerms->published_at) {
            $data['published_at'] = now();
        }

        $platformTerms->update($data);

        return response()->json(['data' => $platformTerms->fresh()]);
    }

    public function destroy(PlatformTerms $platformTerms): JsonResponse
    {
        $platformTerms->delete();

        return response()->json(null, 204);
    }
}
