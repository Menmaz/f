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
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->index(["manga_id", "chapter_number", "user_id", "created_at"]);
            $table->longText('content')->nullable();
            $table->string('title')->nullable();
            $table->double('chapter_number', 8, 2);
            $table->foreignId('manga_id')->onDelete('cascade');
            $table->foreignId('user_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
