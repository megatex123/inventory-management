<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDraft extends Model
{
    protected $table = 'order_drafts';

    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'draft_id',
        'customer_id',
        'qty',
        'sub_total',
        'total',
        'craft_id',
        'serve_id',
        'care_id',
        'order_details_snapshot',
    ];

    protected $casts = [
        'order_details_snapshot' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Generate the next draft revision id for a given order, e.g.
     * "BLDP-DRF-000042-01" then "BLDP-DRF-000042-02" for that SAME order's
     * next edit. Scoped per-order, unlike App\Support\BusinessId::next()
     * (which finds the next number globally per table/column) -- this
     * counter only ever looks at rows belonging to $order.
     */
    public static function nextDraftId(Order $order): string
    {
        preg_match('/(\d+)$/', (string) $order->order_id, $matches);
        $orderNumber = $matches[1] ?? str_pad('0', 6, '0', STR_PAD_LEFT);

        $revisionNumber = self::where('order_id', $order->id)->count() + 1;

        return 'BLDP-DRF-' . $orderNumber . '-' . str_pad((string) $revisionNumber, 2, '0', STR_PAD_LEFT);
    }
}
