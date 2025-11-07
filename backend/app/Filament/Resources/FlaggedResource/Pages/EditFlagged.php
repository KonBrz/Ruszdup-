<?php

namespace App\Filament\Resources\FlaggedResource\Pages;

use App\Filament\Resources\FlaggedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFlagged extends EditRecord
{
    protected static string $resource = FlaggedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
