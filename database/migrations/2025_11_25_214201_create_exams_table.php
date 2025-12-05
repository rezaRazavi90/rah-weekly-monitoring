<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // تا وقتی آزمون هست، درس حذف نشود

            $table->date('exam_date');          // تاریخ آزمون
            $table->unsignedSmallInteger('total_question'); // تعداد سوالات آزمون

            $table->string('title')->nullable(); // مثلا "آزمون فصل ۲"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
