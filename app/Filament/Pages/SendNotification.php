<?php

namespace App\Filament\Pages;

use App\Jobs\PushNotificationJob;
use App\Models\City;
use App\Models\University;
use App\Models\Speciality;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\DB;

class SendNotification extends Page implements HasForms
{
    use InteractsWithForms;

    public ?int $city_id = null;
    public ?int $university_id = null;
    public ?int $specialty_id = null;
    public ?string $message_title = null;
    public ?string $message = null;
    public array $image = [];

    protected static ?string $navigationLabel = 'Отправить уведомление';
    protected static string $view = 'filament.pages.send-notification';

    protected static ?string $navigationGroup = 'Уведомления';
    protected static ?int $navigationSort = 10;

    public array $formData = [];

    protected function getFormSchema(): array
    {
        return [
            Select::make('city_id')
                ->label('Город')
                ->options(City::pluck('name', 'id'))
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set) => $set('university_id', null))
                ->required(),

            Select::make('university_id')
                ->label('Университет')
                ->options(fn ($get) => $get('city_id')
                    ? University::where('city_id', $get('city_id'))->pluck('name', 'id')
                    : [])
                ->searchable(),

            /*Select::make('specialty_id')
                ->label('Специальность')
                ->options(fn ($get) => $get('university_id')
                    ? Speciality::where('university_id', $get('university_id'))->pluck('name', 'id')
                    : [])
                ->searchable(),*/

            TextInput::make('message_title')->label('Заголовок')->required(),

            Textarea::make('message')->label('Текст уведомления')->required(),

            FileUpload::make('image')
                ->label('Картинка')
                ->image()
                ->directory('notifications/images')
                ->maxSize(2048),
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $users = User::where(['user_profile.city_id' => $data['city_id'], 'user_profile.university_id' => $data['university_id']])
            ->select('users.id', 'users.device_token')
            ->join('user_profile', 'users.id', '=', 'user_profile.user_id')
            ->whereNotNull('users.device_token')
            ->get();

        $notifications = [];
        $tokens = [];

        if($users->count() > 0){
            foreach ($users as $user) {
                $notifications[] = [
                    'user_id' => $user->id,
                    'type' => 'user',
                    'title' => $data['message_title'],
                    'message' => $data['message'],
                    'status'  => 'new',
                    'image' => $data['image'] ?? null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                $tokens[] = $user->device_token;
            }

            Notification::insert($notifications);

            if (!empty($tokens)) {
                PushNotificationJob::dispatch(
                    $tokens,
                    $data['message_title'],
                    $data['message'],
                    ['type' => 'user'],
                    $data['image'] ?? null
                );
            }

            session()->flash('success', '✅ Уведомление добавлено в очередь');
        } else {
            session()->flash('success', 'Сейчас нет студентов :(');
        }

        // Очистка формы
        $this->city_id = null;
        $this->university_id = null;
        $this->specialty_id = null;
        $this->message_title = null;
        $this->message = null;
        $this->image = [];
    }

    protected function getFormModel(): string
    {
        return Notification::class;
    }
}
