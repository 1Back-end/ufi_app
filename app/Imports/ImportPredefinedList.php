<?php

namespace App\Imports;

use App\Models\CatPredefinedList;
use App\Models\PredefinedList;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportPredefinedList implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return Model|PredefinedList|null
     */
    public function model(array $row): Model|PredefinedList|null
    {
        if (! $row['nom']) return null;

        $cat = CatPredefinedList::firstOrCreate([
            'slug' => str($row['cat'])->slug()
        ], [
            'name' => $row['cat'],
            'slug' => str($row['cat'])->slug()
        ]);

        return new PredefinedList([
            'name' => $row['nom'],
            'slug' => str($row['nom'] . (PredefinedList::max('id') + 1))->slug(),
            'cat_predefined_list_id' => $cat->id,
            'show' => true,
        ]);
    }
}
