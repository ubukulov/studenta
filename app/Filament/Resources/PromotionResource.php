<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromotionResource\Pages;
use App\Filament\Resources\PromotionResource\RelationManagers;
use App\Models\ImageUpload;
use App\Models\Promotion;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\ImageColumn;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationLabel = 'Акции';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('organization_id')
                    ->relationship('organization', 'name') // Нужно чтобы в Event была связь group()
                    ->required()
                    ->label('Организация'),

                Select::make('category_id')
                    ->relationship('category', 'name') // Связь с пользователем
                    ->getOptionLabelUsing(function ($user) {
                        return optional($user)->name ?? 'Без имени';
                    })
                    ->required()
                    ->label('Категория'),

                Forms\Components\TextInput::make('establishments_name')
                    ->label('Название')
                    ->required(),

                Forms\Components\TextInput::make('size')
                    ->label('Размер')
                    ->required(),

                Forms\Components\TextInput::make('address')
                    ->label('Адрес')
                    ->required(),

                Forms\Components\TextInput::make('description')
                    ->label('Описание')
                    ->required(),

                Forms\Components\TextInput::make('two_gis')
                    ->label('2GIS')
                    ->required(),

                Forms\Components\TextInput::make('seats')
                    ->label('Кол-во мест')
                    ->required(),


                DateTimePicker::make('start_date')
                    ->required()
                    ->label('Дата начала'),

                DateTimePicker::make('end_date')
                    ->required()
                    ->label('Дата окончания'),

                Repeater::make('images')
                    ->relationship('images')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Изображение')
                            ->image()
                            ->directory('promotions/images')
                            ->visibility('public')
                            ->enableDownload()
                            ->enableOpen()
                            ->maxSize(2048)
                            ->columnSpan(1), // Левая колонка

                        FileUpload::make('video')
                            ->label('Видео')
                            ->acceptedFileTypes(['video/mp4', 'video/avi', 'video/mov'])
                            ->directory('promotions/videos')
                            ->visibility('public')
                            ->enableDownload()
                            ->enableOpen()
                            ->maxSize(10240)
                            ->columnSpan(1), // Правая колонка
                    ])
                    ->columns(2) // Две колонки
                    ->label('Медиа')
                    ->minItems(1)
                    ->maxItems(10)
                    ->createItemButtonLabel('Добавить медиа'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('establishments_name'),
                Tables\Columns\TextColumn::make('organization.name')->label('Организация'),
                Tables\Columns\TextColumn::make('category.name')->label('Категория'),
                Tables\Columns\TextColumn::make('seats')->label('Кол-во мест'),
                Tables\Columns\TextColumn::make('size')->label('Размер'),
                Tables\Columns\TextColumn::make('start_date')->label('Начало'),
                Tables\Columns\TextColumn::make('end_date')->label('Окончание'),
                ImageColumn::make('images.image')
                    ->label('Изображение')
                    ->getStateUsing(fn ($record) => $record->images->first()?->image)
                    ->disk('public')
                    ->size(60)
                    ->circular(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }
}
