<?php

use App\Models\Centre;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
     * @param string|null $filename
     * @param Media|null $update
     * @return void
     */
    function upload_media(Model $model, UploadedFile $file, string $name, string $disk, string $path, string $filename = null, Media $update = null): void
    {
        $mimetype = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension();
        $fileName = $filename ? $filename .'.'. $extension : $file->getClientOriginalName();

        if ($update) {
            delete_media(
                $disk,
                $update->path .'/'. $update->filename,
                $update
            );
        }

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

if (! function_exists('load_permissions')) {
    /**
     * Retourne toutes les permissions d’un utilisateur
     *
     * @param User $user
     * @param Centre|null $centre
     * @return array
     */
    function load_permissions(User $user, ?Centre $centre = null): array
    {
        if ($centre) {
            $permissionsByCentre = $user->permissions()
                ->where('permissions.active', true)
                ->wherePivot('active', true)
                ->where(function (Builder $query) use ($centre) {
                    $query->where('model_has_permissions.centre_id', $centre->id)
                        ->orWhereNull('model_has_permissions.centre_id');
                })
                ->pluck('name')
                ->toArray();

            $roles = $user->roles()
                ->where('roles.active', true)
                ->wherePivot('active', true)
                ->get();

            $permissionsByRole = collect();
            foreach ($roles as $role) {
                $permissionByRole = $role->permissions()
                    ->where('permissions.active', true)
                    ->wherePivot('active', true)
                    ->where(function (Builder $query) use ($centre) {
                        $query->where('role_has_permissions.centre_id', $centre->id)
                            ->orWhereNull('role_has_permissions.centre_id');
                    })
                    ->pluck('permissions.name')
                    ->toArray();

                $permissionsByRole->push(...$permissionByRole);
            }

            $permissions = collect([...$permissionsByCentre, ...$permissionsByRole])->unique()->flatten()->toArray();
        }
        else {
            $permission = $user->permissions()
                ->where('permissions.active', true)
                ->wherePivot('active', true)
                ->pluck('name')
                ->toArray();

            $roles = $user->roles()
                ->where('roles.active', true)
                ->wherePivot('active', true)
                ->get();

            $permissionsByRole = collect();
            foreach ($roles as $role) {
                $permissionByRole = $role->permissions()
                    ->where('permissions.active', true)
                    ->wherePivot('active', true)
                    ->pluck('permissions.name')
                    ->toArray();

                $permissionsByRole->push(...$permissionByRole);
            }

            $permissions = collect([...$permission, ...$permissionsByRole])->unique()->flatten()->toArray();
        }

        return $permissions;
    }
}
