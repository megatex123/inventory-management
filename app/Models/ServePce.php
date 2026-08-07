<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServePce extends Model
{
    use SoftDeletes;

    protected $table = 'serve_pce';

    protected $fillable = [
        'serve_pce_id',
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
        'dust_cleaning_50_description',
        'dust_cleaning_50_year4',
        'dust_cleaning_50_claim_date_year4',
        'dust_cleaning_50_year5',
        'dust_cleaning_50_claim_date_year5',
        'dust_cleaning_50_year6',
        'dust_cleaning_50_claim_date_year6',
        'dust_cleaning_50_year7',
        'dust_cleaning_50_claim_date_year7',
        'dust_cleaning_50_year8',
        'dust_cleaning_50_claim_date_year8',
        'dust_cleaning_50_year9',
        'dust_cleaning_50_claim_date_year9',
        'dust_cleaning_50_year10',
        'dust_cleaning_50_claim_date_year10',
        'upgrade_service_50_description',
        'upgrade_service_50_year1',
        'upgrade_service_50_claim_date_year1',
        'upgrade_service_50_year2',
        'upgrade_service_50_claim_date_year2',
        'upgrade_service_50_year3',
        'upgrade_service_50_claim_date_year3',
        'upgrade_service_30_description',
        'upgrade_service_30_year4',
        'upgrade_service_30_claim_date_year4',
        'upgrade_service_30_year5',
        'upgrade_service_30_claim_date_year5',
        'upgrade_service_30_year6',
        'upgrade_service_30_claim_date_year6',
        'upgrade_service_30_year7',
        'upgrade_service_30_claim_date_year7',
        'upgrade_service_30_year8',
        'upgrade_service_30_claim_date_year8',
        'upgrade_service_30_year9',
        'upgrade_service_30_claim_date_year9',
        'upgrade_service_30_year10',
        'upgrade_service_30_claim_date_year10',
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
        'dust_cleaning_50_claim_date_year4' => 'date',
        'dust_cleaning_50_claim_date_year5' => 'date',
        'dust_cleaning_50_claim_date_year6' => 'date',
        'dust_cleaning_50_claim_date_year7' => 'date',
        'dust_cleaning_50_claim_date_year8' => 'date',
        'dust_cleaning_50_claim_date_year9' => 'date',
        'dust_cleaning_50_claim_date_year10' => 'date',
        'upgrade_service_50_claim_date_year1' => 'date',
        'upgrade_service_50_claim_date_year2' => 'date',
        'upgrade_service_50_claim_date_year3' => 'date',
        'upgrade_service_30_claim_date_year4' => 'date',
        'upgrade_service_30_claim_date_year5' => 'date',
        'upgrade_service_30_claim_date_year6' => 'date',
        'upgrade_service_30_claim_date_year7' => 'date',
        'upgrade_service_30_claim_date_year8' => 'date',
        'upgrade_service_30_claim_date_year9' => 'date',
        'upgrade_service_30_claim_date_year10' => 'date',
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
