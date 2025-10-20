<?php

namespace App\Filament\Resources\UniversityResource\RelationManagers;

use App\Models\Speciality;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\RelationManagers\HasManyRelationManager;
use Filament\Tables;
use Filament\Forms;

class SpecialitiesRelationManager extends HasManyRelationManager
{
    protected static string $relationship = 'specialities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Названия специальности')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('code')
                    ->label('Код специальности')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable()->label('Названия'),
                Tables\Columns\TextColumn::make('code')->sortable()->searchable()->label('Код'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
