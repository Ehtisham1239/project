<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->unique(); // E.164 format, WhatsApp ID
            $table->string('email')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('source')->default('whatsapp'); // whatsapp, manual, import, ghl, erp
            $table->json('whatsapp_profile')->nullable(); // raw profile data from Meta
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
