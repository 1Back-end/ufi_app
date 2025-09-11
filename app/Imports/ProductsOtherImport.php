<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Fournisseurs;
use App\Models\GroupProduct;
use App\Models\Product;
use App\Models\UniteProduit;
use App\Models\VoixTransmissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsOtherImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $authId = auth()->id();

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $name = $row['name'] ?? null;
                $pu = $row['pu'] ?? 0;
                $facturable = $row['facturable'] ?? false;

                if (!$name) {
                    Log::warning('Ligne ignorée : nom du produit manquant', ['row' => $row]);
                    continue;
                }

                // Récupération des relations par défaut
                $unite_produit = UniteProduit::first();
                $groupe_produit = GroupProduct::first();
                $categorie_produit = Category::first();
                $fournisseur = Fournisseurs::first();
                $voixtransmission = VoixTransmissions::first();

                $product = Product::where('name', $name)
                    ->where('is_deleted', false)
                    ->first();

                if ($product) {
                    // Mise à jour si le produit existe
                    $product->update([
                        'ref' => 'PROD' . now()->format('ymdHis') . mt_rand(10, 99),
                        'name' => $name,
                        'price' => $pu,
                        'dosage' => "A MODIFIER",
                        'facturable' => $facturable,
                        'unite_produits_id' => $unite_produit?->id,
                        'group_products_id' => $groupe_produit?->id,
                        'voix_transmissions_id' => $voixtransmission?->id,
                        'unite_par_emballage' => "A MODIFIER",
                        'condition_par_unite_emballage' => "A MODIFIER",
                        'Dosage_defaut' => "A MODIFIER",
                        'schema_administration' => "A MODIFIER",
                        'created_by' => $authId,
                        'updated_by' => $authId,
                        'status' => 'Actif',
                    ]);
                } else {
                    // Création si le produit n’existe pas
                    $product = Product::create([
                        'ref' => 'PROD' . now()->format('ymdHis') . mt_rand(10, 99),
                        'name' => $name,
                        'price' => $pu,
                        'dosage' => "A MODIFIER",
                        'facturable' => $facturable,
                        'unite_produits_id' => $unite_produit?->id,
                        'group_products_id' => $groupe_produit?->id,
                        'voix_transmissions_id' => $voixtransmission?->id,
                        'unite_par_emballage' => "A MODIFIER",
                        'condition_par_unite_emballage' => "A MODIFIER",
                        'Dosage_defaut' => "A MODIFIER",
                        'schema_administration' => "A MODIFIER",
                        'created_by' => $authId,
                        'updated_by' => $authId,
                        'status' => 'Actif',
                        'is_deleted' => false,
                    ]);
                }

                // Associer les catégories et fournisseurs
                if ($categorie_produit) {
                    $product->categories()->sync([$categorie_produit->id]);
                }
                if ($fournisseur) {
                    $product->fournisseurs()->sync([$fournisseur->id]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l’importation des produits : ' . $e->getMessage());
            throw $e;
        }
    }
}
