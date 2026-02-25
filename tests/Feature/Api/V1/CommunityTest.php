<?php

namespace Tests\Feature\Api\V1;

use App\Models\CommunityPost;
use App\Models\CommunityReply;
use App\Models\CommunityReport;
use App\Models\CommunityTopic;
use App\Models\CommunityVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/community';
    private string $adminPrefix = '/api/v1/admin/community';

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function authUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIC: TOPICS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_public_list_topics(): void
    {
        CommunityTopic::factory()->count(3)->create();
        CommunityTopic::factory()->inactive()->create();

        $response = $this->getJson("{$this->prefix}/topics");

        $response->assertOk()
            ->assertJsonCount(3, 'data.topics');
    }

    public function test_public_list_topics_empty(): void
    {
        $response = $this->getJson("{$this->prefix}/topics");

        $response->assertOk()
            ->assertJsonCount(0, 'data.topics');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIC: POSTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_public_list_posts_visible_only(): void
    {
        $topic = CommunityTopic::factory()->create();
        CommunityPost::factory()->count(3)->forTopic($topic)->create();
        CommunityPost::factory()->hidden()->forTopic($topic)->create();

        $response = $this->getJson("{$this->prefix}/posts");

        $response->assertOk()
            ->assertJsonCount(3, 'data.posts');
    }

    public function test_public_list_posts_filter_by_topic(): void
    {
        $topic1 = CommunityTopic::factory()->create();
        $topic2 = CommunityTopic::factory()->create();
        CommunityPost::factory()->count(2)->forTopic($topic1)->create();
        CommunityPost::factory()->count(3)->forTopic($topic2)->create();

        $response = $this->getJson("{$this->prefix}/posts?topic={$topic1->slug}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.posts');
    }

    public function test_public_list_posts_pagination(): void
    {
        $topic = CommunityTopic::factory()->create();
        CommunityPost::factory()->count(20)->forTopic($topic)->create();

        $response = $this->getJson("{$this->prefix}/posts?per_page=5");

        $response->assertOk()
            ->assertJsonCount(5, 'data.posts')
            ->assertJsonPath('data.pagination.total', 20);
    }

    public function test_public_show_post(): void
    {
        $post = CommunityPost::factory()->create();

        $response = $this->getJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.post.uuid', $post->uuid);
    }

    public function test_public_show_hidden_post_404(): void
    {
        $post = CommunityPost::factory()->hidden()->create();

        $response = $this->getJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertStatus(404);
    }

    public function test_public_show_post_includes_user_voted_flag(): void
    {
        $user = $this->authUser();
        $post = CommunityPost::factory()->create();

        CommunityVote::create([
            'votable_type' => CommunityPost::class,
            'votable_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.post.user_voted', true);
    }

    public function test_public_show_post_user_voted_false_for_guests(): void
    {
        $post = CommunityPost::factory()->create();

        $response = $this->getJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.post.user_voted', false);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIC: REPLIES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_public_list_post_replies(): void
    {
        $post = CommunityPost::factory()->create();
        CommunityReply::factory()->count(3)->forPost($post)->create();

        $response = $this->getJson("{$this->prefix}/posts/{$post->uuid}/replies");

        $response->assertOk()
            ->assertJsonCount(3, 'data.replies');
    }

    public function test_public_list_replies_nonexistent_post(): void
    {
        $response = $this->getJson("{$this->prefix}/posts/fake-uuid/replies");

        $response->assertStatus(404);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUTHENTICATED: CREATE POST
    // ═══════════════════════════════════════════════════════════════════════

    public function test_auth_create_post(): void
    {
        $user = $this->authUser();
        $topic = CommunityTopic::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts", [
                'topic_uuid' => $topic->uuid,
                'title' => 'My First Community Post',
                'content' => 'This is a detailed community post about pets.',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('community_posts', [
            'user_id' => $user->id,
            'title' => 'My First Community Post',
        ]);
    }

    public function test_auth_create_post_requires_auth(): void
    {
        $topic = CommunityTopic::factory()->create();

        $response = $this->postJson("{$this->prefix}/posts", [
            'topic_uuid' => $topic->uuid,
            'title' => 'Unauth',
            'content' => 'Should fail without login.',
        ]);

        $response->assertStatus(401);
    }

    public function test_auth_create_post_inactive_topic(): void
    {
        $user = $this->authUser();
        $topic = CommunityTopic::factory()->inactive()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts", [
                'topic_uuid' => $topic->uuid,
                'title' => 'Post in Inactive Topic',
                'content' => 'This should fail because topic is inactive.',
            ]);

        $response->assertStatus(404);
    }

    public function test_auth_create_post_validation(): void
    {
        $user = $this->authUser();

        // Missing all fields
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts", []);

        $response->assertStatus(422);

        // Title too short
        $topic = CommunityTopic::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts", [
                'topic_uuid' => $topic->uuid,
                'title' => 'Hi',
                'content' => 'Short title should fail validation.',
            ]);

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUTHENTICATED: DELETE POST
    // ═══════════════════════════════════════════════════════════════════════

    public function test_auth_delete_own_post(): void
    {
        $user = $this->authUser();
        $post = CommunityPost::factory()->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertOk();
        $this->assertSoftDeleted('community_posts', ['id' => $post->id]);
    }

    public function test_auth_delete_others_post_forbidden(): void
    {
        $user = $this->authUser();
        $other = $this->authUser();
        $post = CommunityPost::factory()->forUser($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_any_post(): void
    {
        $admin = $this->adminUser();
        $user = $this->authUser();
        $post = CommunityPost::factory()->forUser($user)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("{$this->prefix}/posts/{$post->uuid}");

        $response->assertOk();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUTHENTICATED: REPLIES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_auth_create_reply(): void
    {
        $user = $this->authUser();
        $post = CommunityPost::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/{$post->uuid}/replies", [
                'content' => 'This is a thoughtful reply to the post.',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_auth_create_nested_reply(): void
    {
        $user = $this->authUser();
        $post = CommunityPost::factory()->create();
        $parentReply = CommunityReply::factory()->forPost($post)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/{$post->uuid}/replies", [
                'content' => 'This is a nested reply.',
                'parent_uuid' => $parentReply->uuid,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('community_replies', [
            'post_id' => $post->id,
            'parent_id' => $parentReply->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_auth_create_reply_locked_post(): void
    {
        $user = $this->authUser();
        $post = CommunityPost::factory()->locked()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/{$post->uuid}/replies", [
                'content' => 'Trying to reply to locked post.',
            ]);

        $response->assertStatus(403);
    }

    public function test_auth_create_reply_invalid_parent(): void
    {
        $user = $this->authUser();
        $post = CommunityPost::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/posts/{$post->uuid}/replies", [
                'content' => 'Reply with bad parent.',
                'parent_uuid' => 'nonexistent-uuid',
            ]);

        $response->assertStatus(422);
    }

    public function test_auth_delete_own_reply(): void
    {
        $user = $this->authUser();
        $reply = CommunityReply::factory()->forUser($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("{$this->prefix}/replies/{$reply->uuid}");

        $response->assertOk();
    }

    public function test_auth_delete_others_reply_forbidden(): void
    {
        $user = $this->authUser();
        $other = $this->authUser();
        $reply = CommunityReply::factory()->forUser($other)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("{$this->prefix}/replies/{$reply->uuid}");

        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUTHENTICATED: VOTES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_auth_vote_on_post(): void
    {
        $user = $this->authUser();
        $post = CommunityPost::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/votes", [
                'votable_type' => 'post',
                'votable_uuid' => $post->uuid,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.voted', true)
            ->assertJsonPath('data.votes_count', 1);
    }

    public function test_auth_vote_toggle_off(): void
    {
        $user = $this->authUser();
        $post = CommunityPost::factory()->create();

        CommunityVote::create([
            'votable_type' => CommunityPost::class,
            'votable_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/votes", [
                'votable_type' => 'post',
                'votable_uuid' => $post->uuid,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.voted', false)
            ->assertJsonPath('data.votes_count', 0);
    }

    public function test_auth_vote_on_reply(): void
    {
        $user = $this->authUser();
        $reply = CommunityReply::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/votes", [
                'votable_type' => 'reply',
                'votable_uuid' => $reply->uuid,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.voted', true);
    }

    public function test_auth_vote_nonexistent_content(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/votes", [
                'votable_type' => 'post',
                'votable_uuid' => 'nonexistent-uuid',
            ]);

        $response->assertStatus(404);
    }

    public function test_auth_vote_invalid_type(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/votes", [
                'votable_type' => 'comment',
                'votable_uuid' => 'some-uuid',
            ]);

        $response->assertStatus(422);
    }

    public function test_auth_vote_requires_auth(): void
    {
        $response = $this->postJson("{$this->prefix}/votes", [
            'votable_type' => 'post',
            'votable_uuid' => 'some-uuid',
        ]);

        $response->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUTHENTICATED: REPORTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_auth_report_post(): void
    {
        $user = $this->authUser();
        $post = CommunityPost::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/reports", [
                'reportable_type' => 'post',
                'reportable_uuid' => $post->uuid,
                'reason' => 'This content is inappropriate and violates community guidelines.',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('community_reports', [
            'user_id' => $user->id,
            'reportable_type' => CommunityPost::class,
        ]);
    }

    public function test_auth_report_reply(): void
    {
        $user = $this->authUser();
        $reply = CommunityReply::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/reports", [
                'reportable_type' => 'reply',
                'reportable_uuid' => $reply->uuid,
                'reason' => 'This reply contains spam and irrelevant content.',
            ]);

        $response->assertStatus(201);
    }

    public function test_auth_report_validation(): void
    {
        $user = $this->authUser();

        // Missing fields
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/reports", []);

        $response->assertStatus(422);

        // Reason too short
        $post = CommunityPost::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->prefix}/reports", [
                'reportable_type' => 'post',
                'reportable_uuid' => $post->uuid,
                'reason' => 'Short',
            ]);

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADMIN: TOPICS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_create_topic(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->adminPrefix}/topics", [
                'name' => 'Pet Health Discussion',
                'description' => 'Discuss pet health topics',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('community_topics', ['name' => 'Pet Health Discussion']);
    }

    public function test_admin_create_topic_forbidden_for_user(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("{$this->adminPrefix}/topics", [
                'name' => 'Hack Topic',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_update_topic(): void
    {
        $admin = $this->adminUser();
        $topic = CommunityTopic::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/topics/{$topic->uuid}", [
                'name' => 'Updated Topic Name',
            ]);

        $response->assertOk();
        $this->assertEquals('Updated Topic Name', $topic->fresh()->name);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADMIN: POST MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_list_all_posts(): void
    {
        $admin = $this->adminUser();
        CommunityPost::factory()->count(3)->create();
        CommunityPost::factory()->hidden()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->adminPrefix}/posts");

        $response->assertOk()
            ->assertJsonCount(5, 'data.posts');
    }

    public function test_admin_toggle_lock(): void
    {
        $admin = $this->adminUser();
        $post = CommunityPost::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/posts/{$post->uuid}/lock");

        $response->assertOk();
        $this->assertTrue($post->fresh()->is_locked);

        // Toggle back
        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/posts/{$post->uuid}/lock");

        $response->assertOk();
        $this->assertFalse($post->fresh()->is_locked);
    }

    public function test_admin_toggle_visibility(): void
    {
        $admin = $this->adminUser();
        $post = CommunityPost::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/posts/{$post->uuid}/toggle-visibility");

        $response->assertOk();
        $this->assertTrue($post->fresh()->is_hidden);
    }

    public function test_admin_delete_post(): void
    {
        $admin = $this->adminUser();
        $post = CommunityPost::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("{$this->adminPrefix}/posts/{$post->uuid}");

        $response->assertOk();
        $this->assertSoftDeleted('community_posts', ['id' => $post->id]);
    }

    public function test_admin_delete_reply(): void
    {
        $admin = $this->adminUser();
        $reply = CommunityReply::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("{$this->adminPrefix}/replies/{$reply->uuid}");

        $response->assertOk();
        $this->assertSoftDeleted('community_replies', ['id' => $reply->id]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADMIN: REPORTS
    // ═══════════════════════════════════════════════════════════════════════

    public function test_admin_list_pending_reports(): void
    {
        $admin = $this->adminUser();
        $post = CommunityPost::factory()->create();
        CommunityReport::create([
            'reportable_type' => CommunityPost::class,
            'reportable_id' => $post->id,
            'user_id' => $this->authUser()->id,
            'reason' => 'Inappropriate content that needs review.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("{$this->adminPrefix}/reports");

        $response->assertOk()
            ->assertJsonCount(1, 'data.reports');
    }

    public function test_admin_review_report(): void
    {
        $admin = $this->adminUser();
        $post = CommunityPost::factory()->create();
        $report = CommunityReport::create([
            'reportable_type' => CommunityPost::class,
            'reportable_id' => $post->id,
            'user_id' => $this->authUser()->id,
            'reason' => 'Inappropriate content that needs review.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/reports/{$report->uuid}", [
                'status' => 'reviewed',
                'admin_notes' => 'Content has been reviewed and is fine.',
            ]);

        $response->assertOk();
        $this->assertEquals('reviewed', $report->fresh()->status);
        $this->assertEquals($admin->id, $report->fresh()->reviewed_by);
    }

    public function test_admin_dismiss_report(): void
    {
        $admin = $this->adminUser();
        $post = CommunityPost::factory()->create();
        $report = CommunityReport::create([
            'reportable_type' => CommunityPost::class,
            'reportable_id' => $post->id,
            'user_id' => $this->authUser()->id,
            'reason' => 'False report that should be dismissed.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/reports/{$report->uuid}", [
                'status' => 'dismissed',
            ]);

        $response->assertOk();
        $this->assertEquals('dismissed', $report->fresh()->status);
    }

    public function test_admin_review_report_validation(): void
    {
        $admin = $this->adminUser();

        $post = CommunityPost::factory()->create();
        $report = CommunityReport::create([
            'reportable_type' => CommunityPost::class,
            'reportable_id' => $post->id,
            'user_id' => $this->authUser()->id,
            'reason' => 'Another report for validation testing.',
        ]);

        // Invalid status
        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->adminPrefix}/reports/{$report->uuid}", [
                'status' => 'invalid',
            ]);

        $response->assertStatus(422);
    }

    public function test_admin_reports_forbidden_for_user(): void
    {
        $user = $this->authUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->adminPrefix}/reports");

        $response->assertStatus(403);
    }
}
