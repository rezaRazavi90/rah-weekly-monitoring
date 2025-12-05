<?php

namespace App\Filament\Widgets;

use App\Models\ExamResult;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class AttentionNeededWidget extends Widget
{
    protected string $view = 'filament.widgets.attention-needed-widget';

    protected static ?string $heading = 'پایش سریع: چه کسانی الان نیاز به توجه دارند؟';

    public array $studentsWithDrop = [];
    public array $weakGrades       = [];
    public array $problemSubjects  = [];

    public function mount(): void
    {
        $this->computeData();
    }

    protected function computeData(): void
    {
        $today = now();

        // بازهٔ هفته جاری و قبل
        $currentStart = $today->copy()->startOfWeek(Carbon::SATURDAY);
        $currentEnd   = $today->copy()->endOfWeek(Carbon::FRIDAY);

        $prevStart = $currentStart->copy()->subWeek();
        $prevEnd   = $currentEnd->copy()->subWeek();

        // میانگین دانش‌آموزان برای دو هفته
        $current = $this->studentAverages($currentStart, $currentEnd);
        $prev    = $this->studentAverages($prevStart, $prevEnd);

        // ۱) دانش‌آموزان با افت شدید (مثلاً بیش از ۳ نمره)
        $drops = collect();

        foreach ($current as $studentId => $curr) {
            if (! isset($prev[$studentId])) {
                continue;
            }

            $prevAvg = $prev[$studentId]['avg'];
            $diff = $curr['avg'] - $prevAvg;

            if ($diff <= -3) {
                $student = $curr['student'];

                $drops->push([
                    'name'     => $student->last_name . ' ' . $student->name,
                    'grade'    => $student->grade?->name ?? '-',
                    'prev_avg' => $prevAvg,
                    'curr_avg' => $curr['avg'],
                    'diff'     => $diff,
                ]);
            }
        }

        $this->studentsWithDrop = $drops
            ->sortBy('diff') // منفی‌تر یعنی افت بیشتر
            ->take(5)
            ->values()
            ->all();

        // ۲) پایه‌هایی که این هفته میانگین‌شان پایین است
        $this->weakGrades = $this->weakGradesThisWeek($currentStart, $currentEnd);

        // ۳) چند درس که در این هفته میانگین خیلی پایینی دارند
        $this->problemSubjects = $this->problemSubjectsThisWeek($currentStart, $currentEnd);
    }

    protected function studentAverages(Carbon $start, Carbon $end): Collection
    {
        $results = ExamResult::query()
            ->where('is_absent', false)
            ->whereNotNull('correct_answer_count')
            ->whereHas('exam', function ($q) use ($start, $end) {
                $q->whereBetween('exam_date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);
            })
            ->with(['exam', 'student.grade'])
            ->get()
            ->filter(fn ($r) => $r->exam && $r->exam->total_question > 0);

        return $results
            ->groupBy('student_id')
            ->map(function (Collection $items) {
                $student = $items->first()->student;

                $avg = $items->avg(function ($r) {
                    return ($r->correct_answer_count / $r->exam->total_question) * 20;
                });

                return [
                    'student' => $student,
                    'avg'     => $avg,
                ];
            });
    }

    protected function weakGradesThisWeek(Carbon $start, Carbon $end): array
    {
        $results = ExamResult::query()
            ->where('is_absent', false)
            ->whereNotNull('correct_answer_count')
            ->whereHas('exam', function ($q) use ($start, $end) {
                $q->whereBetween('exam_date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);
            })
            ->with(['exam.subject.grade'])
            ->get()
            ->filter(fn ($r) => $r->exam && $r->exam->total_question > 0 && $r->exam->subject && $r->exam->subject->grade);

        $byGrade = $results->groupBy(fn ($r) => $r->exam->subject->grade_id)
            ->map(function (Collection $items) {
                $grade = $items->first()->exam->subject->grade;

                $avg = $items->avg(function ($r) {
                    return ($r->correct_answer_count / $r->exam->total_question) * 20;
                });

                return [
                    'grade' => $grade?->name ?? '-',
                    'avg'   => $avg,
                ];
            })
            ->filter(fn ($row) => $row['avg'] < 14); // مثلاً زیر ۱۴ را «نیاز به توجه» در نظر بگیریم

        return $byGrade
            ->sortBy('avg')
            ->take(3)
            ->values()
            ->all();
    }

    protected function problemSubjectsThisWeek(Carbon $start, Carbon $end): array
    {
        $results = ExamResult::query()
            ->where('is_absent', false)
            ->whereNotNull('correct_answer_count')
            ->whereHas('exam', function ($q) use ($start, $end) {
                $q->whereBetween('exam_date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);
            })
            ->with(['exam.subject'])
            ->get()
            ->filter(fn ($r) => $r->exam && $r->exam->total_question > 0 && $r->exam->subject);

        $grouped = $results->groupBy('exam.subject_id')
            ->map(function (Collection $items) {
                $subject = $items->first()->exam->subject;

                $avg = $items->avg(function ($r) {
                    return ($r->correct_answer_count / $r->exam->total_question) * 20;
                });

                return [
                    'subject' => $subject->name ?? '-',
                    'avg'     => $avg,
                ];
            })
            ->filter(fn ($row) => $row['avg'] < 14);

        return $grouped
            ->sortBy('avg')
            ->take(3)
            ->values()
            ->all();
    }
}
