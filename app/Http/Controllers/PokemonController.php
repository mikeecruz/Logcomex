<?php

namespace App\Http\Controllers;

use App\Models\feeds\FeedsModel;
use Illuminate\Http\Request;

class PokemonController extends Controller
{
    public function page()
    {
        return view('pokemons.index');
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $type   = $request->query('type');
        $perPage = (int) $request->query('per_page', 24);

        $query = FeedsModel::query()
            ->select(['id','poke_id','name','height','weight','dream_world_svg'])
            ->with(['types:id,pokemon_id,name'])
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($type, function ($q) use ($type) {
                $q->whereHas('types', fn($tq) => $tq->where('name', $type));
            })
            ->orderBy('poke_id');

        $page = $query->paginate($perPage);
        $page->getCollection()->transform(function ($p) {
            return [
                'poke_id' => $p->poke_id,
                'name' => $p->name,
                'height' => $p->height,
                'weight' => $p->weight,
                'dream_world_svg' => $p->dream_world_svg,
                'types' => $p->types->pluck('name')->values(),
            ];
        });

        return response()->json($page);
    }

    public function show(int $poke_id)
    {
        $p = FeedsModel::query()
            ->where('poke_id', $poke_id)
            ->with(['types:id,pokemon_id,name'])
            ->firstOrFail();

        return response()->json([
            'poke_id' => $p->poke_id,
            'name' => $p->name,
            'height' => $p->height,
            'weight' => $p->weight,
            'dream_world_svg' => $p->dream_world_svg,
            'types' => $p->types->pluck('name')->values(),
        ]);
    }
}