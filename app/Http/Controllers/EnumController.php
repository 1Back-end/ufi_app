<?php

namespace App\Http\Controllers;

use App\Enums\AntecedentSubType;
use App\Enums\AntecedentType;
use App\Enums\InvoiceStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderType;
use App\Enums\RendezVousStatus;
use App\Enums\StockAdjustmentAction;
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

    public function StockAdjustmentStatus()
    {
        return response()->json([
            'status' => 'success',
            'data'   => StockAdjustmentAction::toArray(),
        ]);
    }

    public function RendezVousStatus()
    {
        return response()->json([
            'status' => 'success',
            'data'   => RendezVousStatus::toArray(),
        ]);
    }

    public function InvoiceStatus()
    {
        return response()->json([
            'status' => 'success',
            'data'   => InvoiceStatus::toArray(),
        ]);
    }

    public function AntecedentSubType()
    {
        return response()->json([
            'status' => 'success',
            'data'   => AntecedentSubType::toArray(),
        ]);
    }
    public function AntecedentType()
    {
        return response()->json([
            'status' => 'success',
            'data'   => AntecedentType::toArray(),
        ]);
    }
}
