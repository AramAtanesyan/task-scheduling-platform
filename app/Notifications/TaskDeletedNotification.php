<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var int
     */
    private $taskId;

    /**
     * Create a new notification instance.
     *
     * @param int $taskId
     * @return void
     */
    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
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

        return (new MailMessage)
            ->subject('Task Deleted: ' . $task->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A task that was assigned to you has been deleted.')
            ->line('**Task:** ' . $task->title)
            ->line('**Description:** ' . $task->description)
            ->line('**Status:** ' . $task->status->name)
            ->line('**Start Date:** ' . $task->start_date->format('M d, Y'))
            ->line('**Due Date:** ' . $task->end_date->format('M d, Y'))
            ->line('Your availability has been automatically updated.')
            ->line('Thank you for using our task management platform!');
    }
}

