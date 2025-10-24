<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller {
    /**
     * @OA\Get(
     *     path="/api/v1/posts/{post}/comments",
     *     operationId="getPostComments",
     *     tags={"Comments"},
     *     summary="Get all comments for a post",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID of the post",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of comments",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comments fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="post_id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="content", type="string", example="This is a comment"),
     *                     @OA\Property(property="created_at", type="string", format="date-time",
     *                          example="2024-01-01T00:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time",
     *                          example="2024-01-01T00:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch comments",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch comments")
     *         )
     *     )
     * )
     */
    public function index($postId) {
        try {
            $comments = Comment::with('user') // eager load user
            ->where('post_id', $postId)
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'post_id' => $comment->post_id,
                    'user_id' => $comment->user_id,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                    'user' => [
                        'name' => $comment->user->name,
                        'email' => $comment->user->email,
                    ],
                ];
            });
            return apiResponse($comments, 'Comments fetched successfully');
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to fetch comments', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/posts/{post}/comments",
     *     operationId="createComment",
     *     tags={"Comments"},
     *     summary="Add a comment to a post",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="ID of the post",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="This is a comment")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comment created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="post_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="content", type="string", example="This is a comment"),
     *                 @OA\Property(property="created_at", type="string", format="date-time",
     *                      example="2024-01-01T00:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time",
     *                      example="2024-01-01T00:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to create comment",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create comment")
     *         )
     *     )
     * )
     */
    public function store(Request $request, $postId) {
        $request->validate([
            'content' => 'required|string'
        ]);

        try {
            $comment = Comment::create([
                'user_id' => $request->user()->id,
                'post_id' => $postId,
                'body' => $request->content,
                'is_approved' => true,
            ]);
            return apiResponse($comment, 'Comment created successfully', 201);
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to create comment', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/comments/{comment}",
     *     operationId="deleteComment",
     *     tags={"Comments"},
     *     summary="Delete a comment",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="ID of the comment",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to delete comment",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete comment")
     *         )
     *     )
     * )
     */
    public function destroy(Comment $comment) {
        $this->authorize('delete', $comment);

        try {
            $comment->delete();
            return apiResponse(null, 'Comment deleted successfully');
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to delete comment', 500);
        }
    }
}
