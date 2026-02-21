<?php

namespace App\Services;

use App\Models\CommunityPost;
use App\Models\CommunityReply;
use App\Models\CommunityReport;
use App\Models\CommunityTopic;
use App\Models\CommunityVote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommunityService
{
    // ─── Topics ─────────────────────────────────────────────────────

    public function getActiveTopics(): Collection
    {
        return CommunityTopic::active()
            ->withCount(['posts' => fn ($q) => $q->visible()])
            ->orderBy('name')
            ->get();
    }

    public function findTopicByUuid(string $uuid): ?CommunityTopic
    {
        return CommunityTopic::where('uuid', $uuid)->first();
    }

    public function createTopic(array $data): CommunityTopic
    {
        return CommunityTopic::create($data);
    }

    public function updateTopic(CommunityTopic $topic, array $data): CommunityTopic
    {
        $topic->update($data);
        return $topic->fresh();
    }

    // ─── Posts ──────────────────────────────────────────────────────

    public function getVisiblePosts(int $perPage = 15, ?string $topicSlug = null): LengthAwarePaginator
    {
        $query = CommunityPost::visible()
            ->with(['user:id,name', 'topic:id,uuid,name,slug'])
            ->withCount(['replies', 'votes']);

        if ($topicSlug) {
            $query->whereHas('topic', fn ($q) => $q->where('slug', $topicSlug));
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getAllPosts(int $perPage = 20): LengthAwarePaginator
    {
        return CommunityPost::with(['user:id,name', 'topic:id,uuid,name,slug'])
            ->withCount(['replies', 'votes'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findPostByUuid(string $uuid, bool $visibleOnly = true): ?CommunityPost
    {
        $query = CommunityPost::where('uuid', $uuid)
            ->with(['user:id,name', 'topic:id,uuid,name,slug'])
            ->withCount(['replies', 'votes']);

        if ($visibleOnly) {
            $query->visible();
        }

        return $query->first();
    }

    public function createPost(int $topicId, int $userId, array $data): CommunityPost
    {
        $post = CommunityPost::create([
            'topic_id' => $topicId,
            'user_id'  => $userId,
            'title'    => $data['title'],
            'content'  => $data['content'],
        ]);

        return $post->load(['user:id,name', 'topic:id,uuid,name,slug']);
    }

    public function deletePost(CommunityPost $post): bool
    {
        return $post->delete();
    }

    public function toggleLock(CommunityPost $post): CommunityPost
    {
        $post->update(['is_locked' => !$post->is_locked]);
        return $post->fresh(['user:id,name', 'topic:id,uuid,name,slug']);
    }

    public function toggleVisibility(CommunityPost $post): CommunityPost
    {
        $post->update(['is_hidden' => !$post->is_hidden]);
        return $post->fresh(['user:id,name', 'topic:id,uuid,name,slug']);
    }

    // ─── Replies ────────────────────────────────────────────────────

    public function getPostReplies(CommunityPost $post, int $perPage = 20): LengthAwarePaginator
    {
        return CommunityReply::where('post_id', $post->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name',
                'children' => fn ($q) => $q->with('user:id,name')->withCount('votes'),
                'children.children' => fn ($q) => $q->with('user:id,name')->withCount('votes'),
            ])
            ->withCount('votes')
            ->orderBy('created_at')
            ->paginate($perPage);
    }

    public function createReply(CommunityPost $post, int $userId, array $data): CommunityReply
    {
        $reply = CommunityReply::create([
            'post_id'   => $post->id,
            'user_id'   => $userId,
            'content'   => $data['content'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return $reply->load('user:id,name');
    }

    public function findReplyByUuid(string $uuid): ?CommunityReply
    {
        return CommunityReply::where('uuid', $uuid)->first();
    }

    public function deleteReply(CommunityReply $reply): bool
    {
        return $reply->delete();
    }

    // ─── Votes ──────────────────────────────────────────────────────

    public function toggleVote(string $votableType, int $votableId, int $userId): array
    {
        return DB::transaction(function () use ($votableType, $votableId, $userId) {
            $existing = CommunityVote::where('votable_type', $votableType)
                ->where('votable_id', $votableId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                $count = CommunityVote::where('votable_type', $votableType)
                    ->where('votable_id', $votableId)->count();
                return ['voted' => false, 'votes_count' => $count];
            }

            CommunityVote::create([
                'votable_type' => $votableType,
                'votable_id'   => $votableId,
                'user_id'      => $userId,
            ]);

            $count = CommunityVote::where('votable_type', $votableType)
                ->where('votable_id', $votableId)->count();

            return ['voted' => true, 'votes_count' => $count];
        });
    }

    // ─── Reports ────────────────────────────────────────────────────

    public function createReport(
        string $reportableType,
        int $reportableId,
        int $userId,
        array $data
    ): CommunityReport {
        $report = CommunityReport::create([
            'reportable_type' => $reportableType,
            'reportable_id'   => $reportableId,
            'user_id'         => $userId,
            'reason'          => $data['reason'],
        ]);

        return $report->load('user:id,name');
    }

    public function getPendingReports(int $perPage = 20): LengthAwarePaginator
    {
        return CommunityReport::pending()
            ->with(['user:id,name', 'reportable'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findReportByUuid(string $uuid): ?CommunityReport
    {
        return CommunityReport::where('uuid', $uuid)->first();
    }

    public function reviewReport(CommunityReport $report, int $adminId, array $data): CommunityReport
    {
        $report->update([
            'status'      => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? null,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
        ]);

        Log::info('Community report reviewed', [
            'report_uuid' => $report->uuid,
            'admin_id'    => $adminId,
            'status'      => $data['status'],
        ]);

        return $report->fresh(['user:id,name', 'reviewer:id,name', 'reportable']);
    }
}
