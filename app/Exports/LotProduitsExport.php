<?php

namespace App\Exports;

use App\Models\LotProduit;
use Maatwebsite\Excel\Concerns\FromCollection;

class LotProduitsExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return LotProduit::all();
    }
}
