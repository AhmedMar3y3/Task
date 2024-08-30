<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Annotations as OA;

class CommentController extends Controller
{
    use HttpResponses;

    /**
     * @OA\Post(
     *     path="/posts/{postId}/comments",
     *     summary="Add a comment to a post",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         description="ID of the post to add a comment to",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="content", type="string", example="This is a comment"),
     *                 required={"content"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment added successfully",
     *         @OA\JsonContent(ref="#/components/schemas/CommentResourceSchema")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while adding the comment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while adding the comment")
     *         )
     *     )
     * )
     */
    public function store(StoreCommentRequest $request, $postId)
    {
        try {
            $post = Post::findOrFail($postId);

            $comment = Comment::create([
                'content' => $request->validated()['content'],
                'user_id' => Auth::id(),
                'post_id' => $post->id,
            ]);

            $postOwner = $post->user;

            Mail::raw("A new comment has been added to your post titled '{$post->title}'", function ($message) use ($postOwner) {
                $message->to($postOwner->email)
                    ->subject('New Comment on Your Post');
            });

            return $this->Success(new \App\Http\Resources\CommentResource($comment), 'Comment has been added successfully', 201);

        } catch (ModelNotFoundException $e) {
            return $this->error([], 'Post not found', 404);

        } catch (Exception $e) {
            Log::error('Error adding comment: ' . $e->getMessage());
            return $this->error([], 'An error occurred while adding the comment', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/posts/{postId}/comments/{commentId}",
     *     summary="Delete a comment from a post",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         description="ID of the post from which to delete a comment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="commentId",
     *         in="path",
     *         description="ID of the comment to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post or comment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post or comment not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to delete this comment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You are not authorized to delete this comment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while deleting the comment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the comment")
     *         )
     *     )
     * )
     */
    public function destroy($postId, $commentId)
    {
        try {
            $post = Post::findOrFail($postId);
            $comment = Comment::findOrFail($commentId);
            if (auth()->id() === $post->user_id || auth()->id() === $comment->user_id) {
                $comment->delete();
                return $this->Success([], 'Comment has been deleted successfully', 200);
            }

            return $this->error([], 'You are not authorized to delete this comment', 403);

        } catch (ModelNotFoundException $e) {
            return $this->error([], 'Post or comment not found', 404);
        } catch (Exception $e) {
            Log::error('Error deleting comment: ' . $e->getMessage());
            return $this->error([], 'An error occurred while deleting the comment', 500);
        }
    }
}
