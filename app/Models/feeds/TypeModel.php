<?php

namespace App\Models\feeds;

use Illuminate\Database\Eloquent\Model;
use App\Models\feeds\FeedsModel;

class TypeModel extends Model
{
    protected $table = 'types';
    protected $fillable = [
        'pokemon_id',
        'name',
    ];

    public function pokemon()
    {
        return $this->belongsTo(FeedsModel::class, 'pokemon_id');
    }
}