<?php

namespace App\Filament\Resources\DailyChallenges\Schemas;

use Filament\Schemas\Schema;

class DailyChallengeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Form properties can be kept empty since these logs are managed via API
            ]);
    }
}