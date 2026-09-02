<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\NotificationRule;
use App\Models\NotificationLog;
use App\Traits\SendsTelegramNotifications;

use Filament\Pages\Page;

// Schemas (v5)
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;

// Forms
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;

// Schemas
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;

// Tables
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

// Actions & Notifications
use Filament\Actions\Action;
use Filament\Notifications\Notification;

// Enums (required by Filament)
use BackedEnum;
use UnitEnum;

class SendNotification extends Page implements HasSchemas, HasForms, HasTable
{
    /**
     * Fix trait collision (Filament v5 requirement)
     */
    use InteractsWithSchemas, InteractsWithForms, InteractsWithTable {
        InteractsWithForms::getCachedSchemas insteadof InteractsWithSchemas;
        InteractsWithSchemas::getCachedSchemas as getCachedSchemaInstances;
    }

    use SendsTelegramNotifications;

    // ✅ MUST match Filament types EXACTLY
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected string $view = 'filament.pages.send-notification';

    public ?array $data = [];

    /**
     * INIT
     */
    public function mount(): void
    {
        $this->getSchema('form')->fill([
            'target_group' => 'all',
            'rules' => NotificationRule::all()->toArray(),
        ]);
    }

    /**
     * FORM (Schemas API)
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Notification Center')
                    ->tabs([

                        // 🔵 TAB 1: BROADCAST
                        Tabs\Tab::make('Manual Broadcast')
                            ->icon('heroicon-m-megaphone')
                            ->schema([

                                Section::make('Targeting')
                                    ->schema([
                                        Select::make('target_group')
                                            ->options([
                                                'all' => 'All Users',
                                                'zero_lives' => '0 Lives',
                                                'high_streak' => '5+ Streak',
                                                'inactive' => 'Inactive',
                                            ])
                                            ->default('all')
                                            ->native(false),
                                    ]),

                                Section::make('Message')
                                    ->schema([
                                        Textarea::make('message')
                                            ->required()
                                            ->rows(5),

                                        TextInput::make('button_text')
                                            ->label('Button Label'),

                                        TextInput::make('button_url')
                                            ->label('Button URL')
                                            ->url(),
                                    ]),
                            ]),

                        // 🟣 TAB 2: RULES
                        Tabs\Tab::make('Automation Rules')
                            ->icon('heroicon-m-cpu-chip')
                            ->schema([
                                Repeater::make('rules')
                                    ->schema([
                                        Select::make('event_type')
                                            ->options([
                                                'level_up' => 'Level Up',
                                                'streak_milestone' => 'Streak',
                                                'out_of_lives' => 'Out of Lives',
                                                'life_refilled' => 'Life Refilled',
                                            ])
                                            ->required(),

                                        TextInput::make('threshold')
                                            ->numeric()
                                            ->required(),

                                        Textarea::make('message_template')
                                            ->required()
                                            ->columnSpanFull(),

                                        Toggle::make('is_active')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * TABLE (🔥 NEW CLEAN FILAMENT TABLE)
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                NotificationLog::query()->with('user')->latest()
            )
            ->columns([

                TextColumn::make('user.name')
                    ->label('User')
                    ->default('Unknown')
                    ->searchable(),

                BadgeColumn::make('type')
                    ->colors([
                        'success' => 'manual',
                        'primary' => 'auto',
                        'warning' => 'preview',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state)),

                TextColumn::make('message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->message)
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->striped();
    }

    /**
     * ACTIONS
     */
    protected function getActions(): array
    {
        return [
            Action::make('send')
                ->label('Broadcast')
                ->color('success')
                ->action('send'),

            Action::make('preview')
                ->label('Preview')
                ->outlined()
                ->action('sendPreview'),

            Action::make('saveRules')
                ->label('Save Rules')
                ->action('saveRules'),
        ];
    }

    /**
     * SAVE RULES
     */
    public function saveRules(): void
    {
        $state = $this->getSchema('form')->getRawState();

        NotificationRule::truncate();

        foreach ($state['rules'] ?? [] as $rule) {
            NotificationRule::create($rule);
        }

        Notification::make()->title('Saved')->success()->send();
    }

    /**
     * PREVIEW
     */
    public function sendPreview(): void
    {
        $state = $this->getSchema('form')->getRawState();
        $user = auth()->user();

        if (!$user?->telegram_id) {
            Notification::make()->title('No Telegram ID')->danger()->send();
            return;
        }

        $this->dispatchTelegram(
            $user,
            $state['message'],
            null,
            $state['button_text'] ?? null,
            $state['button_url'] ?? null,
            'preview'
        );

        Notification::make()->title('Preview Sent')->success()->send();
    }

    /**
     * BROADCAST
     */
    public function send(): void
    {
        $state = $this->getSchema('form')->getRawState();

        $query = User::whereNotNull('telegram_id');

        $query = match ($state['target_group'] ?? 'all') {
            'zero_lives' => $query->where('daily_lives', 0),
            'high_streak' => $query->where('current_streak', '>=', 5),
            'inactive' => $query->where('current_streak', 0),
            default => $query,
        };

        $count = 0;

        foreach ($query->get() as $user) {
            if ($this->dispatchTelegram(
                $user,
                $state['message'],
                null,
                $state['button_text'] ?? null,
                $state['button_url'] ?? null,
                'manual'
            )) {
                $count++;
            }
        }

        Notification::make()
            ->title("Sent to {$count} users")
            ->success()
            ->send();
    }
}