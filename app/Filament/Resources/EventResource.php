<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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
use Filament\Tables\Columns\ImageColumn;

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
                        return optional($user)->name ?? 'Без имени';
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

                FileUpload::make('upload_image')
                    ->label('Загрузить изображение')
                    ->image()
                    ->multiple(false)
                    ->preserveFilenames()
                    ->imagePreviewHeight('200')
                    ->directory('events/images')   // где Filament будет сохранять новые загрузки
                    ->disk('public')               // куда сохранять новые файлы (можешь поменять)
                    // --- гидрация: передаём в компонент именно то, что в БД (массив)
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if (! $record || ! $record->imageUpload) {
                            return;
                        }

                        // $img может быть:
                        // - полный URL: http://domain/upload/images/xxx.jpg
                        // - полный URL: http://domain/storage/events/images/xxx.jpg
                        // - относительный путь: upload/images/xxx.jpg
                        // - относительный путь: events/images/xxx.jpg
                        $img = $record->imageUpload->image;

                        // всегда передаём массив (Filament ожидает array for file state)
                        $component->state([$img]);
                    })
                    // --- как FileUpload получает URL для предпросмотра
                    ->getUploadedFileUrlUsing(function ($state) {
                        if (! $state) {
                            return null;
                        }

                        // если state - массив (редкий случай), берём первый элемент
                        if (is_array($state)) {
                            $state = $state[0] ?? null;
                            if (! $state) return null;
                        }

                        // 1) если это абсолютный URL — возвращаем его как есть
                        if (preg_match('#^https?://#', $state)) {
                            return $state;
                        }

                        // 2) если начинается с '/storage/' (админские записи типа http://host/storage/...)
                        if (str_starts_with($state, '/storage/')) {
                            return url($state); // -> http://host/storage/...
                        }

                        // 3) если начинается с 'storage/' (без ведущего слеша)
                        if (str_starts_with($state, 'storage/')) {
                            return url('/' . $state);
                        }

                        // 4) если начинается с '/upload/' или 'upload/' — файлы в public/upload
                        if (str_starts_with($state, '/upload/')) {
                            return url($state);
                        }
                        if (str_starts_with($state, 'upload/')) {
                            return url('/' . $state);
                        }

                        // 5) иначе — предполагаем, что это путь относительно диска 'public' (storage/app/public)
                        //      и используем Storage::disk('public')->url(...)
                        return \Storage::disk('public')->url($state);
                    })
                    // --- при сохранении: из массива вернуть строку (первый элемент)
                    ->dehydrateStateUsing(function ($state) {
                        if (is_array($state)) {
                            return $state[0] ?? null;
                        }
                        return $state;
                    }),

                Hidden::make('image_id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('imageUpload.image') // если связь event -> imageUpload
                    ->label('Фото')
                    ->circular(),
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
