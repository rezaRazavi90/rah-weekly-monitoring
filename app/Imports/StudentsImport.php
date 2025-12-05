<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure,
    SkipsOnError,
    SkipsEmptyRows
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    use Importable;
    use SkipsFailures;
    use SkipsErrors;
    public function model(array $row)
    {
        return new Student([
            'name'         => $row['name'] ?? null,
            'last_name'    => $row['lastname'] ?? null,   // ستون اکسل lastname → ستون دیتابیس last_name
            'student_code' => $row['student_code'] ?? null,
            'grade_id'     => $row['grade_id'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'lastname' => [
                'required',
                'string',
                'max:100',
            ],

            'student_code' => [
                'required',
                'integer',
                'min:1',
                'unique:students,student_code', // در کل جدول یکتا باشد
            ],

            'grade_id' => [
                'required',
                'integer',
                'exists:grades,id', // باید به یک پایه‌ی موجود اشاره کند
            ],
        ];
    }
    public function customValidationAttributes(): array
    {
        return [
            'name'          => 'ستون name',
            'lastname'      => 'ستون lastname',
            'student_code'  => 'ستون student_code',
            'grade_id'      => 'ستون grade_id',
        ];
    }
}
