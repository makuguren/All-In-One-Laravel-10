<?php

namespace App\Filament\Resources\StudentResource\Pages;

use Filament\Actions;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\StudentResource;
use App\Imports\StudentsImport;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Student'),
            Actions\Action::make('importstudents')
            ->label('Import Students using CSV')
            ->form([
                FileUpload::make('attachment'),
            ])
            ->action(function (array $data) {
                $file = public_path('storage/'. $data['attachment']);
                Excel::import(new StudentsImport, $file);
                // dd($file);

                Notification::make()
                    ->title('Students Imported')
                    ->success()
                    ->send();
            })
        ];
    }
}
