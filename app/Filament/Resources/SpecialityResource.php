<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpecialityResource\Pages;
use App\Filament\Resources\SpecialityResource\RelationManagers;
use App\Models\Speciality;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SpecialityResource extends Resource
{
    protected static ?string $model = Speciality::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Справочники';
    protected static ?string $navigationLabel = 'Специалности';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSpecialities::route('/'),
            'create' => Pages\CreateSpeciality::route('/create'),
            'edit' => Pages\EditSpeciality::route('/{record}/edit'),
        ];
    }
}
