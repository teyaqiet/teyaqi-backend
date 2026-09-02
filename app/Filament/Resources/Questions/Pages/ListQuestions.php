<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
use App\Models\Category;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // 📥 ACTION 1: DOWNLOAD SAMPLE
            Action::make('downloadSample')
                ->label('Download Template')
                ->icon('heroicon-m-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    return new StreamedResponse(function () {
                        $handle = fopen('php://output', 'w');
                        
                        // Header row
                        fputcsv($handle, [
                            'category_id', 'question_en', 'question_am', 
                            'option_a_en', 'option_a_am', 'option_b_en', 'option_b_am', 
                            'option_c_en', 'option_c_am', 'option_d_en', 'option_d_am', 
                            'correct_answer', 'difficulty', 'difficulty_score'
                        ]);

                        // Sample data row
                        fputcsv($handle, [
                            'History', 'Who developed the theory of relativity?', 'የአንፃራዊነትን ንድፈ ሃሳብ ያመነጨው ማን ነው?',
                            'Isaac Newton', 'አይዛክ ኒውተን', 'Nikola Tesla', 'ኒኮላ ቴስላ',
                            'Albert Einstein', 'አልበርት አይንስታይን', 'Galileo Galilei', 'ጋሊልዮ ጋሊሊ',
                            'c', 'medium', '50'
                        ]);

                        fclose($handle);
                    }, 200, [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="teyaqi_import_template.csv"',
                    ]);
                }),
            
            // 📤 ACTION 2: BULK IMPORT
            Action::make('importQuestions')
                ->label('Bulk Import')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('info')
                ->form([
                    FileUpload::make('attachment')
                        ->label('Upload Questions CSV')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'application/csv']),
                ])
                ->action(function (array $data) {
                    $file = Storage::disk('local')->path($data['attachment']);
                    
                    if (($handle = fopen($file, 'r')) !== FALSE) {
                        $header = fgetcsv($handle, 1000, ',');
                        $count = 0;
                        
                        // Pre-load categories for mapping
                        $categories = Category::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);

                        try {
                            DB::transaction(function () use ($handle, $header, &$count, $categories) {
                                while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                                    if (count($header) !== count($row)) continue;
                                    
                                    $csv = array_combine($header, $row);
                                    
                                    // Smart Category Lookup (Name or ID)
                                    $catId = $csv['category_id'];
                                    if (!is_numeric($catId)) {
                                        $catId = $categories[strtolower(trim($catId))] ?? null;
                                    }

                                    if (!$catId) throw new \Exception("Category '{$csv['category_id']}' not found at row " . ($count + 2));

                                    Question::create([
                                        'category_id' => $catId,
                                        'question_text' => ['en' => trim($csv['question_en']), 'am' => trim($csv['question_am'] ?? '')],
                                        'option_a' => ['en' => trim($csv['option_a_en']), 'am' => trim($csv['option_a_am'] ?? '')],
                                        'option_b' => ['en' => trim($csv['option_b_en']), 'am' => trim($csv['option_b_am'] ?? '')],
                                        'option_c' => ['en' => trim($csv['option_c_en']), 'am' => trim($csv['option_c_am'] ?? '')],
                                        'option_d' => ['en' => trim($csv['option_d_en']), 'am' => trim($csv['option_d_am'] ?? '')],
                                        'correct_answer' => strtolower(trim($csv['correct_answer'])),
                                        'difficulty' => strtolower($csv['difficulty'] ?? 'medium'),
                                        'difficulty_score' => (int)($csv['difficulty_score'] ?? 50),
                                        'is_active' => true,
                                    ]);
                                    $count++;
                                }
                            });

                            Notification::make()
                                ->title("Import Successful")
                                ->body("$count questions added to Teyaqi.")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title("Import Failed")
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        } finally {
                            fclose($handle);
                        }
                    }
                }),
        ];
    }
}