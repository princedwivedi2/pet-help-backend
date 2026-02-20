<?php

namespace Tests\Feature\Api\V1;

use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogLike;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/blog';
    private string $adminPrefix = '/api/v1/admin/blog';

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function authUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIC: CATEGORIES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_public_list_categories_returns_active_only(): void
    {
        BlogCategory::factory()->count(3)->create();
        BlogCategory::factory()->inactive()->create();

        $response = $this->getJson("{$this->prefix}/categories");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data.categories');
    }

    public function test_public_list_categories_empty(): void
    {
        $response = $this->getJson("{$this->prefix}/categories");

        $response->assertOk()
            ->assertJsonCount(0, 'data.categories');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIC: POSTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_public_list_posts_returns_published_only(): void
    {
        $admin = $this->adminUser();
        BlogPost::factory()->published()->forAuthor($admin)->count(3)->create();
        BlogPost::factory()->draft()->forAuthor($admin)->count(2)->create();

        $response = $this->getJson("{$this->prefix}/posts");

        $response->assertOk()
            ->assertJsonCount(3, 'data.posts')
            ->assertJsonStructure([
                'data' => [
                    'posts' => [['uuid', 'title', 'slug', 'excerpt']],
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_public_list_posts_filter_by_category(): void
    {
        $admin = $this->adminUser();
        $cat = BlogCategory::factory()->create();
        BlogPost::factory()->published()->forAuthor($admin)->forCategory($cat)->count(2)->create();
        BlogPost::factory()->published()->forAuthor($admin)->create();

        $response = $this->getJson("{$this->prefix}/posts?category={$cat->slug}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.posts');
    }

    public function test_public_list_posts_filter_by_tag(): void
    {
        $admin = $this->adminUser();
        $tag = BlogTag::factory()->create();
        $tagged = BlogPost::factory()->published()->forAuthor($admin)->create();
        $tagged->tags()->attach($tag);
        BlogPost::factory()->published()->forAuthor($admin)->create(); // untagged

        $response = $this->getJson("{$this->prefix}/posts?tag={$tag->slug}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.posts');
    }

    public function test_public_list_posts_pagination(): void
    {
        $admin = $this->adminUser();
        BlogPost::factory()->published()->forAuthor($admin)->count(20)->create();

        $response = $this->getJson("{$this->prefix}/posts?per_page=5");

        $response->assertOk()
            ->assertJsonCount(5, 'data.posts')
            ->assertJsonPath('data.pagination.per_page', 5)
            ->assertJsonPath('data.pagination.total', 20);
    }

    public function test_public_list_posts_per_page_max_50(): void
    {
        $admin = $this->adminUser();
        BlogPost::factory()->published()->forAuthor($admin)->count(5)->create();

        $response = $this->getJson("{$this->prefix}/posts?per_page=999");

        $response->assertOk()
            ->assertJsonPath('data.pagination.per_page', 50);
    }

    public function test_public_show_published_post(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();

        $response = $this->getJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['post' => ['uuid' => $post->uuid]],
            ]);
    }

    public function test_public_show_draft_post_404(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->draft()->forAuthor($admin)->create();

        $response = $this->getJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertStatus(404);
    }

    public function test_public_show_nonexistent_post_404(): void
    {
        $response = $this->getJson("{$this->prefix}/posts/nonexistent-uuid");

        $response->assertStatus(404);
    }

    public function test_public_show_post_includes_user_liked_flag(): void
    {
        $admin = $this->adminUser();
        $user = $this->authUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();

        BlogLike::create([
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.post.user_liked', true);
    }

    public function test_public_show_post_user_liked_false_for_guests(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();

        $response = $this->getJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.post.user_liked', false);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIC: TAGS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_public_list_tags(): void
    {
        BlogTag::factory()->count(5)->create();

        $response = $this->getJson("{$this->prefix}/tags");

        $response->assertOk()
            ->assertJsonCount(5, 'data.tags');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUTHENTICATED: COMMENTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_auth_store_comment_success(): void
    {
        $user = $this->authUser();
        $admin = $this->adminUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/{$post->uuid}/comments", [
                'content' => 'Great article, very informative!',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.comment.is_approved', false);

        $this->assertDatabaseHas('blog_comments', [
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
            'is_approved' => false,
        ]);
    }

    public function test_auth_store_comment_requires_auth(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();

        $response = $this->postJson("{$this->prefix}/posts/{$post->uuid}/comments", [
            'content' => 'Unauthenticated comment',
        ]);

        $response->assertStatus(401);
    }

    public function test_auth_store_comment_validation(): void
    {
        $user = $this->authUser();
        $admin = $this->adminUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();

        // Missing content
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/{$post->uuid}/comments", []);

        $response->assertStatus(422);

        // Too short
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/{$post->uuid}/comments", [
                'content' => 'X',
            ]);

        $response->assertStatus(422);
    }

    public function test_auth_store_comment_nonexistent_post(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/fake-uuid/comments", [
                'content' => 'Comment on ghost post',
            ]);

        $response->assertStatus(404);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUTHENTICATED: LIKES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_auth_toggle_like_add(): void
    {
        $user = $this->authUser();
        $admin = $this->adminUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/{$post->uuid}/like");

        $response->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);

        $this->assertDatabaseHas('blog_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_auth_toggle_like_remove(): void
    {
        $user = $this->authUser();
        $admin = $this->adminUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();

        BlogLike::create(['blog_post_id' => $post->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/{$post->uuid}/like");

        $response->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.likes_count', 0);
    }

    public function test_auth_toggle_like_requires_auth(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();

        $response = $this->postJson("{$this->prefix}/posts/{$post->uuid}/like");

        $response->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADMIN: CATEGORIES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_list_categories_includes_inactive(): void
    {
        BlogCategory::factory()->count(2)->create();
        BlogCategory::factory()->inactive()->create();

        $admin = $this->adminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->adminPrefix}/categories");

        $response->assertOk()
            ->assertJsonCount(3, 'data.categories');
    }

    public function test_admin_categories_forbidden_for_regular_user(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->adminPrefix}/categories");

        $response->assertStatus(403);
    }

    public function test_admin_create_category(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->adminPrefix}/categories", [
                'name' => 'Pet Nutrition',
                'description' => 'Articles about pet nutrition',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.category.name', 'Pet Nutrition');

        $this->assertDatabaseHas('blog_categories', ['name' => 'Pet Nutrition']);
    }

    public function test_admin_create_category_validation(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->adminPrefix}/categories", []);

        $response->assertStatus(422);
    }

    public function test_admin_update_category(): void
    {
        $admin = $this->adminUser();
        $cat = BlogCategory::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/categories/{$cat->uuid}", [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.category.name', 'Updated Name');
    }

    public function test_admin_delete_category(): void
    {
        $admin = $this->adminUser();
        $cat = BlogCategory::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("{$this->adminPrefix}/categories/{$cat->uuid}");

        $response->assertOk();
        $this->assertDatabaseMissing('blog_categories', ['id' => $cat->id]);
    }

    public function test_admin_delete_nonexistent_category(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("{$this->adminPrefix}/categories/fake-uuid");

        $response->assertStatus(404);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADMIN: POSTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_list_all_posts(): void
    {
        $admin = $this->adminUser();
        BlogPost::factory()->published()->forAuthor($admin)->count(3)->create();
        BlogPost::factory()->draft()->forAuthor($admin)->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->adminPrefix}/posts");

        $response->assertOk()
            ->assertJsonCount(5, 'data.posts');
    }

    public function test_admin_list_posts_filter_status(): void
    {
        $admin = $this->adminUser();
        BlogPost::factory()->published()->forAuthor($admin)->count(3)->create();
        BlogPost::factory()->draft()->forAuthor($admin)->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->adminPrefix}/posts?status=draft");

        $response->assertOk()
            ->assertJsonCount(2, 'data.posts');
    }

    public function test_admin_create_post(): void
    {
        $admin = $this->adminUser();
        $cat = BlogCategory::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->adminPrefix}/posts", [
                'title' => 'How to Care for Your Dog',
                'content' => str_repeat('This is detailed content about dog care. ', 5),
                'category_id' => $cat->id,
                'status' => 'published',
                'tags' => ['dog-care', 'health'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.post.title', 'How to Care for Your Dog');

        $this->assertDatabaseHas('blog_posts', ['title' => 'How to Care for Your Dog']);
        $this->assertDatabaseHas('blog_tags', ['name' => 'dog-care']);
    }

    public function test_admin_create_post_validation(): void
    {
        $admin = $this->adminUser();

        // Missing required fields
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->adminPrefix}/posts", []);

        $response->assertStatus(422);

        // Content too short
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->adminPrefix}/posts", [
                'title' => 'Short',
                'content' => 'Too short',
            ]);

        $response->assertStatus(422);
    }

    public function test_admin_create_post_forbidden_for_user(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->adminPrefix}/posts", [
                'title' => 'Hack',
                'content' => str_repeat('Content. ', 20),
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_update_post(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->draft()->forAuthor($admin)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/posts/{$post->uuid}", [
                'title' => 'Updated Title',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.post.title', 'Updated Title');
    }

    public function test_admin_delete_post(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->draft()->forAuthor($admin)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("{$this->adminPrefix}/posts/{$post->uuid}");

        $response->assertOk();
        $this->assertSoftDeleted('blog_posts', ['id' => $post->id]);
    }

    public function test_admin_toggle_publish(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->draft()->forAuthor($admin)->create();

        // Draft → Published
        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/posts/{$post->uuid}/toggle-publish");

        $response->assertOk();
        $this->assertEquals('published', $post->fresh()->status);

        // Published → Draft
        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/posts/{$post->uuid}/toggle-publish");

        $response->assertOk();
        $this->assertEquals('draft', $post->fresh()->status);
    }

    public function test_admin_show_post(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->draft()->forAuthor($admin)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->adminPrefix}/posts/{$post->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.post.uuid', $post->uuid);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADMIN: COMMENTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_list_post_comments(): void
    {
        $admin = $this->adminUser();
        $post = BlogPost::factory()->published()->forAuthor($admin)->create();
        BlogComment::factory()->count(3)->create(['blog_post_id' => $post->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->adminPrefix}/posts/{$post->uuid}/comments");

        $response->assertOk()
            ->assertJsonCount(3, 'data.comments');
    }

    public function test_admin_approve_comment(): void
    {
        $admin = $this->adminUser();
        $comment = BlogComment::factory()->pending()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/comments/{$comment->uuid}/approve");

        $response->assertOk();
        $this->assertTrue($comment->fresh()->is_approved);
    }

    public function test_admin_unapprove_comment(): void
    {
        $admin = $this->adminUser();
        $comment = BlogComment::factory()->approved()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/comments/{$comment->uuid}/approve");

        $response->assertOk();
        $this->assertFalse($comment->fresh()->is_approved);
    }

    public function test_admin_delete_comment(): void
    {
        $admin = $this->adminUser();
        $comment = BlogComment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("{$this->adminPrefix}/comments/{$comment->uuid}");

        $response->assertOk();
        $this->assertSoftDeleted('blog_comments', ['id' => $comment->id]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADMIN: TAGS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_list_tags(): void
    {
        BlogTag::factory()->count(3)->create();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->adminPrefix}/tags");

        $response->assertOk()
            ->assertJsonCount(3, 'data.tags');
    }

    public function test_admin_create_tag(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->adminPrefix}/tags", [
                'name' => 'pet-health',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('blog_tags', ['name' => 'pet-health']);
    }

    public function test_admin_create_tag_duplicate(): void
    {
        $admin = $this->adminUser();
        BlogTag::factory()->create(['name' => 'existing']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->adminPrefix}/tags", [
                'name' => 'existing',
            ]);

        $response->assertStatus(422);
    }

    public function test_admin_create_tag_validation(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->adminPrefix}/tags", []);

        $response->assertStatus(422);
    }
}
