<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')
                ->constrained('exams')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // اگر آزمون حذف شد، نتایجش هم پاک شوند

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete(); // اگر دانش‌آموز حذف شد، نتایجش هم پاک شوند

            $table->unsignedSmallInteger('correct_answer_count')
                ->nullable(); // اگر غایب بود، NULL؛ اگر حاضر بود 0 تا total_question

            $table->boolean('is_absent')
                ->default(false); // 0 = حاضر، 1 = غایب

            // هر دانش‌آموز فقط یک نتیجه برای هر آزمون داشته باشد
            $table->unique(['exam_id', 'student_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
