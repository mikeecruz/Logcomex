<?php

namespace App\Models\feeds;

use Illuminate\Database\Eloquent\Model;
use App\Models\feeds\TypeModel;

class FeedsModel extends Model
{
    protected $table = 'pokemons';
    protected $fillable = [
        'poke_id',
        'name',
        'api_url',
        'height',
        'weight',
        'dream_world_svg',
    ];

    public function types()
    {
        return $this->hasMany(TypeModel::class, 'pokemon_id');
    }
}
