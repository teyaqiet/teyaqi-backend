<?php

namespace App\Filament\Resources\Challenges\Schemas;

use App\Enums\ChallengeStatus;
use App\Models\Category;
use App\Models\Question;
use App\Models\Topic; 
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ChallengeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                |--------------------------------------------------------------------------
                | General Details & Media
                |--------------------------------------------------------------------------
                |
                */
                Section::make('General Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(
                                        fn ($state, callable $set) =>
                                        $set('slug', Str::slug($state))
                                    ),
                                TextInput::make('slug')
                                    ->required(),
                            ]),

                        Textarea::make('description')
                            ->rows(3),

                        FileUpload::make('thumbnail')
                            ->image()
                            ->disk('public') // ⚡ FORCES FILAMENT TO USE THE PUBLIC DISK
                            ->directory('challenges/thumbnails') // 📁 OUTCOME: app/storage/app/public/challenges/thumbnails
                            ->visibility('public'),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | Detailed Configuration Architecture
                |--------------------------------------------------------------------------
                |
                */
                Tabs::make('Configuration')
                    ->tabs([

                        // Tab 1: Workflow States & Visibility Flags
                        Tab::make('Settings')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('status')
                                            ->options(ChallengeStatus::class)
                                            ->required(),
                                        Select::make('type')
                                            ->options([
                                                'daily' => 'Daily',
                                                'topic' => 'Topic',
                                                'timed' => 'Timed',
                                                'ranked' => 'Ranked',
                                            ])
                                            ->required(),
                                        Select::make('visibility')
                                            ->options([
                                                'public' => 'Public',
                                                'private' => 'Private',
                                                'hidden' => 'Hidden',
                                            ])
                                            ->default('public'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Select::make('category_id')
                                            ->label('Target Category')
                                            ->options(fn () => Category::pluck('name', 'id')->toArray())
                                            ->searchable()
                                            ->preload(),
                                        Select::make('topic_id')
                                            ->label('Target Topic')
                                            ->options(fn () => Topic::pluck('name', 'id')->toArray())
                                            ->searchable()
                                            ->preload(),
                                    ]),

                                Grid::make(4)
                                    ->schema([
                                        Toggle::make('is_daily'),
                                        Toggle::make('is_featured'),
                                        Toggle::make('is_ranked'),
                                        Toggle::make('allow_retry')
                                            ->default(true),
                                    ]),
                            ]),

                        // Tab 2: Criteria Thresholds & Constraints
                        Tab::make('Rules & Constraints')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('difficulty')
                                            ->options([
                                                'easy' => 'Easy',
                                                'medium' => 'Medium',
                                                'hard' => 'Hard',
                                            ]),
                                        
                                        TextInput::make('question_count')
                                            ->numeric()
                                            ->default(5)
                                            ->required()
                                            ->label('Total Question Count (Global Cap)')
                                            ->hint('The absolute final count shown to users'),
                                            
                                        TextInput::make('passing_score')
                                            ->numeric()
                                            ->default(70)
                                            ->suffix('%')
                                            ->label('Passing Score Threshold'),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        Select::make('time_mode')
                                            ->label('Timer Mode Strategy')
                                            ->options([
                                                'per_session' => 'Whole Session Countdown',
                                                'per_question' => 'Per Question Countdown',
                                            ])
                                            ->default('per_session')
                                            ->required()
                                            ->live(),

                                        TextInput::make('time_limit')
                                            ->numeric()
                                            ->default(60)
                                            ->label('Time Limit Constraint')
                                            ->suffix(fn (callable $get) => $get('time_mode') === 'per_question' ? 'secs / item' : 'secs total'),

                                        TextInput::make('level_min')
                                            ->numeric()
                                            ->default(1)
                                            ->label('Min Level Entry Limit'),
                                    ]),

                                Grid::make(1)
                                    ->schema([
                                        TextInput::make('level_max')
                                            ->numeric()
                                            ->label('Max Level Entry Limit'),
                                    ]),

                                Section::make('Dynamic Generation Rules Pool')
                                    ->description('Configure targeted pool extractions. Overlapping items are uniquely de-duplicated.')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('rules')
                                            ->relationship('rules')
                                            ->saveRelationshipsUsing(function ($record, $component, $state) {
                                                $record->rules()->delete();

                                                $items = $state ?? data_get($component->getLivewire(), 'data.rules', []);

                                                foreach ((array) $items as $ruleData) {
                                                    if (empty($ruleData)) continue;

                                                    if (isset($ruleData['tags']) && is_string($ruleData['tags'])) {
                                                        $ruleData['tags'] = array_map('trim', explode(',', $ruleData['tags']));
                                                    }

                                                    $record->rules()->create([
                                                        'selection_type' => $ruleData['selection_type'] ?? 'random',
                                                        'category_id'    => $ruleData['category_id'] ?? null,
                                                        'topic_id'       => $ruleData['topic_id'] ?? null,
                                                        'difficulty'     => $ruleData['difficulty'] ?? null,
                                                        'tags'           => $ruleData['tags'] ?? null,
                                                        'question_count' => $ruleData['question_count'] ?? 5,
                                                    ]);
                                                }
                                            })
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Select::make('category_id')
                                                            ->label('Category Filter')
                                                            ->options(fn () => Category::pluck('name', 'id')->toArray())
                                                            ->searchable()
                                                            ->preload(),

                                                        Select::make('topic_id')
                                                            ->label('Topic Filter')
                                                            ->options(fn () => Topic::pluck('name', 'id')->toArray())
                                                            ->searchable()
                                                            ->preload(),

                                                        Select::make('selection_type')
                                                            ->options([
                                                                'random' => 'Random Pull',
                                                                'weighted' => 'Weighted (High Priority Items)',
                                                            ])->default('random'),
                                                    ]),
                                                Grid::make(3)
                                                    ->schema([
                                                        Select::make('difficulty')
                                                            ->options([
                                                                'easy' => 'Easy',
                                                                'medium' => 'Medium',
                                                                'hard' => 'Hard',
                                                            ]),

                                                        TextInput::make('question_count')
                                                            ->label('Rule Pull Count')
                                                            ->numeric()
                                                            ->default(5)
                                                            ->required(),

                                                        TextInput::make('tags')
                                                            ->label('Meta Tags Filtering')
                                                            ->placeholder('e.g. history, geography'),
                                                    ]),
                                            ])
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => 
                                                "Pull Rule: " . ($state['question_count'] ?? 5) . " items" . 
                                                (!empty($state['difficulty']) ? " [{$state['difficulty']}]" : "")
                                            ),
                                    ]),
                            ]),

                        // Tab 3: Native Manual Selection Matrix
                        Tab::make('Manual Selection')
                            ->schema([
                                Grid::make(1)->schema([
                                    Select::make('manual_category_filter')
                                        ->label('Filter Manual Selection Options By Category')
                                        ->options(fn () => Category::pluck('name', 'id')->toArray())
                                        ->searchable()
                                        ->preload()
                                        ->live(),

                                    Select::make('manualQuestions')
                                        ->relationship(
                                            name: 'manualQuestions',
                                            titleAttribute: 'id',
                                            modifyQueryUsing: function ($query, callable $get) {
                                                $categoryId = $get('manual_category_filter');
                                                return $query->when($categoryId, fn ($q) => $q->where('category_id', $categoryId));
                                            }
                                        )
                                        ->getOptionLabelFromRecordUsing(fn ($record) => "• [EN]: " . ($record->getTranslation('question_text', 'en') ?? data_get($record->question_text, 'en')) . " | [AM]: " . ($record->getTranslation('question_text', 'am') ?? data_get($record->question_text, 'am')))
                                        ->multiple()
                                        ->searchable()
                                        ->preload(),
                                ]),
                            ]),

                        // Tab 4: Reward Allocations
                        Tab::make('Rewards')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('reward_xp')
                                            ->numeric()
                                            ->label('Experience Points (XP) Reward')
                                            ->default(0),
                                        TextInput::make('reward_coins')
                                            ->numeric()
                                            ->label('Teyaqi Coins Reward')
                                            ->default(0),
                                    ]),
                            ]),

                        // Tab 5: Scheduling Constraints
                        Tab::make('Scheduling')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        DateTimePicker::make('start_at')
                                            ->label('Activation Timestamp')
                                            ->native(false),
                                        DateTimePicker::make('end_at')
                                            ->label('Deactivation Timestamp')
                                            ->native(false),
                                    ]),
                            ]),

                    ])
                    ->columnSpanFull(),
            ]);
    }
}