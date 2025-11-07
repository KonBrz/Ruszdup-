<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FlaggedResource\Pages;
use App\Filament\Resources\FlaggedResource\RelationManagers;
use App\Models\Flagged;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\FlagStatus;
use App\Enums\Decision;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FlaggedResource extends Resource
{
    protected static ?string $model = Flagged::class;
    protected static ?string $slug = 'flagged';
    protected static ?string $modelLabel = 'Flag'; // nazwa pojedyncza
    protected static ?string $pluralModelLabel = 'Flags'; // nazwa mnoga

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('user_id')
                    ->label('User')
                    ->content(fn($record) => $record?->user?->name)
                    ->hidden(fn($record) => blank($record?->user?->name)),

                Placeholder::make('trip_id')
                    ->label('Trip')
                    ->content(fn($record) => $record?->trip?->title)
                    ->hidden(fn($record) => blank($record?->trip?->title)),

                Placeholder::make('task_id')
                    ->label('Task')
                    ->content(fn($record) => $record?->task?->title)
                    ->hidden(fn($record) => blank($record?->task?->title)),

                Placeholder::make('reason')
                    ->label('Reason')
                    ->content(fn($record) => $record?->reason ?? '-'),

                Select::make('decision')
                    ->label('Decision')
                    ->options([
                        Decision::None->value => 'Brak decyzji',
                        Decision::Remove->value => 'Usuń powiązany rekord',
                        Decision::UpToStandard->value => 'Uznano za OK',
                    ]),

                Toggle::make('is_closed')
                    ->label('Is closed')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("id")->label('id')->sortable()->searchable(),
                TextColumn::make("user.name")->label('User')->sortable()->searchable(),
                TextColumn::make("trip.title")->label('Trip')->sortable()->searchable(),
                TextColumn::make("task.title")->label('Task')->sortable()->searchable(),
                BooleanColumn::make('is_closed')->label('Closed')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListFlagged::route('/'),
            'create' => Pages\CreateFlagged::route('/create'),
            'edit' => Pages\EditFlagged::route('/{record}/edit'),
        ];
    }
}
