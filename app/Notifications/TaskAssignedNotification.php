<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $task;

    /**
     * Create a new notification instance.
     *
     * @param Task $task
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
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
        return (new MailMessage)
            ->subject('New Task Assigned: ' . $this->task->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('You have been assigned a new task.')
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
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_description' => $this->task->description,
            'start_date' => $this->task->start_date,
            'end_date' => $this->task->end_date,
            'status' => $this->task->status->name,
            'message' => "You have been assigned a new task: {$this->task->title}",
        ];
    }
}
