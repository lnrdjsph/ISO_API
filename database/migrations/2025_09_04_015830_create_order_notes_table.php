<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id'); // Link to orders table
            $table->unsignedBigInteger('user_id')->nullable(); // Who added the note
            $table->enum('status', [
                'new order',
                'for approval',
                'approved',
                'rejected',
                'pending',
                'processing',
                'completed',
                'cancelled',
                'restored',
            ])->nullable(); // status at the time of note
            $table->text('note')->nullable(); // actual note content (like rejection reason, approval comment, etc.)
            $table->timestamps();

            // Foreign keys (adjust table names if different)
            $table->foreign('order_id')
                ->references('id')->on('orders')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_notes');
    }
};
