<?php
namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * @OA\Info(title="Laravel API", version="1.0.0")
 * @OA\Server(url=L5_SWAGGER_CONST_HOST)
 */
class PostController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Get(
     *     path="/api/posts",
     *     summary="List all posts",
     *     tags={"Posts"},
     *     @OA\Response(
     *         response=200,
     *         description="Posts retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/PostResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No posts found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="An error has occurred ...."),
     *             @OA\Property(property="message", type="string", example="No posts found"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="An error has occurred ...."),
     *             @OA\Property(property="message", type="string", example="An error occurred while fetching posts"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $posts = Post::with('comments')->get();

            if ($posts->isEmpty()) {
                return $this->error([], 'No posts found', 404);
            }

            return $this->Success(PostResource::collection($posts), 'Posts retrieved successfully', 200);

        } catch (Exception $e) {
            Log::error('Error fetching posts: ' . $e->getMessage());
            return $this->error([], 'An error occurred while fetching posts', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/posts",
     *     summary="Create a new post",
     *     tags={"Posts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "content"},
     *             @OA\Property(property="title", type="string", example="My New Post"),
     *             @OA\Property(property="content", type="string", example="This is the content of the post")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/PostResource")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="An error has occurred ...."),
     *             @OA\Property(property="message", type="string", example="An error occurred while creating the post"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function store(StorePostRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $validatedData['user_id'] = Auth::id();

            $post = Post::create($validatedData);

            return $this->Success(new PostResource($post), 'Post created successfully', 201);

        } catch (Exception $e) {
            Log::error('Error creating post: ' . $e->getMessage());
            return $this->error([], 'An error occurred while creating the post', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/posts/{id}",
     *     summary="Get a post by ID",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/PostResource")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="An error has occurred ...."),
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving the post"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        try {
            $post = Post::with('comments')->findOrFail($id);

            return $this->Success(new PostResource($post), 'Post retrieved successfully', 200);

        } catch (Exception $e) {
            Log::error('Error retrieving post: ' . $e->getMessage());
            return $this->error([], 'An error occurred while retrieving the post', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/posts/{id}",
     *     summary="Update a post",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Title"),
     *             @OA\Property(property="content", type="string", example="Updated content")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/PostResource")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to update this post",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="An error has occurred ...."),
     *             @OA\Property(property="message", type="string", example="You are not authorized to make this request"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="An error has occurred ...."),
     *             @OA\Property(property="message", type="string", example="An error occurred while updating the post"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        try {
            $post = Post::findOrFail($id);
            $post->update($request->all());

            return $this->Success(new PostResource($post), 'Post updated successfully', 200);

        } catch (Exception $e) {
            Log::error('Error updating post: ' . $e->getMessage());
            return $this->error([], 'An error occurred while updating the post', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/posts/{id}",
     *     summary="Delete a post",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Post deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="An error has occurred ...."),
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the post"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $post = Post::findOrFail($id);
            $post->delete();

            return response()->json(['message' => 'Post deleted successfully'], 204);

        } catch (Exception $e) {
            Log::error('Error deleting post: ' . $e->getMessage());
            return $this->error([], 'An error occurred while deleting the post', 500);
        }
    }
}
