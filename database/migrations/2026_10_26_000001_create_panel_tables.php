<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 32)->unique();
            $table->string('status', 20)->default('provisioning')->index();
            $table->string('app_url');
            $table->string('database_name');
            $table->string('database_user');
            $table->text('database_password')->nullable();
            $table->string('redis_prefix');
            $table->text('app_key')->nullable();
            $table->string('image_ref')->nullable();
            $table->string('admin_name');
            $table->string('admin_email');
            $table->text('admin_password')->nullable();
            $table->timestamp('password_shown_at')->nullable();
            $table->string('checkpoint', 20)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('image_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_name');
            $table->string('subject_slug', 32);
            $table->string('type', 20);
            $table->string('status', 20);
            $table->text('output')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('operations');
        Schema::dropIfExists('tenants');
    }
};
