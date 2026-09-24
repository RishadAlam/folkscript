<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MediaUpload
{
    public function url(Media $media): string
    {
        $url = $media->getUrl('web');
        return config('filesystems.disks.'.$media->disk.'.driver') === 'local'
            ? (parse_url($url, PHP_URL_PATH) ?: $url)
            : $url;
    }

    public function store(User $user, UploadedFile $file, string $collection): Media
    {
        // Bound decoded image memory independently of the compressed file size.
        $dimensions = @getimagesize($file->getPathname());
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 24_000_000) {
            throw ValidationException::withMessages([$collection === 'stories' ? 'image' : $collection => 'Choose an image smaller than 24 megapixels.']);
        }

        return $user->addMedia($file)
            ->usingFileName(Str::uuid().'.'.$file->extension())
            ->toMediaCollection($collection, config('media-library.disk_name', 'public'));
    }
}
