<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Blog\StoreBlogCategoryRequest;
use App\Http\Requests\Api\V1\Blog\StoreBlogCommentRequest;
use App\Http\Requests\Api\V1\Blog\StoreBlogPostRequest;
use App\Http\Requests\Api\V1\Blog\UpdateBlogCategoryRequest;
use App\Http\Requests\Api\V1\Blog\UpdateBlogPostRequest;
use App\Services\BlogService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use ApiResponse;

    public function __construct(
        private BlogService $blogService
    ) {}

    // ─── Public ─────────────────────────────────────────────────────

    public function categories(): JsonResponse
    {
        $categories = $this->blogService->getActiveCategories();

        return $this->success('Categories retrieved successfully', [
            'categories' => $categories,
        ]);
    }

    public function posts(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 15), 50);

        $posts = $this->blogService->getPublishedPosts(
            $perPage,
            $request->category,
            $request->tag
        );

        return $this->success('Posts retrieved successfully', [
            'posts'      => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'per_page'     => $posts->perPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }

    public function showPost(string $uuid): JsonResponse
    {
        $post = $this->blogService->findPublishedByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $postData = $post->toArray();
        $postData['user_liked'] = false;

        if (auth('sanctum')->check()) {
            $postData['user_liked'] = $this->blogService->hasUserLiked(
                $post,
                auth('sanctum')->id()
            );
        }

        return $this->success('Post retrieved successfully', ['post' => $postData]);
    }

    public function tags(): JsonResponse
    {
        $tags = $this->blogService->getAllTags();

        return $this->success('Tags retrieved successfully', ['tags' => $tags]);
    }

    // ─── Authenticated: Comments & Likes ────────────────────────────

    public function storeComment(StoreBlogCommentRequest $request, string $uuid): JsonResponse
    {
        $post = $this->blogService->findPublishedByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $comment = $this->blogService->createComment(
            $post,
            $request->user()->id,
            $request->validated()
        );

        return $this->created('Comment submitted for approval', ['comment' => $comment]);
    }

    public function toggleLike(Request $request, string $uuid): JsonResponse
    {
        $post = $this->blogService->findPublishedByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $result = $this->blogService->toggleLike($post, $request->user()->id);
        $message = $result['liked'] ? 'Post liked' : 'Like removed';

        return $this->success($message, $result);
    }

    // ─── Admin: Categories ──────────────────────────────────────────

    public function adminCategories(): JsonResponse
    {
        $categories = $this->blogService->getAllCategories();

        return $this->success('Categories retrieved successfully', [
            'categories' => $categories,
        ]);
    }

    public function storeCategory(StoreBlogCategoryRequest $request): JsonResponse
    {
        $category = $this->blogService->createCategory($request->validated());

        return $this->created('Category created successfully', ['category' => $category]);
    }

    public function updateCategory(UpdateBlogCategoryRequest $request, string $uuid): JsonResponse
    {
        $category = $this->blogService->findCategoryByUuid($uuid);

        if (!$category) {
            return $this->notFound('Category not found');
        }

        $category = $this->blogService->updateCategory($category, $request->validated());

        return $this->success('Category updated successfully', ['category' => $category]);
    }

    public function destroyCategory(string $uuid): JsonResponse
    {
        $category = $this->blogService->findCategoryByUuid($uuid);

        if (!$category) {
            return $this->notFound('Category not found');
        }

        $this->blogService->deleteCategory($category);

        return $this->success('Category deleted successfully');
    }

    // ─── Admin: Posts ───────────────────────────────────────────────

    public function adminPosts(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $posts = $this->blogService->getAllPosts($perPage, $request->status);

        return $this->success('Posts retrieved successfully', [
            'posts'      => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'per_page'     => $posts->perPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }

    public function storePost(StoreBlogPostRequest $request): JsonResponse
    {
        $post = $this->blogService->createPost(
            $request->user()->id,
            $request->validated()
        );

        return $this->created('Post created successfully', ['post' => $post]);
    }

    public function adminShowPost(string $uuid): JsonResponse
    {
        $post = $this->blogService->findByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        return $this->success('Post retrieved successfully', ['post' => $post]);
    }

    public function updatePost(UpdateBlogPostRequest $request, string $uuid): JsonResponse
    {
        $post = $this->blogService->findByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $post = $this->blogService->updatePost($post, $request->validated());

        return $this->success('Post updated successfully', ['post' => $post]);
    }

    public function destroyPost(string $uuid): JsonResponse
    {
        $post = $this->blogService->findByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $this->blogService->deletePost($post);

        return $this->success('Post deleted successfully');
    }

    public function togglePublish(string $uuid): JsonResponse
    {
        $post = $this->blogService->findByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $post = $this->blogService->togglePublish($post);
        $action = $post->isPublished() ? 'published' : 'unpublished';

        return $this->success("Post {$action} successfully", ['post' => $post]);
    }

    // ─── Admin: Comments ────────────────────────────────────────────

    public function adminPostComments(Request $request, string $uuid): JsonResponse
    {
        $post = $this->blogService->findByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $comments = $this->blogService->getAllComments($post, $perPage);

        return $this->success('Comments retrieved successfully', [
            'comments'   => $comments->items(),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page'    => $comments->lastPage(),
                'per_page'     => $comments->perPage(),
                'total'        => $comments->total(),
            ],
        ]);
    }

    public function approveComment(string $uuid): JsonResponse
    {
        $comment = $this->blogService->findCommentByUuid($uuid);

        if (!$comment) {
            return $this->notFound('Comment not found');
        }

        $comment = $this->blogService->toggleCommentApproval($comment);
        $action = $comment->is_approved ? 'approved' : 'unapproved';

        return $this->success("Comment {$action}", ['comment' => $comment]);
    }

    public function destroyComment(string $uuid): JsonResponse
    {
        $comment = $this->blogService->findCommentByUuid($uuid);

        if (!$comment) {
            return $this->notFound('Comment not found');
        }

        $this->blogService->deleteComment($comment);

        return $this->success('Comment deleted successfully');
    }

    // ─── Admin: Tags ────────────────────────────────────────────────

    public function adminTags(): JsonResponse
    {
        $tags = $this->blogService->getAllTags();

        return $this->success('Tags retrieved successfully', ['tags' => $tags]);
    }

    public function storeTag(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:blog_tags,name',
        ]);

        $tag = $this->blogService->createTag($request->only('name'));

        return $this->created('Tag created successfully', ['tag' => $tag]);
    }
}
