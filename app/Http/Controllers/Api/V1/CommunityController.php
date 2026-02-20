<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Community\ReviewCommunityReportRequest;
use App\Http\Requests\Api\V1\Community\StoreCommunityPostRequest;
use App\Http\Requests\Api\V1\Community\StoreCommunityReplyRequest;
use App\Http\Requests\Api\V1\Community\StoreCommunityReportRequest;
use App\Http\Requests\Api\V1\Community\StoreCommunityTopicRequest;
use App\Http\Requests\Api\V1\Community\StoreCommunityVoteRequest;
use App\Http\Requests\Api\V1\Community\UpdateCommunityTopicRequest;
use App\Models\CommunityPost;
use App\Models\CommunityReply;
use App\Models\CommunityVote;
use App\Services\CommunityService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CommunityService $communityService
    ) {}

    // ─── Public ─────────────────────────────────────────────────────

    public function topics(): JsonResponse
    {
        $topics = $this->communityService->getActiveTopics();

        return $this->success('Topics retrieved successfully', ['topics' => $topics]);
    }

    public function posts(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 15), 50);

        $posts = $this->communityService->getVisiblePosts($perPage, $request->topic);

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
        $post = $this->communityService->findPostByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $postData = $post->toArray();
        $postData['user_voted'] = false;

        if (auth('sanctum')->check()) {
            $postData['user_voted'] = CommunityVote::where('votable_type', CommunityPost::class)
                ->where('votable_id', $post->id)
                ->where('user_id', auth('sanctum')->id())
                ->exists();
        }

        return $this->success('Post retrieved successfully', ['post' => $postData]);
    }

    public function postReplies(Request $request, string $uuid): JsonResponse
    {
        $post = $this->communityService->findPostByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $replies = $this->communityService->getPostReplies($post, $perPage);

        return $this->success('Replies retrieved successfully', [
            'replies'    => $replies->items(),
            'pagination' => [
                'current_page' => $replies->currentPage(),
                'last_page'    => $replies->lastPage(),
                'per_page'     => $replies->perPage(),
                'total'        => $replies->total(),
            ],
        ]);
    }

    // ─── Authenticated ──────────────────────────────────────────────

    public function storePost(StoreCommunityPostRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $topic = $this->communityService->findTopicByUuid($validated['topic_uuid']);

        if (!$topic || !$topic->is_active) {
            return $this->notFound('Topic not found or inactive');
        }

        $post = $this->communityService->createPost(
            $topic->id,
            $request->user()->id,
            $validated
        );

        return $this->created('Post created successfully', ['post' => $post]);
    }

    public function storeReply(StoreCommunityReplyRequest $request, string $uuid): JsonResponse
    {
        $post = $this->communityService->findPostByUuid($uuid);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        if ($post->is_locked) {
            return $this->forbidden('This post is locked and no longer accepts replies.');
        }

        $data = $request->validated();

        if (!empty($data['parent_uuid'])) {
            $parent = $this->communityService->findReplyByUuid($data['parent_uuid']);

            if (!$parent || $parent->post_id !== $post->id) {
                return $this->validationError('Invalid parent reply', [
                    'parent_uuid' => ['Parent reply not found or belongs to a different post.'],
                ]);
            }

            $data['parent_id'] = $parent->id;
        }

        $reply = $this->communityService->createReply(
            $post,
            $request->user()->id,
            $data
        );

        return $this->created('Reply created successfully', ['reply' => $reply]);
    }

    public function destroyPost(Request $request, string $uuid): JsonResponse
    {
        $post = $this->communityService->findPostByUuid($uuid, false);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        if ($post->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return $this->forbidden('You can only delete your own posts.');
        }

        $this->communityService->deletePost($post);

        return $this->success('Post deleted successfully');
    }

    public function destroyReply(Request $request, string $uuid): JsonResponse
    {
        $reply = $this->communityService->findReplyByUuid($uuid);

        if (!$reply) {
            return $this->notFound('Reply not found');
        }

        if ($reply->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return $this->forbidden('You can only delete your own replies.');
        }

        $this->communityService->deleteReply($reply);

        return $this->success('Reply deleted successfully');
    }

    public function vote(StoreCommunityVoteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['votable_type'] === 'post') {
            $votable = CommunityPost::where('uuid', $validated['votable_uuid'])->first();
            $votableType = CommunityPost::class;
        } else {
            $votable = CommunityReply::where('uuid', $validated['votable_uuid'])->first();
            $votableType = CommunityReply::class;
        }

        if (!$votable) {
            return $this->notFound('Content not found');
        }

        $result = $this->communityService->toggleVote(
            $votableType,
            $votable->id,
            $request->user()->id
        );

        $message = $result['voted'] ? 'Vote added' : 'Vote removed';

        return $this->success($message, $result);
    }

    public function report(StoreCommunityReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['reportable_type'] === 'post') {
            $reportable = CommunityPost::where('uuid', $validated['reportable_uuid'])->first();
            $reportableType = CommunityPost::class;
        } else {
            $reportable = CommunityReply::where('uuid', $validated['reportable_uuid'])->first();
            $reportableType = CommunityReply::class;
        }

        if (!$reportable) {
            return $this->notFound('Content not found');
        }

        $report = $this->communityService->createReport(
            $reportableType,
            $reportable->id,
            $request->user()->id,
            $validated
        );

        return $this->created('Report submitted successfully', ['report' => $report]);
    }

    // ─── Admin ──────────────────────────────────────────────────────

    public function storeTopic(StoreCommunityTopicRequest $request): JsonResponse
    {
        $topic = $this->communityService->createTopic($request->validated());

        return $this->created('Topic created successfully', ['topic' => $topic]);
    }

    public function updateTopic(UpdateCommunityTopicRequest $request, string $uuid): JsonResponse
    {
        $topic = $this->communityService->findTopicByUuid($uuid);

        if (!$topic) {
            return $this->notFound('Topic not found');
        }

        $topic = $this->communityService->updateTopic($topic, $request->validated());

        return $this->success('Topic updated successfully', ['topic' => $topic]);
    }

    public function adminPosts(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $posts = $this->communityService->getAllPosts($perPage);

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

    public function toggleLock(string $uuid): JsonResponse
    {
        $post = $this->communityService->findPostByUuid($uuid, false);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $post = $this->communityService->toggleLock($post);
        $action = $post->is_locked ? 'locked' : 'unlocked';

        return $this->success("Post {$action}", ['post' => $post]);
    }

    public function toggleVisibility(string $uuid): JsonResponse
    {
        $post = $this->communityService->findPostByUuid($uuid, false);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $post = $this->communityService->toggleVisibility($post);
        $action = $post->is_hidden ? 'hidden' : 'visible';

        return $this->success("Post is now {$action}", ['post' => $post]);
    }

    public function adminDestroyPost(string $uuid): JsonResponse
    {
        $post = CommunityPost::where('uuid', $uuid)->first();

        if (!$post) {
            return $this->notFound('Post not found');
        }

        $this->communityService->deletePost($post);

        return $this->success('Post deleted successfully');
    }

    public function adminDestroyReply(string $uuid): JsonResponse
    {
        $reply = $this->communityService->findReplyByUuid($uuid);

        if (!$reply) {
            return $this->notFound('Reply not found');
        }

        $this->communityService->deleteReply($reply);

        return $this->success('Reply deleted successfully');
    }

    public function reports(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 100);

        $reports = $this->communityService->getPendingReports($perPage);

        return $this->success('Reports retrieved successfully', [
            'reports'    => $reports->items(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page'    => $reports->lastPage(),
                'per_page'     => $reports->perPage(),
                'total'        => $reports->total(),
            ],
        ]);
    }

    public function reviewReport(ReviewCommunityReportRequest $request, string $uuid): JsonResponse
    {
        $report = $this->communityService->findReportByUuid($uuid);

        if (!$report) {
            return $this->notFound('Report not found');
        }

        $report = $this->communityService->reviewReport(
            $report,
            $request->user()->id,
            $request->validated()
        );

        return $this->success('Report reviewed', ['report' => $report]);
    }
}
