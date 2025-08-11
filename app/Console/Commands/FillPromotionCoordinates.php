<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Promotion;
use Illuminate\Support\Facades\Http;
class FillPromotionCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:fill-coordinates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Получить координаты для всех акций из address';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $promotions = Promotion::whereNull('latitude')
            ->whereNotNull('address')
            ->get();

        foreach ($promotions as $promotion) {
            $this->info("Обрабатываю: {$promotion->address}");

            $response = Http::get('https://geocode-maps.yandex.ru/1.x/', [
                'apikey'  => env('YANDEX_API_KEY'),
                'geocode' => $promotion->address,
                'format'  => 'json'
            ]);

            if ($response->failed()) {
                $this->error("Ошибка при запросе");
                continue;
            }

            $data = $response->json();

            $pos = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'] ?? null;

            if (!$pos) {
                $this->error("Координаты не найдены");
                continue;
            }

            [$lon, $lat] = explode(' ', $pos);

            $promotion->latitude = $lat;
            $promotion->longitude = $lon;
            $promotion->save();

            $this->info("Сохранено: lat={$lat}, lng={$lon}");
            sleep(1); // чтобы не заблокировали API
        }

        $this->info('Готово!');
    }
}
