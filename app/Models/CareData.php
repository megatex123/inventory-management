<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class CareData extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'care_id',
        'care_data_id',
        'customer_id',
        'order_id',
        'lkp_care_id',
        'total_part',
        'price',
        'update_membership',
    ];

    protected $casts = [
        'update_membership' => 'boolean',
        'total_part' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Membership status is no longer a manual toggle — it's derived from the
    // linked order's date plus the care tier's coverage period (COR3=3yr,
    // RI5E=5yr, VIS10N=10yr), so it always reflects actual remaining time.
    protected $appends = ['membership_active', 'membership_remaining'];

    /**
     * Date the QuiviCare coverage clock starts counting from — the order date,
     * falling back to this record's created_at if there's no linked order.
     */
    public function getMembershipStartDateAttribute()
    {
        return optional($this->order)->order_date ?: $this->created_at;
    }

    /**
     * Date the QuiviCare coverage period ends, or null if it can't be determined
     * (missing start date or an unparseable care tier period).
     */
    public function getMembershipExpiryDateAttribute()
    {
        $startDate = $this->membership_start_date;
        $years = optional($this->care)->period_years;

        if (!$startDate || !$years) {
            return null;
        }

        return Carbon::parse($startDate)->addYears($years);
    }

    /**
     * Whether QuiviCare membership is still within its coverage period.
     */
    public function getMembershipActiveAttribute()
    {
        $expiryDate = $this->membership_expiry_date;

        if (!$expiryDate) {
            return false;
        }

        return Carbon::now()->lte($expiryDate);
    }

    /**
     * Human-readable remaining time, matching the "X Months Y Days" style
     * already used for order approval expiry (see OrderController).
     */
    public function getMembershipRemainingAttribute()
    {
        $expiryDate = $this->membership_expiry_date;

        if (!$expiryDate) {
            return 'N/A';
        }

        $now = Carbon::now();

        if ($now->gt($expiryDate)) {
            return 'Expired';
        }

        $diff = $now->diff($expiryDate);
        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y . ' Year' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' Month' . ($diff->m > 1 ? 's' : '');
        }
        $parts[] = $diff->d . ' Day' . ($diff->d != 1 ? 's' : '');

        return implode(' ', $parts);
    }

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function care()
    {
        return $this->belongsTo(Care::class, 'lkp_care_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItems()
    {
        return $this->hasManyThrough(
            OrderDetails::class,
            Order::class,
            'id',
            'order_id',
            'order_id',
            'id'
        );
    }

    public function directOrderDetails()
    {
        return $this->hasMany(OrderDetails::class, 'order_id', 'order_id');
    }
}
