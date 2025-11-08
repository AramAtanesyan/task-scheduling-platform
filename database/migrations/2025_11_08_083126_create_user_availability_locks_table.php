<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAvailabilityLocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_availability_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->boolean('is_processing')->default(true);
            $table->timestamp('locked_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent duplicate locks for same user+task
            $table->unique(['user_id', 'task_id']);
            
            // Indexes for faster queries
            $table->index('user_id');
            $table->index('is_processing');
            $table->index('locked_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_availability_locks');
    }
}
