<?php

namespace App\Http\Resources;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="CommentResourceSchema",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="content", type="string", example="This is a comment"),
 *     @OA\Property(property="user", ref="#/components/schemas/UserResourceSchema"),
 *     @OA\Property(property="post", ref="#/components/schemas/PostResourceSchema"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */
class CommentResourceSchema
{
    // Schema for CommentResource
}
