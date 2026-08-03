<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSma extends Model
{
    protected $table = 'school_sma';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $keyType = 'string';
    protected $guarded = [];
    
    public function sekolah() {
        return $this->belongsTo(Sekolah::class, 'npsn', 'npsn');
    }
}
