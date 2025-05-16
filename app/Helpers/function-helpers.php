<?php

use App\Enums\StateFacture;
use App\Enums\TypePrestation;
use App\Models\Acte;
use App\Models\Centre;
use App\Models\Facture;
use App\Models\Media;
use App\Models\Prestation;
use App\Models\Soins;
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
        $fileName = $filename ? $filename . '.' . $extension : $file->getClientOriginalName();

        if ($update) {
            delete_media(
                $disk,
                $update->path . '/' . $update->filename,
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
        } else {
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

if (! function_exists('calculate_amount_facture')) {
    /**
     * @param Prestation $prestation
     * @return array
     */
    function calculate_amount_facture(Prestation $prestation): array
    {
        $amount = 0;
        $amount_pc = 0;
        $amount_remise = 0;
        $amount_client = 0;

        switch ($prestation->type) {
            case TypePrestation::ACTES:
                foreach ($prestation->actes as $acte) {
                    $pu = $acte->pu;

                    $amount_acte_pc = 0;
                    if ($prestation->priseCharge) {
                        $pu = $acte->b * $acte->k_modulateur;
                        $amount_acte_pc = ($acte->pivot->quantity * $pu * $prestation->priseCharge->taux_pc) / 100;
                        $amount_pc += $amount_acte_pc;
                    }

                    $amount_acte_remise = ($acte->pivot->quantity * $pu * $acte->pivot->remise) / 100;
                    $amount_remise += $amount_acte_remise;

                    $amount += $acte->pivot->quantity * $pu;
                    $amount_client += $acte->pivot->quantity * $pu - $amount_acte_remise - $amount_acte_pc;
                }
                break;
            case TypePrestation::SOINS:
                foreach ($prestation->soins as $soin) {
                    $pu = $soin->pu;
                    $amount_soin_pc = 0;
                    if ($prestation->priseCharge) {
                        $pu = $soin->pu_default;
                        $amount_soin_pc = ($soin->pivot->nbr_days * $pu * $prestation->priseCharge->taux_pc) / 100;
                        $amount_pc += $amount_soin_pc;
                    }

                    $amount_soin_remise = ($soin->pivot->nbr_days * $pu * $soin->pivot->remise) / 100;
                    $amount_remise += $amount_soin_remise;

                    $amount += $soin->pivot->nbr_days * $pu;
                    $amount_client += $soin->pivot->nbr_days * $pu - $amount_soin_remise - $amount_soin_pc;
                }
                break;
            case TypePrestation::CONSULTATIONS:
                throw new \Exception('To be implemented');
                break;
            case TypePrestation::PRODUITS:
                throw new \Exception('To be implemented');
                break;
            case TypePrestation::LABORATOIR:
                throw new \Exception('To be implemented');
                break;
        }


        return [$amount, $amount_pc, $amount_remise, $amount_client];
    }
}

if (! function_exists('save_facture')) {
    /**
     * @param Prestation $prestation
     * @param int $centre_id
     * @param int $type 1: Proforma, 2: Facture
     * @return Facture
     * @throws Exception
     */
    function save_facture(Prestation $prestation, int $centre_id, int $type): Facture
    {
        [$amount, $amount_pc, $amount_remise, $amount_client] = calculate_amount_facture($prestation);

        $latestFacture = Facture::whereType($type)
            ->where('centre_id', $centre_id)
            ->whereYear('created_at', now()->year)
            ->latest()->first();
        $sequence =  $latestFacture ? $latestFacture->sequence + 1 : 1;

        $facture = $prestation->factures()->where('type', $type)->first();
        if (! $facture) {
            $state = $prestation->payable_by || $amount_client <= 0 ? StateFacture::IN_PROGRESS->value : StateFacture::CREATE->value;

            $facture = Facture::create([
                'prestation_id' => $prestation->id,
                'date_fact' => now(),
                'amount' => $amount,
                'amount_pc' => $amount_pc,
                'amount_remise' => $amount_remise,
                'amount_client' => max($amount_client, 0),
                'type' => $type,
                'sequence' => $sequence,
                'centre_id' => $centre_id,
                'state' => $state,
                'code' => $prestation->centre->reference . '-' . date('ym') . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT)
            ]);
        } else {
            $facture->update([
                'amount' => $amount,
                'amount_pc' => $amount_pc,
                'amount_remise' => $amount_remise,
                'amount_client' => max($amount_client, 0),
            ]);
        }

        return $facture;
    }
}
