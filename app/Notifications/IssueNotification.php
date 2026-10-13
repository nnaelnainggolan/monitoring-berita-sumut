<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IssueNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(public int $issueId, public string $title, public string $message)
    {
        $this->afterCommit();
    }
    public function via(object $notifiable): array
    {
        return ($notifiable->notification_preferences['email'] ?? false) ? ['database', 'mail'] : ['database'];
    }
    public function shouldSend(object $notifiable, string $channel): bool { return (bool) $notifiable->is_active; }
    public function toArray(object $notifiable): array
    {
        return ['issue_id' => $this->issueId, 'title' => $this->title, 'message' => $this->message];
    }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Monitoring Sumut: '.$this->title)->greeting('Halo '.$notifiable->name)
            ->line($this->message)->action('Lihat isu', route('issues.show', $this->issueId));
    }
}
