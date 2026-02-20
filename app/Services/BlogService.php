<?php

namespace App\Services;

use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogLike;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogService
{
    // ─── Categories ────────────────────────────────────────────────

    public function getActiveCategories(): Collection
    {
        return BlogCategory::active()
            ->withCount(['blogPosts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get();
    }

    public function getAllCategories(): Collection
    {
        return BlogCategory::withCount('blogPosts')
            ->orderBy('name')
            ->get();
    }

    public function findCategoryByUuid(string $uuid): ?BlogCategory
    {
        return BlogCategory::where('uuid', $uuid)->first();
    }

    public function createCategory(array $data): BlogCategory
    {
        return BlogCategory::create($data);
    }

    public function updateCategory(BlogCategory $category, array $data): BlogCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    public function deleteCategory(BlogCategory $category): bool
    {
        return $category->delete();
    }

    // ─── Posts ──────────────────────────────────────────────────────

    public function getPublishedPosts(
        int $perPage = 15,
        ?string $categorySlug = null,
        ?string $tagSlug = null
    ): LengthAwarePaginator {
        $query = BlogPost::published()
            ->with(['author:id,name', 'category:id,uuid,name,slug', 'tags:id,uuid,name,slug'])
            ->withCount(['comments' => fn ($q) => $q->approved(), 'likes']);

        if ($categorySlug) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($tagSlug) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug));
        }

        return $query->orderByDesc('published_at')->paginate($perPage);
    }

    public function findPublishedByUuid(string $uuid): ?BlogPost
    {
        return BlogPost::published()
            ->where('uuid', $uuid)
            ->with(['author:id,name', 'category:id,uuid,name,slug', 'tags:id,uuid,name,slug'])
            ->withCount(['comments' => fn ($q) => $q->approved(), 'likes'])
            ->first();
    }

    public function getAllPosts(int $perPage = 20, ?string $status = null): LengthAwarePaginator
    {
        $query = BlogPost::with(['author:id,name', 'category:id,uuid,name,slug', 'tags:id,uuid,name,slug'])
            ->withCount(['comments', 'likes']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?BlogPost
    {
        return BlogPost::where('uuid', $uuid)
            ->with(['author:id,name', 'category:id,uuid,name,slug', 'tags:id,uuid,name,slug'])
            ->withCount(['comments', 'likes'])
            ->first();
    }

    public function createPost(int $authorId, array $data): BlogPost
    {
        return DB::transaction(function () use ($authorId, $data) {
            $post = BlogPost::create([
                'title'          => $data['title'],
                'excerpt'        => $data['excerpt'] ?? null,
                'content'        => $data['content'],
                'featured_image' => $data['featured_image'] ?? null,
                'category_id'    => $data['category_id'] ?? null,
                'author_id'      => $authorId,
                'status'         => $data['status'] ?? 'draft',
                'published_at'   => ($data['status'] ?? 'draft') === 'published' ? now() : null,
            ]);

            if (!empty($data['tags'])) {
                $tagIds = $this->resolveTagIds($data['tags']);
                $post->tags()->sync($tagIds);
            }

            return $post->load(['author:id,name', 'category:id,uuid,name,slug', 'tags:id,uuid,name,slug']);
        });
    }

    public function updatePost(BlogPost $post, array $data): BlogPost
    {
        return DB::transaction(function () use ($post, $data) {
            if (isset($data['status']) && $data['status'] === 'published' && $post->status !== 'published') {
                $data['published_at'] = now();
            }

            if (isset($data['status']) && $data['status'] === 'draft') {
                $data['published_at'] = null;
            }

            $postData = collect($data)->except('tags')->toArray();
            $post->update($postData);

            if (array_key_exists('tags', $data)) {
                $tagIds = $this->resolveTagIds($data['tags'] ?? []);
                $post->tags()->sync($tagIds);
            }

            return $post->fresh(['author:id,name', 'category:id,uuid,name,slug', 'tags:id,uuid,name,slug'])
                ->loadCount(['comments', 'likes']);
        });
    }

    public function deletePost(BlogPost $post): bool
    {
        return $post->delete();
    }

    public function togglePublish(BlogPost $post): BlogPost
    {
        if ($post->isPublished()) {
            $post->update(['status' => 'draft', 'published_at' => null]);
        } else {
            $post->update(['status' => 'published', 'published_at' => now()]);
        }

        return $post->fresh(['author:id,name', 'category:id,uuid,name,slug', 'tags:id,uuid,name,slug']);
    }

    // ─── Tags ───────────────────────────────────────────────────────

    public function getAllTags(): Collection
    {
        return BlogTag::withCount('blogPosts')->orderBy('name')->get();
    }

    public function createTag(array $data): BlogTag
    {
        return BlogTag::create($data);
    }

    /**
     * Resolve tag names or IDs into tag IDs.
     * Creates tags that don't exist yet.
     */
    private function resolveTagIds(array $tags): array
    {
        $ids = [];

        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $ids[] = (int) $tag;
            } else {
                $model = BlogTag::firstOrCreate(
                    ['slug' => Str::slug($tag)],
                    ['name' => $tag]
                );
                $ids[] = $model->id;
            }
        }

        return $ids;
    }

    // ─── Comments ───────────────────────────────────────────────────

    public function getApprovedComments(BlogPost $post, int $perPage = 20): LengthAwarePaginator
    {
        return BlogComment::where('blog_post_id', $post->id)
            ->approved()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getAllComments(BlogPost $post, int $perPage = 20): LengthAwarePaginator
    {
        return BlogComment::where('blog_post_id', $post->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function createComment(BlogPost $post, int $userId, array $data): BlogComment
    {
        $comment = BlogComment::create([
            'blog_post_id' => $post->id,
            'user_id'      => $userId,
            'content'      => $data['content'],
            'is_approved'  => false,
        ]);

        return $comment->load('user:id,name');
    }

    public function findCommentByUuid(string $uuid): ?BlogComment
    {
        return BlogComment::where('uuid', $uuid)->first();
    }

    public function toggleCommentApproval(BlogComment $comment): BlogComment
    {
        $comment->update(['is_approved' => !$comment->is_approved]);
        return $comment->fresh('user:id,name');
    }

    public function deleteComment(BlogComment $comment): bool
    {
        return $comment->delete();
    }

    // ─── Likes ──────────────────────────────────────────────────────

    public function toggleLike(BlogPost $post, int $userId): array
    {
        $existing = BlogLike::where('blog_post_id', $post->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            return ['liked' => false, 'likes_count' => $post->likes()->count()];
        }

        BlogLike::create([
            'blog_post_id' => $post->id,
            'user_id'      => $userId,
        ]);

        return ['liked' => true, 'likes_count' => $post->likes()->count()];
    }

    public function hasUserLiked(BlogPost $post, int $userId): bool
    {
        return BlogLike::where('blog_post_id', $post->id)
            ->where('user_id', $userId)
            ->exists();
    }
}
