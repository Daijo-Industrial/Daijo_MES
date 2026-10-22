<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SapForecast extends Model
{
    use HasFactory;

    protected $table = 'sap_forecast';

    public $timestamps = false;

    // public $incrementing = false;
    protected $primaryKey = null;
}
