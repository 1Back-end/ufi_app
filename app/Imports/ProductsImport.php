<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Fournisseurs;
use App\Models\GroupProduct;
use App\Models\Product;
use App\Models\UniteProduit;
use App\Models\VoixTransmissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Faker\Factory as Faker;

class ProductsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $authId = auth()->id();
        $faker = Faker::create();

        // Ignore la ligne d'en-tête
        $rows->shift();

        $existing = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                try {
                    $Nom = trim((string) ($row[0] ?? ''));
                    $Dosage = trim((string) ($row[1] ?? ''));
                    $Prix = trim((string) ($row[2] ?? 0));
                    $UniteEmballage = trim((string) ($row[3] ?? ''));
                    $ConditionUniteEmballage = trim((string) ($row[4] ?? ''));
                    $DosageDefaut = trim((string) ($row[5] ?? ''));
                    $SchemasAdministration = trim((string) ($row[6] ?? ''));
                    $Facturable = strtolower(trim((string) ($row[7] ?? 'non'))) === 'oui';

                    // Ignorer si nom vide ou doublon
                    if (empty($Nom)) continue;
                    $key = strtolower($Nom . '_' . $Dosage);
                    if (in_array($key, $existing)) continue;
                    $existing[] = $key;

                    // Création ou récupération du schema d'administration
                    $voix_administration = VoixTransmissions::firstOrCreate(
                        ['name' => $SchemasAdministration ?: 'Non défini'],
                        [
                            'code' => collect(explode(' ', $SchemasAdministration ?: 'Non défini'))
                                ->map(fn($word) => strtoupper($word[0] ?? ''))
                                ->join(''),
                            'created_by' => $authId,
                            'updated_by' => $authId
                        ]
                    );

                    $unite_produit = UniteProduit::first();
                    $groupe_produit = GroupProduct::first();
                    $categorie_produit = Category::first();
                    $fournisseurs = Fournisseurs::first();

                    if (!$unite_produit || !$groupe_produit || !$categorie_produit || !$fournisseurs) {
                        Log::warning("Références manquantes à la ligne $index");
                        continue;
                    }

                    // Création du produit
                    $product = Product::create([
                        'ref' => 'PROD' . now()->format('ymdHis') . mt_rand(10, 99),
                        'name' => $Nom,
                        'dosage' => $Dosage,
                        'facturable' => $Facturable,
                        'voix_transmissions_id' => $voix_administration->id,
                        'price' => $Prix,
                        'unite_produits_id' => $unite_produit->id,
                        'group_products_id' => $groupe_produit->id,
                        'unite_par_emballage' => $UniteEmballage,
                        'condition_par_unite_emballage' => $ConditionUniteEmballage,
                        'Dosage_defaut' => $DosageDefaut,
                        'schema_administration' => $SchemasAdministration,
                        'created_by' => $authId,
                        'updated_by' => $authId,
                        'status' => 'Actif'
                    ]);

                    // Associer catégories et fournisseurs par défaut
                    $product->categories()->sync([$categorie_produit->id]);
                    $product->fournisseurs()->sync([$fournisseurs->id]);

                } catch (\Exception $e) {
                    Log::error("Erreur ligne $index : " . $e->getMessage());
                    continue; // passe à la ligne suivante au lieu de bloquer
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur importation générale : " . $e->getMessage());
        }
    }


}
