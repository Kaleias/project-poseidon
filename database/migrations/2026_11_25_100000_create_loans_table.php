<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            // A book/member that still has loan history cannot be deleted.
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();

            // Staff user who issued the loan. Keep the loan if that user is removed.
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('borrowed_at');
            $table->date('due_date');
            $table->date('returned_at')->nullable();
            $table->string('status', 20)->default('borrowed');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
