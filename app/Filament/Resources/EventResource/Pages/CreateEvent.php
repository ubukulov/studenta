<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Livewire\TemporaryUploadedFile;
use App\Models\ImageUpload;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        Log::info('EditEvent::mutateFormDataBeforeSave - incoming keys: ' . implode(',', array_keys($data)));
        Log::info('EditEvent::mutateFormDataBeforeSave - upload_image type: ' . (isset($data['upload_image']) ? gettype($data['upload_image']) : 'null'));
        if (isset($data['upload_image'])) {
            Log::info('EditEvent::mutateFormDataBeforeSave - upload_image dump: ' . (is_string($data['upload_image']) ? substr($data['upload_image'], 0, 200) : get_class($data['upload_image'])));
        }

        if (empty($data['upload_image'])) {
            return $data;
        }

        if ($data['upload_image'] instanceof \Livewire\TemporaryUploadedFile
            || $data['upload_image'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {

            try {
                $path = $data['upload_image']->store('events/images', 'public');

                $image = ImageUpload::create([
                    'image' => env('APP_URL') . "/storage/" . $path,
                ]);

                $data['image_id'] = $image->id;

                Log::info('EditEvent - saved uploaded TemporaryUploadedFile', ['path' => $path, 'image_id' => $image->id]);
            } catch (\Throwable $e) {
                Log::error('EditEvent - error saving uploaded TemporaryUploadedFile: ' . $e->getMessage());
            }

            unset($data['upload_image']);
            return $data;
        }

        if (is_string($data['upload_image']) && str_starts_with($data['upload_image'], 'livewire-file:')) {
            try {
                $tempFile = null;
                if (class_exists(\Livewire\Features\SupportFileUploads\TemporaryUploadedFile::class)) {
                    $tempFile = \Livewire\Features\SupportFileUploads\TemporaryUploadedFile::createFromLivewire($data['upload_image']);
                } elseif (class_exists(\Livewire\TemporaryUploadedFile::class)) {
                    $tempFile = \Livewire\TemporaryUploadedFile::createFromLivewire($data['upload_image']);
                }

                if ($tempFile) {
                    $path = $tempFile->storeAs('events/images', $tempFile->getClientOriginalName(), 'public');

                    $image = ImageUpload::create([
                        'image' => env('APP_URL') . "/storage/" . $path,
                    ]);

                    $data['image_id'] = $image->id;

                    Log::info('EditEvent - created from livewire-file string', ['path' => $path, 'image_id' => $image->id]);
                } else {
                    Log::warning('EditEvent - TemporaryUploadedFile class not found to create from livewire-file string');
                }
            } catch (\Throwable $e) {
                Log::error('EditEvent - error processing livewire-file string: ' . $e->getMessage());
            }

            unset($data['upload_image']);
            return $data;
        }

        if (is_string($data['upload_image'])) {
            try {
                $image = ImageUpload::create([
                    'image' => env('APP_URL') . "/storage/" . $data['upload_image'],
                ]);
                $data['image_id'] = $image->id;
                Log::info('EditEvent - fallback saved string to image_uploads', ['value' => substr($data['upload_image'], 0, 200), 'image_id' => $image->id]);
            } catch (\Throwable $e) {
                Log::error('EditEvent - fallback saving failed: ' . $e->getMessage());
            }
            unset($data['upload_image']);
        }

        return $data;
    }
}
