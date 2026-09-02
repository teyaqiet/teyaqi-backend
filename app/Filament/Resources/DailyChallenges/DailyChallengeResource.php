<?php

namespace App\Filament\Resources\DailyChallenges;

use App\Filament\Resources\DailyChallenges\Pages\CreateDailyChallenge;
use App\Filament\Resources\DailyChallenges\Pages\EditDailyChallenge;
use App\Filament\Resources\DailyChallenges\Pages\ListDailyChallenge;
use App\Filament\Resources\DailyChallenges\Pages\ViewDailyChallenge;
use App\Filament\Resources\DailyChallenges\Schemas\DailyChallengeForm;
use App\Filament\Resources\DailyChallenges\Schemas\DailyChallengeInfolist;
use App\Filament\Resources\DailyChallenges\Tables\DailyChallengesTable;
use App\Models\DailyChallenge;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class DailyChallengeResource extends Resource
{
    protected static ?string $model = DailyChallenge::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content Management';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return DailyChallengeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DailyChallengeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyChallengesTable::configure($table);
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
            'index' => ListDailyChallenge::route('/'),
            'create' => CreateDailyChallenge::route('/create'),
            'view' => ViewDailyChallenge::route('/{record}'),
            'edit' => EditDailyChallenge::route('/{record}/edit'),
        ];
    }
}