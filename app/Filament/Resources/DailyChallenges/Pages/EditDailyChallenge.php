<?php

namespace App\Filament\Resources\DailyChallenges\Pages;

use App\Filament\Resources\DailyChallenges\DailyChallengeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyChallenge extends EditRecord
{
    protected static string $resource = DailyChallengeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}