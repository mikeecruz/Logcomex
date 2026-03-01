<?php

namespace Tests\Feature;

use App\Models\feeds\FeedsModel;
use App\Models\feeds\TypeModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PokemonApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_pokemons_paginado(): void
    {
        $p1 = FeedsModel::create([
            'poke_id' => 1, 'name' => 'bulbasaur', 'api_url' => 'x',
            'height' => 7, 'weight' => 69, 'dream_world_svg' => 'svg1',
        ]);

        $p2 = FeedsModel::create([
            'poke_id' => 4, 'name' => 'charmander', 'api_url' => 'x',
            'height' => 6, 'weight' => 85, 'dream_world_svg' => 'svg4',
        ]);

        $p3 = FeedsModel::create([
            'poke_id' => 7, 'name' => 'squirtle', 'api_url' => 'x',
            'height' => 5, 'weight' => 90, 'dream_world_svg' => 'svg7',
        ]);

        TypeModel::create(['pokemon_id' => $p1->id, 'name' => 'grass']);
        TypeModel::create(['pokemon_id' => $p1->id, 'name' => 'poison']);
        TypeModel::create(['pokemon_id' => $p2->id, 'name' => 'fire']);
        TypeModel::create(['pokemon_id' => $p3->id, 'name' => 'water']);

        $res = $this->getJson('/api/pokemons?per_page=2');

        $res->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['poke_id', 'name', 'height', 'weight', 'dream_world_svg', 'types']
                ],
                'current_page',
                'last_page',
                'total',
            ]);
        $this->assertEquals(2, count($res->json('data')));
    }

    public function test_filtra_por_tipo(): void
    {
        $bulba = FeedsModel::create([
            'poke_id' => 1, 'name' => 'bulbasaur', 'api_url' => 'x',
            'height' => 7, 'weight' => 69, 'dream_world_svg' => 'svg1',
        ]);
        $char = FeedsModel::create([
            'poke_id' => 4, 'name' => 'charmander', 'api_url' => 'x',
            'height' => 6, 'weight' => 85, 'dream_world_svg' => 'svg4',
        ]);

        TypeModel::create(['pokemon_id' => $bulba->id, 'name' => 'grass']);
        TypeModel::create(['pokemon_id' => $char->id, 'name' => 'fire']);

        $res = $this->getJson('/api/pokemons?type=grass');

        $res->assertOk();
        $data = $res->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals(1, $data[0]['poke_id']);
        $this->assertEquals(['grass'], $data[0]['types']);
    }
}