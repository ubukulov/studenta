<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationLabel = 'Ивенты';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name') // Нужно чтобы в Event была связь user()
                    ->required()
                    ->label('Пользователь'),

                Select::make('group_id')
                    ->relationship('group', 'name') // Нужно чтобы в Event была связь group()
                    ->required()
                    ->label('Группа'),

                DateTimePicker::make('start_date')
                    ->required()
                    ->label('Дата начала'),

                DateTimePicker::make('end_date')
                    ->required()
                    ->label('Дата окончания'),

                Select::make('type')
                    ->options([
                        'free' => 'Бесплатный',
                        'paid' => 'Платный',
                    ])
                    ->required()
                    ->label('Тип события'),

                Select::make('image_id')
                    ->relationship('image', 'image') // поле "image" - это путь к картинке
                    ->label('Изображение')
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        return view('components.image-select-option', ['image' => $record->image])->render();
                    })
                    ->disableLabel() // если не хочешь дублировать название
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Пользователь'),
                Tables\Columns\TextColumn::make('group.name')->label('Группа'),
                Tables\Columns\TextColumn::make('start_date')->label('Начало'),
                Tables\Columns\TextColumn::make('end_date')->label('Окончание'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'free',
                        'danger' => 'paid',
                    ])
                    ->label('Тип'),
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
