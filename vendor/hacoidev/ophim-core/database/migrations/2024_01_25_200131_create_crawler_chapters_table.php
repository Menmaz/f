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
            Schema::create('crawler_chapters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_manga');
                $table->unsignedBigInteger('id_chapter');
                $table->string('chapter_api_data', 1024)->nullable();
                $table->string('update_handler', 1024)->nullable();
                $table->string('update_identity', 2048)->nullable();
                $table->string('update_checksum', 2048)->nullable();
                $table->enum('status', ['trailer', 'ongoing', 'completed']);
                $table->timestamps();
                $table->foreign('id_manga')->references('id')->on('mangas')->onDelete('cascade');
                $table->foreign('id_chapter')->references('id')->on('chapters')->onDelete('cascade');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawler_chapters');
    }
};
