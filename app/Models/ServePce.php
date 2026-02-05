<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServePce extends Model
{
    use SoftDeletes;

    protected $table = 'serve_pce';

    protected $fillable = [
        'serve_data_id',
        'date_start',
        'three_year_warranty',
        'unlimited_troubleshooting',
        'troubleshooting',
        'cable_management',
        'cable_management_claim1',
        'cable_management_claim1_date',
        'cable_management_claim2',
        'cable_management_claim2_date',
        'cable_management_claim3',
        'cable_management_claim3_date',
        'cable_management_claim4',
        'cable_management_claim4_date',
        'annual_dust_cleaning',
        'annual_dust_cleaning_year1',
        'claim_date_year1',
        'annual_dust_cleaning_year2',
        'claim_date_year2',
        'annual_dust_cleaning_year3',
        'claim_date_year3',
        '50_dust_cleaning',
        '50_upgrade_service',
        '30_upgrade_service',
        'promo_code',
        'generate_code',
        'promo_claim'
    ];

    protected $casts = [
        'date_start' => 'date',
        'cable_management_claim1_date' => 'date',
        'cable_management_claim2_date' => 'date',
        'cable_management_claim3_date' => 'date',
        'cable_management_claim4_date' => 'date',
        'claim_date_year1' => 'date',
        'claim_date_year2' => 'date',
        'claim_date_year3' => 'date',
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
}
