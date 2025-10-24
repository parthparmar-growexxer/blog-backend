<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Posts",
 *     description="API Endpoints for Managing Blog Posts"
 * )
 */

class PostController extends Controller {
    /**
     * @OA\Get(
     *     path="/api/v1/user/posts",
     *     operationId="getUserPosts",
     *     tags={"Posts"},
     *     summary="Get all posts of authenticated user",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user's posts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Posts fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="My first post"),
     *                     @OA\Property(property="content", type="string", example="Post content"),
     *                     @OA\Property(property="slug", type="string", example="my-first-post"),
     *                     @OA\Property(property="banner", type="string", nullable=true,
     *                              example="banners/example.jpg"),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="category_id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch posts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch posts")
     *         )
     *     )
     * )
     */
    public function index() {
        try {
            $user = request()->user();
            $posts = Post::with(['user', 'category'])->where('user_id', $user->id)->get();
            return apiResponse($posts, 'Posts fetched successfully');
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to fetch posts', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/posts",
     *     operationId="createPost",
     *     tags={"Posts"},
     *     summary="Create a new post",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title","content"},
     *                 @OA\Property(property="title", type="string", example="My First Post"),
     *                 @OA\Property(property="content", type="string", example="Post content here..."),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="banner", type="string", format="binary",
     *                      description="Banner image (jpg, jpeg, png - max 2MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Post created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="My First Post"),
     *                 @OA\Property(property="content", type="string", example="Post content here..."),
     *                 @OA\Property(property="slug", type="string", example="my-first-post-1234567890"),
     *                 @OA\Property(property="banner", type="string", nullable=true, example="banners/example.jpg"),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to create post",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create post")
     *         )
     *     )
     * )
     */
    public function store(Request $request) {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'is_published' => 'boolean',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        try {
            $post = Post::create([
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'slug' => Str::slug($request->title).'-'.time(),
                'content' => $request->content,
                'category_id' => $request->category_id,
                'is_published' => $request->is_published ?? false
            ]);

            if ($request->hasFile('banner')) {
                $bannerPath = $request->file('banner')->store('banners', 'public');
                $post->banner = $bannerPath;
                $post->save();
            }

            return apiResponse($post, 'Post created successfully', 201);
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to create post', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/posts/{post}",
     *     operationId="getPostById",
     *     tags={"Posts"},
     *     summary="Get a specific post by ID",
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
     *         description="Post fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Post fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="My first post"),
     *                 @OA\Property(property="content", type="string", example="Post content"),
     *                 @OA\Property(property="slug", type="string", example="my-first-post-1234567890"),
     *                 @OA\Property(property="banner", type="string", nullable=true, example="banners/example.jpg"),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     )
     * )
     */
    public function show(Post $post) {
        try {
            $post->load(['user', 'category']);
            return apiResponse($post, 'Post fetched successfully');
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to fetch post', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/posts/{post}",
     *     operationId="updatePost",
     *     tags={"Posts"},
     *     summary="Update a post",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT",
     *                      description="Method spoofing for multipart requests"),
     *                 @OA\Property(property="title", type="string", example="Updated Post Title"),
     *                 @OA\Property(property="content", type="string", example="Updated content"),
     *                 @OA\Property(property="category_id", type="integer", example=2),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="banner", type="string", format="binary",
     *                      description="Banner image (jpg, jpeg, png - max 2MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Post updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Updated Post Title"),
     *                 @OA\Property(property="content", type="string", example="Updated content"),
     *                 @OA\Property(property="slug", type="string", example="updated-post-title-1234567890"),
     *                 @OA\Property(property="banner", type="string", nullable=true,
     *                          example="banners/updated-example.jpg"),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="category_id", type="integer", example=2),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update post",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update post")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Post $post) {
        $this->authorize('update', $post);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category_id' => 'nullable|exists:categories,id',
            'is_published' => 'boolean',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        try {
            if ($request->has('title')) {
                $post->slug = Str::slug($request->title).'-'.time();
            }

            $post->update($request->only(['title', 'content', 'category_id', 'is_published']));
            if ($request->hasFile('banner')) {
                $bannerPath = $request->file('banner')->store('banners', 'public');
                $post->banner = $bannerPath;
                $post->save();
            }
            
            return apiResponse($post, 'Post updated successfully');
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to update post', 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/user/posts/{post}/toggle-publish",
     *     operationId="togglePublishPost",
     *     tags={"Posts"},
     *     summary="Toggle publish status of a post",
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
     *         description="Post publish status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Post has been published successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="My Post"),
     *                 @OA\Property(property="content", type="string", example="Post content"),
     *                 @OA\Property(property="slug", type="string", example="my-post-1234567890"),
     *                 @OA\Property(property="banner", type="string", nullable=true, example="banners/example.jpg"),
     *                 @OA\Property(property="is_published", type="boolean", example=true),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to toggle publish status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to toggle publish status")
     *         )
     *     )
     * )
     */
    public function togglePublish(Request $request, Post $post) {
        $this->authorize('update', $post);

        try {
            $post->is_published = !$post->is_published;
            $post->save();

            $status = $post->is_published ? 'published' : 'unpublished';
            return apiResponse($post, "Post has been {$status} successfully");
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to toggle publish status', 500);
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/user/posts/{post}",
     *     operationId="deletePost",
     *     tags={"Posts"},
     *     summary="Delete a post",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Post deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     )
     * )
     */
    public function destroy(Post $post) {
        $this->authorize('delete', $post);

        try {
            $post->delete();
            return apiResponse(null, 'Post deleted successfully');
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to delete post', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/posts",
     *     operationId="getAllPosts",
     *     tags={"Posts"},
     *     summary="Get all published blog posts",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all published posts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All posts fetched successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="category_id", type="integer", example=2),
     *                     @OA\Property(property="title", type="string", example="My First Blog"),
     *                     @OA\Property(property="slug", type="string", example="my-first-blog-1760708032"),
     *                     @OA\Property(property="content", type="string", example="My First Blog Updated Content."),
     *                     @OA\Property(property="banner", type="string", nullable=true, example="banners/example.jpg"),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time",
     *                              example="2025-10-17T13:33:52.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time",
     *                              example="2025-10-17T13:42:05.000000Z"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Parth Parmar"),
     *                         @OA\Property(property="email", type="string", example="parth.parmar@growexx.com"),
     *                         @OA\Property(property="email_verified_at", type="string", format="date-time",
     *                                  nullable=true, example=null),
     *                         @OA\Property(property="role", type="string", example="author"),
     *                         @OA\Property(property="created_at", type="string", format="date-time",
     *                              example="2025-10-17T10:50:41.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time",
     *                              example="2025-10-17T10:50:41.000000Z")
     *                     ),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Technical Blogs"),
     *                         @OA\Property(property="slug", type="string", example="technical-blogs"),
     *                         @OA\Property(property="created_at", type="string", format="date-time",
     *                              example="2025-10-17T13:23:14.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time",
     *                              example="2025-10-17T13:23:14.000000Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch posts",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch posts")
     *         )
     *     )
     * )
     */
    public function allPosts() {
        try {
            $posts = Post::with(['user', 'category'])->where('is_published',true)->get();
            return apiResponse($posts, 'All posts fetched successfully');
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to fetch posts', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/posts/category/{categoryId}",
     *     operationId="getPostsByCategory",
     *     tags={"Posts"},
     *     summary="Get all posts by category",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         required=true,
     *         description="ID of the category",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Posts fetched successfully for the category",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string",
     *                      example="Posts fetched successfully for the category"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="category_id", type="integer", example=2),
     *                     @OA\Property(property="title", type="string", example="Technical Article"),
     *                     @OA\Property(property="slug", type="string", example="technical-article-1760708032"),
     *                     @OA\Property(property="content", type="string", example="Article content here."),
     *                     @OA\Property(property="banner", type="string", nullable=true, example="banners/example.jpg"),
     *                     @OA\Property(property="is_published", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time",
     *                              example="2025-10-17T13:33:52.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time",
     *                              example="2025-10-17T13:42:05.000000Z"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Parth Parmar"),
     *                         @OA\Property(property="email", type="string", example="parth.parmar@growexx.com")
     *                     ),
     *                     @OA\Property(
     *                         property="category",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Technical Blogs"),
     *                         @OA\Property(property="slug", type="string", example="technical-blogs")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch posts for the category",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch posts for the category")
     *         )
     *     )
     * )
     */
    public function postsByCategory($categoryId) {
        try {
            $posts = Post::with(['user', 'category'])->where('category_id', $categoryId)->get();
            return apiResponse($posts, 'Posts fetched successfully for the category');
        } catch (\Exception $e) {
            return apiResponse(null, 'Failed to fetch posts for the category', 500);
        }
    }
}