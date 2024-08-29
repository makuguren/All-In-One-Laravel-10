<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Section;
use App\Models\Student;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\File;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\StudentResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\StudentResource\RelationManagers;
use ZipArchive;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('student_id')
                    ->label('Student ID')
                    ->required(),
                Forms\Components\TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('middle_name')
                    ->label('Middle Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('section_id')
                    ->label('Section')
                    ->options(
                        Section::all()->mapWithKeys(function ($item) {
                            return [
                                $item->id => "{$item->program} {$item->year}{$item->block}"
                            ];
                        })
                    )
                    ->searchable()
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student_id')
                    ->label('Student ID'),
                Tables\Columns\TextColumn::make('fullname')
                    ->searchable('student_id')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('section.programyearblock'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('generateQr')
                        ->action(function (Collection $records) {

                            // Create Sections Folder First
                            $sections = Section::all();
                            foreach ($sections as $section) {
                                // Check if the Folder is Not Exists in Public
                                if(! File::exists(public_path('storage/QrCodes/' . $section->program . ' ' . $section->year . $section->block))){
                                    File::makeDirectory(public_path('storage/QrCodes/' . $section->program . ' ' . $section->year . $section->block));
                                }
                            }

                            $records->each(function ($record) {

                                // Code Here to Execute QRCode
                                QrCode::size(300)->generate($record->id, public_path('storage/QrCodes/'. $record->section->program . ' ' . $record->section->year . $record->section->block . '/' . $record->last_name . '.svg'));
                                // dump($record->id);
                            });

                            // Create Compressed ZIP File after Generating QRCode for Students
                            $zip_file = public_path('storage/QrCodes/qrcodes.zip');
                            $zip = new \ZipArchive();
                            $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                            $path = public_path('storage/QrCodes');
                            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
                            foreach ($files as $file)
                            {
                                // We're skipping all subfolders
                                if (!$file->isDir()) {
                                    $filePath = $file->getRealPath();

                                    // extracting filename with substr/strlen
                                    $relativePath = substr($filePath, strlen($path) + 1);
                                    $zip->addFile($filePath, $relativePath);
                                }
                            }
                            $zip->close();
                            return response()->download($zip_file)->deleteFileAfterSend(true);


                            // Call the Notification is Successfull.
                            Notification::make()
                                ->title('Generate QR Code Successfully')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->label('Generate QR Code')
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
