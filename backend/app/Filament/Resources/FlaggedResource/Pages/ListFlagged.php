<?php

namespace App\Filament\Resources\FlaggedResource\Pages;

use App\Filament\Resources\FlaggedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlagged extends ListRecords
{
    protected static string $resource = FlaggedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
