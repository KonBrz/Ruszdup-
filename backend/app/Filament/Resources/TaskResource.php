<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),

                Select::make('priority')
                    ->label('Priority')
                    ->options([
                        'niski' => 'Niski',
                        'średni' => 'Średni',
                        'wysoki' => 'Wysoki',
                    ])
                    ->required(),

                TextInput::make('deadline')
                    ->label('Deadline')
                    ->type('date')
                    ->required(),

                Select::make('trip_id')
                    ->label('Trip')
                    ->relationship('trip', 'title')
                    ->required(),

                Select::make('assigned_to')
                    ->label('Assigned to')
                    ->relationship('user', 'name')
                    ->nullable()
                    ->searchable(),

                Toggle::make('completed')
                    ->label('Completed')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("title")->label('Title')->sortable()->searchable(),
                TextColumn::make('trip.id')->label('Trip Id')->sortable()->searchable(),
                TextColumn::make('user.name')->label('Username')->sortable()->searchable(),
                BooleanColumn::make('completed')->label('Completed')->sortable()->searchable(),
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
