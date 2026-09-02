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
        Schema::create('uploaded_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete(); // team scoping
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // user scoping
            $table->string('filename'); // display name
            $table->string('provider_file_id'); // provider's file identifier
            $table->string('provider_store_id'); // vector store that holds this file
            $table->json('metadata')->nullable(); // scoping + filters
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_documents');
    }
};
