<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deletion_audits')) {
            return;
        }

        Schema::create('deletion_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->unsignedBigInteger('cultivo_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->text('reason')->nullable();
            $table->string('route_name')->nullable();
            $table->string('path')->nullable();
            $table->string('target_key')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_type')->nullable();
            $table->string('target_label')->nullable();
            $table->string('target_display')->nullable();
            $table->json('request_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['target_id', 'target_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_audits');
    }
};