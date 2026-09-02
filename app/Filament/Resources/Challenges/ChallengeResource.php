<?php

namespace App\Filament\Resources\Challenges;

use App\Filament\Resources\Challenges\Pages\CreateChallenge;
use App\Filament\Resources\Challenges\Pages\EditChallenge;
use App\Filament\Resources\Challenges\Pages\ListChallenges;
use App\Filament\Resources\Challenges\Schemas\ChallengesSchema;
use App\Filament\Resources\Challenges\Tables\ChallengesTable;
use App\Filament\Resources\Challenges\Schemas\ChallengeForm;
use App\Models\Challenge;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class ChallengeResource extends Resource
{
    protected static ?string $model = Challenge::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content Management';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $recordTitleAttribute = 'title';

   public static function form(Schema $schema): Schema
{
    return ChallengeForm::configure($schema);
}

public static function table(Table $table): Table
{
    return ChallengesTable::configure($table);
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
            'index' => ListChallenges::route('/'),
            'create' => CreateChallenge::route('/create'),
            'edit' => EditChallenge::route('/{record}/edit'),
        ];
    }
}