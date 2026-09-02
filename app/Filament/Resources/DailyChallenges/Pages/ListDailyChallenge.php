<?php

namespace App\Filament\Resources\DailyChallenges\Pages;

use App\Filament\Resources\DailyChallenges\DailyChallengeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDailyChallenge extends ListRecords
{
    protected static string $resource = DailyChallengeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}