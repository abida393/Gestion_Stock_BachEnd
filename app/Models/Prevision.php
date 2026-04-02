<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prevision extends Model
{
    use HasFactory;

    protected $table = 'previsions';

    protected $fillable = [
        'produit_id',
        'periode',
        'quantite_predite',
        'confiance',
        'eoq',
        'score_anomalie',
    ];

    protected $casts = [
        'quantite_predite' => 'float',
        'confiance' => 'float',
        'eoq' => 'float',
        'score_anomalie' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the produit for this prevision
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}
