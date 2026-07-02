<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderType;
use Illuminate\Http\Request;

class EnumController extends Controller
{
    public function purchaseOrderTypes()
    {
        return response()->json([
            'status' => 'success',
            'data'   => PurchaseOrderType::toArray(),
        ]);
    }
    public function PurchaseOrderStatus()
    {
        return response()->json([
            'status' => 'success',
            'data'   => PurchaseOrderStatus::toArray(),
        ]);
    }
}
