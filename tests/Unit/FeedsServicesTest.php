<?php

namespace Tests\Unit;

use App\Models\feeds\FeedsModel;
use App\Models\feeds\TypeModel;
use App\Services\FeedsServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FeedsServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_e_persiste_pokemon_com_tipos(): void
    {
        Http::fake([
            'https://pokeapi.co/api/v2/pokemon/1/' => Http::response([
                'id' => 1,
                'name' => 'bulbasaur',
                'height' => 7,
                'weight' => 69,
                'sprites' => [
                    'other' => [
                        'dream_world' => [
                            'front_default' => 'https://example.com/1.svg',
                        ],
                    ],
                ],
                'types' => [
                    ['type' => ['name' => 'grass']],
                    ['type' => ['name' => 'poison']],
                ],
            ], 200),

            'https://pokeapi.co/api/v2/pokemon*' => Http::response([
                'count' => 1,
                'next' => null,
                'previous' => null,
                'results' => [
                    ['name' => 'bulbasaur', 'url' => 'https://pokeapi.co/api/v2/pokemon/1/'],
                ],
            ], 200),
        ]);

        $service = new FeedsServices();
        $result = $service->updateFromApi(limit: 20, sleepMs: 0);

        $this->assertEquals(1, $result['processados']);

        $pokemon = FeedsModel::where('poke_id', 1)->first();
        $this->assertNotNull($pokemon);
        $this->assertEquals('bulbasaur', $pokemon->name);
        $this->assertEquals(7, $pokemon->height);
        $this->assertEquals(69, $pokemon->weight);
        $this->assertEquals('https://example.com/1.svg', $pokemon->dream_world_svg);

        $types = TypeModel::where('pokemon_id', $pokemon->id)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertEquals(['grass', 'poison'], $types);
        Http::assertSentCount(2);
        Http::assertSent(fn ($req) => str_starts_with($req->url(), 'https://pokeapi.co/api/v2/pokemon'));
    }
}