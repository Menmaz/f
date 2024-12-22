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
        Schema::create('crawler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_manga');
            // Additional columns
            $table->string('update_handler', 1024)->nullable();
            $table->string('update_identity', 2048)->nullable();
            $table->string('update_checksum', 2048)->nullable();
            $table->timestamp('updatedAt')->nullable();
            $table->integer('chaptersLatest')->nullable();
            $table->timestamps();
            // Foreign key
            $table->foreign('id_manga')->references('id')->on('mangas')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawler');
    }
};
