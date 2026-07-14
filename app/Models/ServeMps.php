<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServeMps extends Model
{
    use SoftDeletes;

    protected $table = 'serve_mps';

    protected $fillable = [
        'serve_data_id',
        'serve_mps_id',
        'date_start',
        'two_year_assembly_warranty',
        'two_free_onsite_troubleshooting_first_6_months',
        'two_free_onsite_troubleshooting_claim_1',
        'two_free_onsite_troubleshooting_claim_1_date',
        'two_free_onsite_troubleshooting_claim_2',
        'two_free_onsite_troubleshooting_claim_2_date',
        'two_advance_cable_management_first_year',
        'two_advance_cable_management_claim_1',
        'two_advance_cable_management_claim_1_date',
        'two_advance_cable_management_claim_2',
        'two_advance_cable_management_claim_2_date',
        'one_free_dust_cleaning_first_year',
        'one_free_dust_cleaning_claim',
        'one_free_dust_cleaning_claim_date',
        'fifty_percent_off_dust_cleaning_second_year',
        'fifty_percent_off_dust_cleaning_claim_date',
        'thirty_percent_off_labour_fees_upgrade_first_year',
        'thirty_percent_off_labour_fees_claim_date',
        'thirty_percent_off_dust_cleaning',
        'thirty_percent_off_dust_cleaning_claim_date',
        'rm100_promo_code_next_build',
        'generate_code',
        'rm100_promo_code_claim',
    ];

    protected $casts = [
        'date_start' => 'date',
        'two_free_onsite_troubleshooting_claim_1_date' => 'date',
        'two_free_onsite_troubleshooting_claim_2_date' => 'date',
        'two_advance_cable_management_claim_1_date' => 'date',
        'two_advance_cable_management_claim_2_date' => 'date',
        'one_free_dust_cleaning_claim_date' => 'date',
        'fifty_percent_off_dust_cleaning_claim_date' => 'date',
        'thirty_percent_off_labour_fees_claim_date' => 'date',
        'thirty_percent_off_dust_cleaning_claim_date' => 'date',
        'two_year_assembly_warranty' => 'boolean',
        'two_free_onsite_troubleshooting_first_6_months' => 'boolean',
        'two_free_onsite_troubleshooting_claim_1' => 'boolean',
        'two_free_onsite_troubleshooting_claim_2' => 'boolean',
        'two_advance_cable_management_first_year' => 'boolean',
        'two_advance_cable_management_claim_1' => 'boolean',
        'two_advance_cable_management_claim_2' => 'boolean',
        'one_free_dust_cleaning_first_year' => 'boolean',
        'one_free_dust_cleaning_claim' => 'boolean',
        'fifty_percent_off_dust_cleaning_second_year' => 'boolean',
        'thirty_percent_off_labour_fees_upgrade_first_year' => 'boolean',
        'thirty_percent_off_dust_cleaning' => 'boolean',
        'generate_code' => 'boolean',
        'rm100_promo_code_claim' => 'boolean',
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

    // Scope for filtering
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['qvse_cid'])) {
            $query->where('qvse_cid', 'like', '%' . $filters['qvse_cid'] . '%');
        }

        if (isset($filters['date_start_from'])) {
            $query->where('date_start', '>=', $filters['date_start_from']);
        }

        if (isset($filters['date_start_to'])) {
            $query->where('date_start', '<=', $filters['date_start_to']);
        }

        if (isset($filters['has_claims'])) {
            $query->where(function($q) {
                $q->where('two_free_onsite_troubleshooting_claim_1', true)
                  ->orWhere('two_free_onsite_troubleshooting_claim_2', true)
                  ->orWhere('two_advance_cable_management_claim_1', true)
                  ->orWhere('two_advance_cable_management_claim_2', true)
                  ->orWhere('one_free_dust_cleaning_claim', true);
            });
        }

        return $query;
    }
}
