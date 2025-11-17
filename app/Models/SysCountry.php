<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SysCountry extends Model
{
    use HasFactory;

    protected $table = 'sysCountry';

    protected $fillable = [
        'shortName', 
        'longName', 
        'isoAlpha2', 
        'isoAlpha3', 
        'isoNumeric', 
        'currencyCode', 
        'currencyRateUSD', 
        'callingCode', 
        'languages', 
        'geonameId', 
        'memberUN', 
        'cctld', 
        'tagHq', 
        'tagMarket', 
        'tagLocation', 
        'isDeleted'
    ];

    protected $casts = [
        'id' => 'integer', 
        'currencyRateUSD' => 'float', 
        'geonameId' => 'integer', 
        'tagHq' => 'integer', 
        'tagMarket' => 'integer', 
        'tagLocation' => 'integer', 
        'isDeleted' => 'boolean',
        'created_at' => 'datetime', 
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function orgs()
    {
        return $this->hasMany(Org::class, 'sysCpuntry_id');
    }
}