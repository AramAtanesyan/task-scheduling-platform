<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReassignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $task;
    protected $previousUser;

    /**
     * Create a new notification instance.
     *
     * @param Task $task
     * @param User|null $previousUser
     * @return void
     */
    public function __construct(Task $task, $previousUser = null)
    {
        $this->task = $task;
        $this->previousUser = $previousUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject('Task Reassigned: ' . $this->task->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A task has been reassigned to you.');

        if ($this->previousUser) {
            $mailMessage->line('**Previously assigned to:** ' . $this->previousUser->name);
        }

        return $mailMessage
            ->line('**Task:** ' . $this->task->title)
            ->line('**Description:** ' . $this->task->description)
            ->line('**Status:** ' . $this->task->status->name)
            ->line('**Start Date:** ' . $this->task->start_date->format('M d, Y'))
            ->line('**Due Date:** ' . $this->task->end_date->format('M d, Y'))
            ->action('View Task', url('/'))
            ->line('Thank you for using our task management platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $message = "Task '{$this->task->title}' has been reassigned to you";
        if ($this->previousUser) {
            $message .= " from {$this->previousUser->name}";
        }

        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_description' => $this->task->description,
            'start_date' => $this->task->start_date,
            'end_date' => $this->task->end_date,
            'status' => $this->task->status->name,
            'previous_user' => $this->previousUser ? $this->previousUser->name : null,
            'message' => $message,
        ];
    }
}
