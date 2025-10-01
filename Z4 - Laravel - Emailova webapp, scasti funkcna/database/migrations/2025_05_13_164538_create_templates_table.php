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
       Schema::create('templates', function (Blueprint $table) {
          $table->id();

          $table->foreignId('user_id')
             ->constrained()
             ->onDelete('cascade');

          $table->string('name');
          $table->string('subject');

          // HTML verzia šablóny (napr. Blade view obsah)
          $table->text('body_html')->nullable();

          // Plain-text fallback (ak nie je HTML)
          $table->text('body_text')->nullable();

          // Príznak, či sa má použiť HTML telo
          $table->boolean('is_html')->default(true);

          $table->json('attachments')->nullable()->comment('List of file paths or URLs for template attachments');

          $table->timestamps();
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
