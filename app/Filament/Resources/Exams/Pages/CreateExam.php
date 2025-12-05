<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Imports\ExamResultsImport;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    protected function handleRecordCreation(array $data): Model
    {


        // فایل اکسل نتایج را جدا می‌کنیم
        $resultsFile = $data['results_file'] ?? null;

        // چون results_file در جدول exams وجود ندارد، حذفش می‌کنیم
        unset($data['results_file']);


        return DB::transaction(function () use ($data, $resultsFile): Model {
            // ۱. چک اینکه حتماً فایل وجود دارد
            if (! $resultsFile) {
                throw ValidationException::withMessages([
                    'results_file' => 'فایل نتایج آزمون الزامی است.',
                ]);
            }

            // ۲. ایجاد خود آزمون
            /** @var Exam $exam */
            $exam = Exam::create($data);

            // ۳. خواندن فایل اکسل به صورت Collection
            $import = new ExamResultsImport();
            Excel::import($import, $resultsFile);
            $rows = $import->getRows();

            $errors = [];
            $validRows = [];
            $totalQuestions = (int) $exam->total_question;

            // ۴. ولیدیشن تمام سطرها (بدون نوشتن در دیتابیس)
            foreach ($rows as $index => $row) {
                // چون سطر اول هدر است، شماره واقعی اکسل = index + 2
                $excelRow = $index + 2;

                $studentCodeRaw = $row['student_code'] ?? null;
                $correctRaw     = $row['correct_answer_count'] ?? null;
                $absentRaw      = $row['is_absent'] ?? null;

                $studentCode = $studentCodeRaw !== null ? trim((string) $studentCodeRaw) : '';

                if ($studentCode === '') {
                    $errors[] = "سطر {$excelRow}: ستون student_code خالی است.";
                    continue;
                }

                // پیدا کردن دانش‌آموز بر اساس student_code
                $student = Student::where('student_code', $studentCode)->first();

                if (! $student) {
                    $errors[] = "سطر {$excelRow}: دانش‌آموزی با کد {$studentCode} پیدا نشد.";
                    continue;
                }

                // تشخیص غیبت
                $isAbsent = false;
                if ($absentRaw !== null) {
                    $abs = trim(mb_strtolower((string) $absentRaw));

                    if (
                        $abs === '1' ||
                        $abs === 'true' ||
                        $abs === 'yes' ||
                        $abs === 'y' ||
                        $abs === 'غ'
                    ) {
                        $isAbsent = true;
                    }
                }

                // اگر غایب است، نمره را نادیده می‌گیریم
                if ($isAbsent) {
                    $correct = null;
                } else {
                    if ($correctRaw === null || $correctRaw === '') {
                        $errors[] = "سطر {$excelRow}: ستون correct_answer_count خالی است (دانش‌آموز حاضر).";
                        continue;
                    }

                    if (! is_numeric($correctRaw)) {
                        $errors[] = "سطر {$excelRow}: مقدار correct_answer_count باید عددی باشد.";
                        continue;
                    }

                    $correct = (int) $correctRaw;

                    if ($correct < 0 || $correct > $totalQuestions) {
                        $errors[] = "سطر {$excelRow}: correct_answer_count باید بین 0 و {$totalQuestions} باشد (مقدار فعلی: {$correct}).";
                        continue;
                    }
                }

                // جلوگیری از تکرار یک دانش‌آموز در فایل
                if (array_key_exists($student->id, $validRows)) {
                    $errors[] = "سطر {$excelRow}: کد دانش‌آموز {$studentCode} بیش از یک‌بار در فایل آمده است.";
                    continue;
                }

                $validRows[$student->id] = [
                    'student_id'           => $student->id,
                    'correct_answer_count' => $correct,
                    'is_absent'            => $isAbsent,
                ];
            }

            // ۵. اگر حتی یک خطا باشد، Exception می‌زنیم → تراکنش رول‌بک می‌شود
            if (! empty($errors)) {
                throw ValidationException::withMessages([
                    'results_file' => implode("\n", $errors),
                ]);
            }

            // ۶. در صورت بدون خطا بودن، نمرات را در جدول exam_results ثبت می‌کنیم
            foreach ($validRows as $row) {
                ExamResult::create([
                    'exam_id'              => $exam->id,
                    'student_id'           => $row['student_id'],
                    'correct_answer_count' => $row['correct_answer_count'],
                    'is_absent'            => $row['is_absent'],
                ]);
            }

            // اگر همه چیز تا اینجا بدون Exception تمام شود،
            // تراکنش commit می‌شود و هم آزمون داریم هم نمرات کامل
            return $exam;
        });
    }
}
