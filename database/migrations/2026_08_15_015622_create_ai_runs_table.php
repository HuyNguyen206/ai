<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Team::class)->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignIdFor(\App\Models\User::class)->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignIdFor(\App\Models\Ticket::class)->nullable()
                ->constrained()->nullOnDelete();
            $table->string('feature_key');
            $table->string('status');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->string('input_hash')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('invocation_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_runs');
    }
};
