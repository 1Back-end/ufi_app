<?php

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

if (! function_exists('upload_media')) {
    /**
     * Enregistré un média dans le disk spécifié et dans la table médias
     *
     * @param Model $model
     * @param UploadedFile $file
     * @param string $name
     * @param string $disk
     * @param string $path
     * @return void
     */
    function upload_media(Model $model, UploadedFile $file, string $name, string $disk, string $path): void
    {
        $fileName = $file->getClientOriginalName();
        $mimetype = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension();

        $file->storeAs(
            path: $path,
            name: $fileName,
            options: [
                'disk' => $disk
            ]
        );

        $model->medias()->create([
            'name' => $name,
            'disk' => $disk,
            'path' => $path,
            'filename' => $fileName,
            'mimetype' => $mimetype,
            'extension' => $extension
        ]);
    }
}

if (! function_exists('delete_media')) {
    /**
     * Supprimer le fichier dans le disk ou dans la table média
     *
     * @param string $disk
     * @param string $path
     * @param Media|null $media
     * @return void
     */
    function delete_media(string $disk, string $path, ?Media $media = null): void
    {
        Storage::disk($disk)->delete($path);
        $media?->delete();
    }
}
