<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('atas_nama')->default('');
            $table->string('jabatan')->default('');
            $table->string('nama_penandatangan')->default('');
            $table->string('pangkat_nrp')->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_settings');
    }
};
