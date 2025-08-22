<?php

namespace App\Http\Controllers;

use App\Imports\UploadExistingDataImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UploadExistingDataController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        Excel::import(new UploadExistingDataImport, $request->file('file'));

        return response()->json(['message' => 'Data imported successfully.']);
    }
}
