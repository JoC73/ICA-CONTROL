<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_cuts', function (Blueprint $table) {
            $table->id();
            $table->string('period')->index();
            $table->timestamp('cut_at')->index();
            $table->decimal('income_total', 14, 2)->default(0);
            $table->decimal('expense_total', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_cuts');
    }
};
