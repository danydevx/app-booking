<?php

namespace Modules\Bookings\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerTaskDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{title: string, customer: string, due: string, overdue: bool}>  $tasks
     */
    public function __construct(
        public string $teamName,
        public array $tasks,
        public string $todayUrl,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Tareas pendientes',
            'message' => "Tienes {$this->count()} tareas por atender en {$this->teamName}.",
            'url' => $this->todayUrl,
            'kind' => 'task',
        ];
    }

    private function count(): int
    {
        return count($this->tasks);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->from(Branding::fromAddress(), Branding::appName())
            ->subject("Tareas pendientes · {$this->teamName}")
            ->greeting('Hola')
            ->line('Estas son las tareas de seguimiento que requieren tu atención:');

        foreach ($this->tasks as $task) {
            $message->line(sprintf(
                '%s %s · %s (%s)',
                $task['overdue'] ? 'Vencida:' : 'Hoy:',
                $task['title'],
                $task['customer'],
                $task['due'],
            ));
        }

        return $message->action('Abrir tablero Hoy', $this->todayUrl);
    }
}
