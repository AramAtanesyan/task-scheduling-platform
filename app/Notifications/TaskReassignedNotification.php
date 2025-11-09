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

    /**
     * @var int
     */
    private $taskId;

    /**
     * @var int
     */
    private $previousUserId;

    /**
     * Create a new notification instance.
     *
     * @param int $taskId
     * @param int $previousUserId
     * @return void
     */
    public function __construct(int $taskId, int $previousUserId)
    {
        $this->taskId = $taskId;
        $this->previousUserId = $previousUserId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $task = Task::withTrashed()->with('status')->findOrFail($this->taskId);
        $previousUser = User::findOrFail($this->previousUserId);

        return (new MailMessage)
            ->subject('Task Reassigned: ' . $task->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A task has been reassigned to you.')
            ->line('**Previously assigned to:** ' . $previousUser->name)
            ->line('**Task:** ' . $task->title)
            ->line('**Description:** ' . $task->description)
            ->line('**Status:** ' . $task->status->name)
            ->line('**Start Date:** ' . $task->start_date->format('M d, Y'))
            ->line('**Due Date:** ' . $task->end_date->format('M d, Y'))
            ->line('Thank you for using our task management platform!');
    }
}
