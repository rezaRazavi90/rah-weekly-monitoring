<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------
        // ۱) پایه‌ها (grades)
        // -----------------------------
        $gradesConfig = [
            7 => 'هفتم',
            8 => 'هشتم',
            9 => 'نهم',
        ];

        $grades = [];

        foreach ($gradesConfig as $number => $name) {
            $grades[$number] = Grade::firstOrCreate(
                ['name' => $name],
                [] // فیلد اضافه‌ای نداریم
            );
        }

        // -----------------------------
        // ۲) دروس هر پایه (subjects)
        // تقریباً مطابق برنامهٔ درسی متوسطه اول
        // -----------------------------
        $subjectsByGradeNames = [
            7 => [
                'فارسی ۷',
                'نگارش ۷',
                'ریاضی ۷',
                'علوم تجربی ۷',
                'مطالعات اجتماعی ۷',
                'قرآن ۷',
                'پیام‌های آسمان ۷',
                'عربی ۷',
                'زبان انگلیسی ۷',
            ],
            8 => [
                'فارسی ۸',
                'نگارش ۸',
                'ریاضی ۸',
                'علوم تجربی ۸',
                'مطالعات اجتماعی ۸',
                'قرآن ۸',
                'پیام‌های آسمان ۸',
                'عربی ۸',
                'زبان انگلیسی ۸',
            ],
            9 => [
                'فارسی ۹',
                'نگارش ۹',
                'ریاضی ۹',
                'علوم تجربی ۹',
                'مطالعات اجتماعی ۹',
                'قرآن ۹',
                'پیام‌های آسمان ۹',
                'عربی ۹',
                'زبان انگلیسی ۹',
            ],
        ];

        $subjectsByGrade = [];

        foreach ($subjectsByGradeNames as $gradeNumber => $subjectNames) {
            $grade = $grades[$gradeNumber];

            $subjectsByGrade[$gradeNumber] = collect();

            foreach ($subjectNames as $subjectName) {
                $subject = Subject::firstOrCreate(
                    [
                        'name'     => $subjectName,
                        'grade_id' => $grade->id,
                    ],
                    []
                );

                $subjectsByGrade[$gradeNumber]->push($subject);
            }
        }

        // -----------------------------
        // ۳) دانش‌آموزان هر پایه (students)
        // -----------------------------
        $studentsByGrade = [];

        foreach ($grades as $gradeNumber => $grade) {
            $studentsByGrade[$gradeNumber] = collect();

            for ($i = 1; $i <= 20; $i++) {
                $studentCode = ($gradeNumber * 100) + $i; // مثل 701، 702، ...

                $student = Student::firstOrCreate(
                    [
                        'student_code' => $studentCode,
                    ],
                    [
                        'name'       => fake('fa_IR')->firstName(),
                        'last_name'  => fake('fa_IR')->lastName(),
                        'grade_id'   => $grade->id,
                    ]
                );

                $studentsByGrade[$gradeNumber]->push($student);
            }
        }

        // -----------------------------
        // ۴) آزمون‌ها و نتایج (exams, exam_results)
        // از هفته دوم مهر امسال تا امروز
        // هر روز ۳ آزمون برای هر پایه
        // پنج‌شنبه و جمعه تعطیل
        // -----------------------------

        // امروز
        $today = Carbon::today();

        // شروع تقریبی: هفته دوم مهر "امسال"
        // (اینجا فرض می‌گیریم دوم مهر ~ اوایل اکتبر همین سال میلادی)
        // می‌تونی تاریخ رو مطابق نیاز خودت تنظیم کنی.
        $startDate = Carbon::create($today->year, 10, 5)->startOfDay();

        // اگر امروز قبل از این تاریخ بود، از ابتدای همین ماه شروع کن
        if ($today->lt($startDate)) {
            $startDate = $today->copy()->startOfMonth();
        }

        $endDate = $today->copy()->endOfDay();

        // برای هر روز بین startDate و endDate
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // پنج‌شنبه و جمعه تعطیل
            if (in_array($date->dayOfWeek, [Carbon::THURSDAY, Carbon::FRIDAY], true)) {
                continue;
            }

            foreach ($grades as $gradeNumber => $grade) {
                $subjects = $subjectsByGrade[$gradeNumber];

                if ($subjects->count() < 3) {
                    continue; // احتیاطی
                }

                // در هر روز، برای هر پایه، ۳ آزمون با دروس تصادفی (بدون تکرار)
                $examSubjects = $subjects->random(3);

                foreach ($examSubjects as $subject) {
                    $totalQuestions = fake()->numberBetween(2, 5);

                    $exam = Exam::create([
                        'subject_id'     => $subject->id,
                        'exam_date'      => $date->toDateString(), // YYYY-MM-DD
                        'total_question' => $totalQuestions,
                        'title'          => 'آزمون ' . $subject->name . ' - ' . $date->format('Y/m/d'),
                    ]);

                    // نتایج آزمون برای همه دانش‌آموزان پایه
                    $students = $studentsByGrade[$gradeNumber];

                    foreach ($students as $student) {
                        // حدود ۱۵٪ غیبت
                        $isAbsent = fake()->boolean(15);

                        if ($isAbsent) {
                            ExamResult::create([
                                'exam_id'              => $exam->id,
                                'student_id'           => $student->id,
                                'correct_answer_count' => null,
                                'is_absent'            => true,
                            ]);
                        } else {
                            $correctAnswers = fake()->numberBetween(0, $totalQuestions);

                            ExamResult::create([
                                'exam_id'              => $exam->id,
                                'student_id'           => $student->id,
                                'correct_answer_count' => $correctAnswers,
                                'is_absent'            => false,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
