<?php

use App\Enums\StateExamen;
use App\Enums\StateFacture;
use App\Enums\TypePrestation;
use App\Models\Acte;
use App\Models\Centre;
use App\Models\ElementPaillasse;
use App\Models\Examen;
use App\Models\Facture;
use App\Models\Media;
use App\Models\Prestation;
use App\Models\Result;
use App\Models\Soins;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;

if (!function_exists('upload_media')) {
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

if (!function_exists('delete_media')) {
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

if (!function_exists('load_permissions')) {
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

if (!function_exists('calculate_amount_facture')) {
    /**
     * @param Prestation $prestation
     * @return array
     * @throws Exception
     */
    function calculate_amount_facture(Prestation $prestation): array
    {
        $amount = 0;
        $amount_pc = 0;
        $amount_remise = 0;
        $amount_client = 0;
        $amount_prelevement = 0;

        switch ($prestation->type) {
            case TypePrestation::ACTES:
                foreach ($prestation->actes as $acte) {
                    $pu = $acte->pivot->pu;

                    $amount_acte_pc = 0;
                    if ($prestation->priseCharge) {
                        $pu = $acte->pivot->b * $acte->pivot->k_modulateur;

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
                    $pu = $soin->pivot->pu;
                    $amount_soin_pc = 0;
                    if ($prestation->priseCharge) {
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
                foreach ($prestation->consultations as $consultation) {
                    $pu = $consultation->pivot->pu;
                    $amount_consultation_pc = 0;
                    if ($prestation->priseCharge) {
                        $amount_consultation_pc = ($consultation->pivot->quantity * $pu * $prestation->priseCharge->taux_pc) / 100;
                        $amount_pc += $amount_consultation_pc;
                    }

                    $amount_consultation_remise = ($consultation->pivot->quantity * $pu * $consultation->pivot->remise) / 100;
                    $amount_remise += $amount_consultation_remise;

                    $amount += $consultation->pivot->quantity * $pu;
                    $amount_client += ($consultation->pivot->quantity * $pu) - $amount_consultation_remise - $amount_consultation_pc;
                }
                break;
            case TypePrestation::PRODUITS:
                foreach ($prestation->products as $product) {
                    $pu = $product->pivot->pu;
                    $amount_product_pc = 0;

                    $amount_product_remise = ($product->pivot->quantity * $pu * $product->pivot->remise) / 100;
                    $amount_remise += $amount_product_remise;

                    $amount += $product->pivot->quantity * $pu;
                    $amount_client += ($product->pivot->quantity * $pu) - $amount_product_remise - $amount_product_pc;
                }
                break;
            case TypePrestation::LABORATOIR:

                $kbprelevementIds = [];
                foreach ($prestation->examens as $examen) {
                    $pu = $examen->pivot->pu;
                    $amount_examen_pc = 0;
                    if ($prestation->priseCharge) {
                        $pu = $examen->pivot->b * $prestation->priseCharge->quotation->taux;

                        $amount_examen_pc = ($examen->pivot->quantity * $pu * $prestation->priseCharge->taux_pc) / 100;
                        $amount_pc += $amount_examen_pc;
                    }

                    $amount_examen_remise = ($examen->pivot->quantity * $pu * $examen->pivot->remise) / 100;
                    $amount_remise += $amount_examen_remise;

                    if (!in_array($examen->kb_prelevement_id, $kbprelevementIds) && $prestation->apply_prelevement) {
                        $amount += $examen->kbPrelevement->amount;
                        $amount_prelevement += $examen->kbPrelevement->amount;
                        $kbprelevementIds[] = $examen->kb_prelevement_id;
                    }

                    $amount += $examen->pivot->quantity * $pu;
                    $amount_client += ($examen->pivot->quantity * $pu) - $amount_examen_remise - $amount_examen_pc;
                }

                if ($prestation->apply_prelevement) {
                    $amount_prelevement_pc = 0;
                    if ($prestation->priseCharge) {
                        $amount_prelevement_pc = ($amount_prelevement * $prestation->priseCharge->taux_pc) / 100;
                        $amount_pc += $amount_prelevement_pc;
                    }

                    $amount_client += $amount_prelevement_pc ? $amount_prelevement - $amount_prelevement_pc : $amount_prelevement;
                }

                break;
            case TypePrestation::HOSPITALISATION:
                foreach ($prestation->hospitalisations as $hospitalisation) {
                    $pu = $hospitalisation->pivot->pu;
                    $amount_hospitalisation_pc = 0;
                    if ($prestation->priseCharge) {
                        $amount_hospitalisation_pc = ($hospitalisation->pivot->quantity * $pu * $prestation->priseCharge->taux_pc) / 100;
                        $amount_pc += $amount_hospitalisation_pc;
                    }

                    $amount_hospitalisation_remise = ($hospitalisation->pivot->quantity * $pu * $hospitalisation->pivot->remise) / 100;
                    $amount_remise += $amount_hospitalisation_remise;

                    $amount += $hospitalisation->pivot->quantity * $pu;
                    $amount_client += ($hospitalisation->pivot->quantity * $pu) - $amount_hospitalisation_remise - $amount_hospitalisation_pc;
                }
                break;
        }


        return [$amount, $amount_pc, $amount_remise, $amount_client, $amount_prelevement];
    }
}

if (!function_exists('save_facture')) {
    /**
     * @param Prestation $prestation
     * @param int $centre_id
     * @param int $type 1: Proforma, 2: Facture
     * @return Facture
     * @throws Exception
     */
    function save_facture(Prestation $prestation, int $centre_id, int $type): Facture
    {
        [$amount, $amount_pc, $amount_remise, $amount_client, $amount_prelevement] = calculate_amount_facture($prestation);

        $latestFacture = Facture::whereType($type)
            ->where('centre_id', $centre_id)
            ->whereYear('created_at', now()->year)
            ->latest()->first();
        $sequence = $latestFacture ? $latestFacture->sequence + 1 : 1;

        // Log::info($amount_prelevement);

        $facture = $prestation->factures()->where('type', $type)->first();
        if (!$facture) {
            $state = $prestation->payable_by || $amount_client <= 0 ? StateFacture::IN_PROGRESS->value : StateFacture::CREATE->value;

            $facture = Facture::create([
                'prestation_id' => $prestation->id,
                'date_fact' => now(),
                'amount' => $amount,
                'amount_pc' => $amount_pc,
                'amount_remise' => $amount_remise,
                'amount_client' => max($amount_client, 0),
                'amount_prelevement' => $amount_prelevement,
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
                'amount_prelevement' => $amount_prelevement
            ]);
        }

        if ($facture->type == 2 && $prestation->payable_by) {
            $convention = $prestation->payableBy->conventionAssocies()
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->whereColumn('amount', '<=', 'amount_max')
                ->first();

            $convention->update([
                'amount' => $convention->amount + $facture->amount_client
            ]);
        }

        return $facture;
    }
}

if (!function_exists('save_browser_shot_pdf')) {
    /**
     * @param string $view
     * @param array $data
     * @param string $folderPath
     * @param string $path
     * @param string $format
     * @param string $direction
     * @param string $header
     * @param string $footer
     * @return void
     * @throws CouldNotTakeBrowsershot
     * @throws Throwable
     */
    function save_browser_shot_pdf(string $view, array $data, string $folderPath, string $path, string $format = 'a4', string $direction = '', string $header = '', string $footer = '', array $margins = [0, 0, 0, 0]): void
    {
        $bootstrapPath = public_path('assets/bootstrap/css/bootstrap.min.css');
        $bootstrapContent = file_get_contents($bootstrapPath);
        $data = array_merge($data, ['bootstrap' => $bootstrapContent]);

        $folderPath = public_path($folderPath);
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
        }


        $browserShot = Browsershot::html(view($view, $data)->render())
            ->format($format)
            ->margins($margins[0], $margins[1], $margins[2], $margins[3])
            ->showBackground();


        if (env('APP_ENV') == "production") {
            $browserShot->setChromePath('C:\chrome-headless\chrome-headless-shell.exe');
        }


        if ($header) {
            $browserShot->showBrowserHeaderAndFooter()
                ->hideFooter()
                ->headerHtml(view($header, $data)->render());
        }

        if ($footer) {
            $browserShot->showBrowserHeaderAndFooter()
                ->hideHeader()
                ->footerHtml(view($footer, $data)->render());
        }

        if ($direction) {
            $browserShot->landscape();
        }

        $browserShot->save($path);
    }
}

if (!function_exists('showResultExamen')) {
    /**
     * @param Prestation $prestation
     * @param Examen $examen
     * @return Result | null
     */
    function showResultExamen(Prestation $prestation, Examen $examen): Result|null
    {
        foreach ($prestation->results as $result) {
            if (($result->elementPaillasse->name == $examen->name || $result->elementPaillasse->name == 'Résultat' || $result->elementPaillasse->name == 'Resultat') && $result->elementPaillasse->examen_id === $examen->id) {
                return $result;
            }
        }

        return null;
    }
}

if (!function_exists('showResult')) {
    /**
     * @param Prestation $prestation
     * @param ElementPaillasse $elementPaillasse
     * @param Examen $examen
     * @return boolean
     */
    function showResult(Prestation $prestation, ElementPaillasse $elementPaillasse, Examen $examen): bool
    {
        if ($elementPaillasse->typeResult->type == 'group' || $elementPaillasse->typeResult->type == 'inline' || $elementPaillasse->typeResult->type == 'comment') {
            return true;
        }

        $result = $prestation->results()->where('element_paillasse_id', $elementPaillasse->id)->first();
        if (!$result) {
            return false;
        }

        return !!$result->result_client && $result->show && $elementPaillasse->name != $examen->name && $elementPaillasse->name != 'Résultat' && $elementPaillasse->name != 'Resultat';
    }
}

if (!function_exists('showExamHasResult')) {
    /**
     * @param Prestation $prestation
     * @param Examen $examen
     * @return boolean
     */
    function showExamHasResult(Prestation $prestation, Examen $examen): bool
    {
        if (! in_array($examen->pivot->status_examen->value, StateExamen::validated())) {
            return false;
        }

        foreach ($examen->elementPaillasses as $elementPaillasse) {
            if (in_array($elementPaillasse->id, $prestation->results()->pluck('element_paillasse_id')->toArray())) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('showPaillasseHasResult')) {
    /**
     * @param Prestation $prestation
     * @param Collection<Examen> $examens
     * @return boolean
     */
    function showPaillasseHasResult(Prestation $prestation, Collection $examens): bool
    {
        foreach ($examens as $examen) {
            if(showExamHasResult($prestation, $examen)) {
                return true;
            }
        }

        return false;
    }
}


