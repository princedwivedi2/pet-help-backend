<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatbotSession;
use App\Services\ChatbotService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ChatbotService $chatbotService
    ) {}

    /**
     * List all sessions for the authenticated user.
     * GET /api/v1/chatbot/sessions
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = ChatbotSession::where('user_id', $request->user()->id)
            ->withCount('messages')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success('Sessions retrieved', [
            'sessions'   => $sessions->items(),
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
                'per_page'     => $sessions->perPage(),
                'total'        => $sessions->total(),
            ],
        ]);
    }

    /**
     * Start a new chatbot session.
     * POST /api/v1/chatbot/sessions
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'pet_id' => ['nullable', 'integer', 'exists:pets,id'],
        ]);

        $petId = $request->integer('pet_id') ?: null;

        // Validate pet belongs to this user if provided
        if ($petId !== null) {
            $pet = $request->user()->pets()->find($petId);
            if (!$pet) {
                return $this->validationError('Pet not found', [
                    'pet_id' => ['The specified pet does not belong to you.'],
                ]);
            }
        }

        $session = $this->chatbotService->createSession($request->user(), $petId);

        return $this->created('Session started', ['session' => $session]);
    }

    /**
     * Get a specific session (ownership enforced).
     * GET /api/v1/chatbot/sessions/{uuid}
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $session = $this->findSession($request, $uuid);

        if (!$session) {
            return $this->notFound('Session not found');
        }

        return $this->success('Session retrieved', [
            'session' => $session->load('pet'),
        ]);
    }

    /**
     * Close/soft-delete a session.
     * DELETE /api/v1/chatbot/sessions/{uuid}
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $session = $this->findSession($request, $uuid);

        if (!$session) {
            return $this->notFound('Session not found');
        }

        $session->close();
        $session->delete();

        return $this->success('Session closed');
    }

    /**
     * Send a message and get an AI reply.
     * POST /api/v1/chatbot/sessions/{uuid}/messages
     */
    public function sendMessage(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $session = $this->findSession($request, $uuid);

        if (!$session) {
            return $this->notFound('Session not found');
        }

        if (!$session->isActive()) {
            return $this->error('This session is closed. Please start a new session.', null, 422);
        }

        try {
            $reply = $this->chatbotService->sendMessage(
                $session,
                $request->input('content'),
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return $this->validationError($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), null, 503);
        }

        return $this->success('Message sent', ['message' => $reply]);
    }

    /**
     * Get paginated message history for a session.
     * GET /api/v1/chatbot/sessions/{uuid}/messages
     */
    public function messages(Request $request, string $uuid): JsonResponse
    {
        $session = $this->findSession($request, $uuid);

        if (!$session) {
            return $this->notFound('Session not found');
        }

        $messages = $session->messages()
            ->orderBy('created_at')
            ->paginate($request->integer('per_page', 50));

        return $this->success('Messages retrieved', [
            'messages'   => $messages->items(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }

    /**
     * Find a session belonging to the authenticated user.
     */
    private function findSession(Request $request, string $uuid): ?ChatbotSession
    {
        return ChatbotSession::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->first();
    }
}
