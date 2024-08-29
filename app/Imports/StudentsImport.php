<?php

namespace App\Imports;

use App\Models\Section;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Find or create the section based on program, year, and block
        $section = Section::firstOrCreate(
            [
                'program' => $row['program'],
                'year' => $row['year'],
                'block' => $row['block']
            ],
            [
                'program' => $row['program'],
                'year' => $row['year'],
                'block' => $row['block']
            ]
        );

        return new Student([
            'student_id' => $row['student_id'],
            'last_name' => $row['last_name'],
            'first_name' => $row['first_name'],
            'middle_name' => $row['middle_name'],
            'section_id' => $section->id
        ]);
    }
}
