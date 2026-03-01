<?php

namespace App\Services;

use App\Models\feeds\FeedsModel;
use App\Models\feeds\TypeModel;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FeedsServices
{
    private const BASE_URL = 'https://pokeapi.co/api/v2/pokemon';

    public function updateFromApi(int $limit = 200, int $sleepMs = 0, int $concurrency = 10): array
    {
        try {

            $offset = 0;
            $first = $this->fetchPage($offset, $limit);
            $totalApi = (int) ($first['count'] ?? 0);

            $processados = 0;
            $tipos_gravados = 0;

            while ($offset < $totalApi) {
                $page = ($offset === 0) ? $first : $this->fetchPage($offset, $limit);

                $baseRows = collect($page['results'] ?? [])
                    ->map(function (array $item) {
                        $url = (string) ($item['url'] ?? '');
                        $name = (string) ($item['name'] ?? '');

                        $pokeId = $this->extractIdFromUrl($url);

                        return [
                            'poke_id' => $pokeId,
                            'name'    => $name,
                            'api_url' => $url,
                        ];
                    })
                    ->filter(fn ($r) => !empty($r['poke_id']) && !empty($r['name']) && !empty($r['api_url']))
                    ->values();

                if ($baseRows->isEmpty()) {
                    $offset += $limit;
                    continue;
                }

                $detailsMap = $this->fetchDetailsForRows($baseRows);
                $rowsToUpsert = $baseRows->map(function ($r) use ($detailsMap) {
                    $d = $detailsMap[$r['poke_id']] ?? [];

                    return [
                        ...$r,
                        'height' => $d['height'] ?? null,
                        'weight' => $d['weight'] ?? null,
                        'dream_world_svg' => $d['dream_world_svg'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                });

                $countTiposDaPagina = DB::transaction(function () use ($rowsToUpsert, $detailsMap) {
                    FeedsModel::upsert(
                        $rowsToUpsert->all(),
                        ['poke_id'],
                        ['name', 'api_url', 'height', 'weight', 'dream_world_svg', 'updated_at']
                    );

                    $pokeIds = $rowsToUpsert->pluck('poke_id')->all();

                    $pokemons = FeedsModel::query()
                        ->whereIn('poke_id', $pokeIds)
                        ->get(['id', 'poke_id'])
                        ->keyBy('poke_id');

                    $tiposInseridos = 0;

                    foreach ($pokeIds as $pokeId) {
                        $pokemon = $pokemons->get($pokeId);
                        if (!$pokemon) {
                            continue;
                        }

                        $types = $detailsMap[$pokeId]['types'] ?? [];
                        TypeModel::query()->where('pokemon_id', $pokemon->id)->delete();

                        foreach ($types as $t) {
                            if (empty($t['name'])) {
                                continue;
                            }

                            TypeModel::create([
                                'pokemon_id' => $pokemon->id,
                                'name'       => $t['name'],
                            ]);

                            $tiposInseridos++;
                        }
                    }

                    return $tiposInseridos;
                });

                $processados += $rowsToUpsert->count();
                $tipos_gravados += $countTiposDaPagina;

                $offset += $limit;

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }

            return [
                'total_api'      => $totalApi,
                'processados'    => $processados,
                'tipos_gravados' => $tipos_gravados,
                'limit'          => $limit,
            ];
        } catch (\Exception $e) {
            throw $e->getMessage();
        }
    }

    private function fetchPage(int $offset, int $limit): array
    {
        $res = Http::timeout(30)->retry(3, 500)->get(self::BASE_URL, [
            'offset' => $offset,
            'limit'  => $limit,
        ]);

        $res->throw();
        return $res->json();
    }

    private function fetchDetailsForRows($rows): array
    {
        $urls = $rows->pluck('api_url')->all();
        $ids  = $rows->pluck('poke_id')->all();

        $responses = Http::timeout(30)
            ->retry(2, 300)
            ->pool(function (Pool $pool) use ($urls) {
                foreach ($urls as $url) {
                    $pool->get($url);
                }
            });

        $map = [];

        foreach ($responses as $i => $response) {
            $pokeId = $ids[$i] ?? null;
            if (!$pokeId) {
                continue;
            }

            if (!$response->successful()) {
                $map[$pokeId] = [];
                continue;
            }
            $json = $response->json();
            $types = collect($json['types'] ?? [])
                ->map(fn ($t) => [
                    'name' => data_get($t, 'type.name'),
                ])
                ->filter(fn ($t) => !empty($t['name']))
                ->values()
                ->all();

            $map[$pokeId] = [
                'height' => $json['height'] ?? null,
                'weight' => $json['weight'] ?? null,
                'dream_world_svg' => data_get($json, 'sprites.other.dream_world.front_default'),
                'types' => $types,
            ];
        }

        return $map;
    }

    private function extractIdFromUrl(string $url): ?int
    {
        $trimmed = rtrim($url, '/');
        $id = Str::afterLast($trimmed, '/');
        return ctype_digit($id) ? (int) $id : null;
    }
}