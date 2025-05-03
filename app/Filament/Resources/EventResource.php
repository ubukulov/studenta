<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use App\Models\ImageUpload;
use Filament\Forms\Components\TextInput;
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
                    ->relationship('user', 'name') // Связь с пользователем
                    ->getOptionLabelUsing(function ($user) {
                        return $user->name ?? 'Без имени'; // Если имя null, отображается "Без имени"
                    })
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
                    ->reactive()
                    ->required()
                    ->label('Тип события'),

                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->rows(3),

                Forms\Components\TextInput::make('address')
                    ->label('Адрес'),

                Forms\Components\TextInput::make('two_gis')
                    ->label('2GIS'),

                Forms\Components\TextInput::make('cost')
                    ->label('Стоимость')
                    ->default(0)
                    ->hidden(fn (callable $get) => $get('type') == 'free'),

                Forms\Components\TextInput::make('count_place')
                    ->label('Кол-во мест'),

                Forms\Components\TextInput::make('kaspi_phone')
                    ->label('Kaspi Телефон'),

                Forms\Components\TextInput::make('kaspi_name')
                    ->label('Kaspi Имя'),

                Select::make('image_id')
                    ->label('Изображение')
                    ->options(
                        ImageUpload::all()
                            ->filter(fn ($item) => filled($item->image)) // исключаем null/пустые
                            ->mapWithKeys(fn ($item) => [
                                $item->id => (string) $item->image,
                            ])
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('image_preview')
                    ->label('Превью изображения')
                    ->disabled() // делаем поле не редактируемым
                    ->default(function (callable $get) {
                        $imageId = $get('image_id');

                        if (!$imageId) {
                            return 'Картинка не выбрана';
                        }

                        $image = ImageUpload::find($imageId);

                        if (!$image) {
                            return 'Картинка не найдена';
                        }

                        return asset('storage/' . $image->image); // Показываем путь к картинке как текст
                    })
                    ->visible(fn (callable $get) => filled($get('image_id')))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
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
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'free' => 'Бесплатно',
                        'paid' => 'Платно',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->label('Пользователь')
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
