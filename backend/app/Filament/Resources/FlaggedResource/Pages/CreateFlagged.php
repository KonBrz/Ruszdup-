<?php

namespace App\Filament\Resources\FlaggedResource\Pages;

use App\Filament\Resources\FlaggedResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFlagged extends CreateRecord
{
    protected static string $resource = FlaggedResource::class;
}
