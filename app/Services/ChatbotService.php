<?php

namespace App\Services;

use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotService
{
    private const MAX_CONTEXT_MESSAGES = 10;
    private const DISCLAIMER = "\n\n⚠️ *This information is for general guidance only. Always consult a licensed veterinarian for medical decisions.*";

    /**
     * Start a new chatbot session, optionally scoped to a pet.
     */
    public function createSession(User $user, ?int $petId = null): ChatbotSession
    {
        // Validate pet ownership
        if ($petId !== null) {
            $pet = $user->pets()->findOrFail($petId);
        }

        return ChatbotSession::create([
            'user_id' => $user->id,
            'pet_id'  => $petId,
            'status'  => 'active',
            'metadata' => [
                'model'           => config('services.openai.model', 'gpt-4o-mini'),
                'system_prompt_v' => '1.0',
            ],
        ]);
    }

    /**
     * Send a user message and get an AI assistant reply.
     *
     * @throws \RuntimeException on OpenAI failure
     * @throws \InvalidArgumentException on closed session or invalid input
     */
    public function sendMessage(ChatbotSession $session, string $content, User $user): ChatbotMessage
    {
        if ($session->user_id !== $user->id) {
            throw new \InvalidArgumentException('Session does not belong to this user.');
        }

        if (!$session->isActive()) {
            throw new \InvalidArgumentException('Cannot send messages to a closed session.');
        }

        // Sanitize input — strip HTML/script tags
        $content = strip_tags(trim($content));

        if (empty($content)) {
            throw new \InvalidArgumentException('Message content cannot be empty.');
        }

        // Build the system prompt
        $systemPrompt = $this->buildSystemPrompt($session, $user);

        // Load recent context (last N messages)
        $recentMessages = $session->messages()
            ->orderBy('created_at', 'desc')
            ->limit(self::MAX_CONTEXT_MESSAGES)
            ->get()
            ->reverse()
            ->map(fn($msg) => ['role' => $msg->role, 'content' => $msg->content])
            ->values()
            ->all();

        // Append the new user message
        $recentMessages[] = ['role' => 'user', 'content' => $content];

        // Save user message first
        $userMessage = ChatbotMessage::create([
            'session_id' => $session->id,
            'role'       => 'user',
            'content'    => $content,
        ]);

        // Call OpenAI
        $apiKey = config('services.openai.key', '');
        $model  = config('services.openai.model', 'gpt-4o-mini');

        if (empty($apiKey)) {
            Log::warning('OpenAI API key not configured — chatbot unavailable');
            // Return a graceful fallback so the app doesn't crash
            return ChatbotMessage::create([
                'session_id' => $session->id,
                'role'       => 'assistant',
                'content'    => 'The AI assistant is temporarily unavailable. Please try again later.' . self::DISCLAIMER,
            ]);
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'      => $model,
                'messages'   => array_merge(
                    [['role' => 'system', 'content' => $systemPrompt]],
                    $recentMessages
                ),
                'max_tokens' => 800,
                'temperature' => 0.7,
            ]);

        if ($response->failed()) {
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('AI service temporarily unavailable. Please try again.');
        }

        $data            = $response->json();
        $assistantText   = $data['choices'][0]['message']['content'] ?? '';
        $tokensUsed      = $data['usage']['total_tokens'] ?? null;

        // Always append disclaimer — AI health advice must always include the veterinarian caveat
        $assistantText .= self::DISCLAIMER;

        $assistantMessage = ChatbotMessage::create([
            'session_id'  => $session->id,
            'role'        => 'assistant',
            'content'     => $assistantText,
            'tokens_used' => $tokensUsed,
        ]);

        return $assistantMessage;
    }

    /**
     * Build the system prompt, optionally injecting pet context.
     */
    private function buildSystemPrompt(ChatbotSession $session, User $user): string
    {
        $petContext = '';

        if ($session->pet_id && $session->pet) {
            $pet = $session->pet;
            $age = $pet->birth_date
                ? now()->diffInYears($pet->birth_date) . ' years'
                : 'unknown age';

            $activeMeds = $pet->medications()
                ->where('is_active', true)
                ->pluck('name')
                ->implode(', ');

            $petContext = "\n\nPet context:"
                . "\n- Name: {$pet->name}"
                . "\n- Species: {$pet->species}"
                . "\n- Breed: " . ($pet->breed ?? 'unknown')
                . "\n- Age: {$age}"
                . "\n- Active medications: " . ($activeMeds ?: 'none');
        }

        return "You are a helpful, empathetic pet health assistant for the PetHelp app."
            . " You help pet owners understand symptoms, care tips, nutrition, and wellness."
            . $petContext
            . "\n\nIMPORTANT RULES:"
            . "\n1. You are NOT a veterinarian and cannot diagnose or prescribe."
            . "\n2. Always recommend consulting a licensed veterinarian for any medical concern."
            . "\n3. Be concise but thorough. Use simple language."
            . "\n4. If asked about emergencies (difficulty breathing, seizures, poisoning), "
            . "immediately advise the user to contact an emergency vet."
            . "\n5. Never suggest specific dosages of any medication.";
    }
}
