<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServeBek extends Model
{
    use SoftDeletes;

    protected $table = 'serve_bek';

    protected $fillable = [
        'serve_bek_id',
        'serve_data_id',
        'date_start',
        'one_year_assembly_warranty',
        'one_free_onsite_troubleshooting_first_3_months',
        'one_free_onsite_troubleshooting_claim_1',
        'one_free_onsite_troubleshooting_claim_1_date',
        'one_basic_cable_management_3_months',
        'one_basic_cable_management_claim_1',
        'one_basic_cable_management_claim_1_date',
        'fifty_percent_off_dust_cleaning_first_year',
        'fifty_percent_off_dust_cleaning_claim_1',
        'fifty_percent_off_dust_cleaning_claim_1_date',
    ];

    protected $casts = [
        'date_start' => 'date',
        'one_free_onsite_troubleshooting_claim_1_date' => 'date',
        'one_basic_cable_management_claim_1_date' => 'date',
        'fifty_percent_off_dust_cleaning_claim_1_date' => 'date',
        'one_year_assembly_warranty' => 'boolean',
        'one_free_onsite_troubleshooting_first_3_months' => 'boolean',
        'one_free_onsite_troubleshooting_claim_1' => 'boolean',
        'one_basic_cable_management_3_months' => 'boolean',
        'one_basic_cable_management_claim_1' => 'boolean',
        'fifty_percent_off_dust_cleaning_first_year' => 'boolean',
        'fifty_percent_off_dust_cleaning_claim_1' => 'boolean',
    ];

    /**
     * Relationship with ServeData
     */
    public function serveData()
    {
        return $this->belongsTo(ServeData::class, 'serve_data_id');
    }

    /**
     * Accessor to get qvse_cid from related serve_data
     */
    public function getQvseCidAttribute()
    {
        return $this->serveData ? $this->serveData->qvse_cid : null;
    }

    /**
     * Scope to filter by serve_data_id
     */
    public function scopeByServeData($query, $serveDataId)
    {
        return $query->where('serve_data_id', $serveDataId);
    }

    /**
     * Scope to filter active records (not deleted)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Check if a claim is available
     */
    public function isClaimAvailable($claimType)
    {
        $claimFields = [
            'troubleshooting' => [
                'total' => 'one_free_onsite_troubleshooting_first_3_months',
                'used' => 'one_free_onsite_troubleshooting_claim_1',
            ],
            'cable_management' => [
                'total' => 'one_basic_cable_management_3_months',
                'used' => 'one_basic_cable_management_claim_1',
            ],
            'dust_cleaning' => [
                'total' => 'fifty_percent_off_dust_cleaning_first_year',
                'used' => 'fifty_percent_off_dust_cleaning_claim_1',
            ],
        ];

        if (!isset($claimFields[$claimType])) {
            return false;
        }

        $field = $claimFields[$claimType];
        return $this->{$field['total']} && !$this->{$field['used']};
    }
}
