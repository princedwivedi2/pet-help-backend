<?php

namespace App\Notifications;

use App\Models\BlogComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BlogCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        private BlogComment $comment,
        private string $action = 'submitted'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $messages = [
            'submitted' => "New comment on \"{$this->comment->blogPost?->title}\" pending approval.",
            'approved'  => "Your comment on \"{$this->comment->blogPost?->title}\" has been approved.",
        ];

        return [
            'type'          => 'blog_comment_' . $this->action,
            'comment_uuid'  => $this->comment->uuid,
            'post_uuid'     => $this->comment->blogPost?->uuid,
            'post_title'    => $this->comment->blogPost?->title,
            'user_name'     => $this->comment->user?->name,
            'message'       => $messages[$this->action] ?? 'Blog comment update.',
        ];
    }
}
