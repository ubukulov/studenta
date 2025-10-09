<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupReviewReportResource\Pages;
use App\Models\GroupReviewReport;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Resources\Table;
use Illuminate\Support\Str;

class GroupReviewReportResource extends Resource
{
    protected static ?string $model = GroupReviewReport::class;

    protected static ?string $navigationLabel = 'Жалобы на отзывы';
    protected static ?string $pluralModelLabel = 'Жалобы';
    protected static ?string $modelLabel = 'Жалоба';
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Отзывы';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'В ожидании',
                    'approved' => 'Подтверждена',
                    'rejected' => 'Отклонена',
                ])
                ->required()
                ->label('Статус'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Пользователь'),
                Tables\Columns\TextColumn::make('review_display')
                    ->label('Отзыв')
                    ->getStateUsing(fn($record) =>
                    $record->review
                        ? "#{$record->review->id}: " . Str::limit($record->review->comment, 80)
                        : '—'
                    ),

                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'spam' => '📢 Спам и реклама',
                        'abuse' => '⚠️ Оскорбление и мат',
                        'not_review' => '📝 Не отзыв',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => '🕒 В ожидании',
                            'approved' => '✅ Подтверждена',
                            'rejected' => '❌ Отклонена',
                            default => $state,
                        };
                    }),

                Tables\Columns\TextColumn::make('comment')->limit(50)->label('Комментарий'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->label('Дата создания'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'В ожидании',
                        'approved' => 'Подтверждена',
                        'rejected' => 'Отклонена',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип жалобы')
                    ->options([
                        'spam' => 'Спам и реклама',
                        'abuse' => 'Оскорбление и мат',
                        'not_review' => 'Не отзыв',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Подтвердить')
                    ->button()
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'approved']);
                        //$record->review?->delete();
                        $newStatus = match ($record->type) {
                            'spam' => 'spam',
                            'abuse' => 'abuse',
                            'not_review' => 'not_review',
                            default => 'inactive',
                        };

                        if ($record->review) {
                            $record->review->update(['status' => $newStatus]);
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->button()
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->update(['status' => 'rejected'])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroupReviewReports::route('/'),
        ];
    }
}
