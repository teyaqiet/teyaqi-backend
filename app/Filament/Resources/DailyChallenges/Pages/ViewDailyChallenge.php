<?php

namespace App\Filament\Resources\DailyChallenges\Pages;

use App\Filament\Resources\DailyChallenges\DailyChallengeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDailyChallenge extends ViewRecord
{
    protected static string $resource = DailyChallengeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}