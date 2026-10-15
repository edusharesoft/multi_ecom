<?php

namespace App\Http\Controllers;

use App\Models\CourierAccount;
use App\Models\Shipment;
use App\Services\Courier\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Courier status webhook: https://your-site/webhooks/courier/{token}
 * We never trust the posted status — we look up the parcel and pull its status from the courier API.
 */
class CourierWebhookController extends Controller
{
    public function __invoke(Request $request, string $token, ShipmentService $shipments)
    {
        $acc = CourierAccount::where('webhook_token', $token)->where('is_active', true)->first();
        if (! $acc) {
            return response()->json(['status' => 'error', 'message' => 'unknown'], 404);
        }
        $p = $request->all();
        $ids = array_filter([
            $p['consignment_id'] ?? null, $p['tracking_code'] ?? null, $p['tracking_id'] ?? null, $p['cid'] ?? null,
            $p['data']['consignment_id'] ?? null, $p['merchant_order_id'] ?? null, $p['invoice'] ?? null,
        ], fn ($v) => is_scalar($v) && $v !== '');

        $shipment = $ids ? Shipment::where('branch_id', $acc->branch_id)->where('courier', $acc->courier)
            ->where(fn ($q) => $q->whereIn('consignment_id', $ids)->orWhereIn('tracking_code', $ids)
                ->orWhereHas('order', fn ($o) => $o->whereIn('order_number', $ids)))
            ->latest('id')->first() : null;

        if ($shipment) {
            try {
                $shipments->sync($shipment);
            } catch (Throwable $e) {
                Log::warning("[CourierWebhook] sync failed #{$shipment->id}: ".$e->getMessage());
            }
        }

        // Pathao verifies the endpoint with this header
        return response()->json(['status' => 'success'], $acc->courier === 'pathao' ? 202 : 200)
            ->header('X-Pathao-Merchant-Webhook-Integration-Secret', 'f3992ecc-59da-4cbe-a049-a13da2018d51');
    }
}
