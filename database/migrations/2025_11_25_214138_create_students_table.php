<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->string('name');       // نام
            $table->string('last_name');  // نام خانوادگی

            $table->integer('student_code')
                ->unsigned();             // مثل 711، 812 و ...

            // اگر می‌خوای student_code یکتا باشد این خط را فعال کن:
            $table->unique('student_code');

            $table->foreignId('grade_id')
                ->constrained('grades')   // REFERENCES grades(id)
                ->cascadeOnUpdate()
                ->restrictOnDelete();     // تا وقتی دانش‌آموز هست، پایه حذف نشود

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
