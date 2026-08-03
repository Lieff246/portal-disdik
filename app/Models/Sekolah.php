<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sekolah extends Model
{
    protected $table = 'sekolah';
    protected $primaryKey = ['sekolah_id', 'semester_id'];
    public $incrementing = false;
    protected $guarded = [];
    
    public function scopeByJenjang($query, $jenjang) {
        return $query->where('bentuk_pendidikan', $jenjang);
    }
    
    public function scopeByKabupaten($query, $kodeKab) {
        return $query->where('kode_kabupaten', $kodeKab);
    }
    
    public function scopeWilayah3T($query) {
        return $query->where(function($q) {
            $q->where('is_3t', true)
              ->orWhere('wilayah_terpencil', '1')
              ->orWhere('wilayah_perbatasan', '1')
              ->orWhere('wilayah_transmigrasi', '1');
        });
    }
    
    public function detailSma() {
        return $this->hasOne(SchoolSma::class, 'npsn', 'npsn');
    }
}
