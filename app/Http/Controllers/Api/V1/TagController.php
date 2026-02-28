<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

/**
 * @tags Tags
 */
class TagController extends Controller
{
    /**
     * List all tags
     *
     * Returns all available tags for filtering merchant locations.
     */
    public function index(): AnonymousResourceCollection
    {
        $tags = Cache::remember('tags:all', 300, function () {
            return Tag::query()
                ->orderBy('name')
                ->get();
        });

        return TagResource::collection($tags);
    }
}
