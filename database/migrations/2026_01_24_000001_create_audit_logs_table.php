<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('audit_logs', function (Blueprint $table) {
      $table->id();

      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

      // module area e.g. inventory, crm, finance
      $table->string('module', 100)->index();

      // action key e.g. stock_entries.create, users.update
      $table->string('action', 150)->index();

      // human readable
      $table->string('description', 255)->nullable();

      // optional: link to a record (polymorphic)
      $table->nullableMorphs('subject'); // subject_type, subject_id

      // request context
      $table->string('route', 190)->nullable()->index();
      $table->string('url', 500)->nullable();
      $table->string('method', 10)->nullable();
      $table->ipAddress('ip')->nullable();
      $table->text('user_agent')->nullable();

      // optional extra metadata (changes, payload, etc.)
      $table->json('meta')->nullable();

      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('audit_logs');
  }
};
