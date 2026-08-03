<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class CabangDinas extends Model
{
    protected $table = 'cabang_dinas';
    protected $guarded = [];
    
    protected function casts(): array
    {
        return [
            'kode_kabupaten' => 'array',
            'kabupaten_kota' => 'array',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
