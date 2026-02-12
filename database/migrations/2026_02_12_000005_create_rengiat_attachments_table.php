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
        Schema::create('rengiat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('rengiat_entries')->cascadeOnDelete();
            $table->string('path');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rengiat_attachments');
    }
};
