<?php

namespace App\Service;

use App\DTO\NearbyStop;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PublicTransportService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.mobiliteit_api_key%')]
        private readonly string $apiKey,
        #[Autowire('%app.mobiliteit_radius%')]
        private readonly int $radius,
        #[Autowire('%app.mobiliteit_max_stops%')]
        private readonly int $maxStops,
    ) {
    }

    /**
     * @return NearbyStop[]
     */
    public function findNearbyStops(string $lat, string $lng): array
    {
        if ($this->apiKey === '') {
            return [];
        }

        $cacheKey = 'nearby_stops_'.md5(round((float) $lat, 4).'_'.round((float) $lng, 4));

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($lat, $lng): array {
                $item->expiresAfter(86400);

                $response = $this->httpClient->request('GET', 'https://cdt.hafas.de/opendata/apiserver/location.nearbystops', [
                    'query' => [
                        'accessId' => $this->apiKey,
                        'originCoordLat' => $lat,
                        'originCoordLong' => $lng,
                        'r' => $this->radius,
                        'maxNo' => 20,
                        'format' => 'json',
                    ],
                ]);

                $data = $response->toArray();

                return $this->parseResponse($data);
            });
        } catch (\Throwable $e) {
            $this->logger->error('HAFAS API error: {message}', ['message' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return NearbyStop[]
     */
    private function parseResponse(array $data): array
    {
        $stopLocationOrCoordLocation = $data['stopLocationOrCoordLocation'] ?? [];

        $stops = [];
        $seen = [];

        foreach ($stopLocationOrCoordLocation as $entry) {
            $stop = $entry['StopLocation'] ?? null;
            if ($stop === null) {
                continue;
            }

            $name = $stop['name'] ?? '';
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;

            $distance = (int) ($stop['dist'] ?? 0);
            $products = (int) ($stop['products'] ?? 0);
            $lines = $this->extractLines($stop);
            $type = $this->determineType($products);

            $stops[] = new NearbyStop(
                name: $name,
                distance: $distance,
                lines: $lines,
                type: $type,
            );
        }

        usort($stops, static fn (NearbyStop $a, NearbyStop $b) => $a->distance <=> $b->distance);

        return array_slice($stops, 0, $this->maxStops);
    }

    /**
     * @return string[]
     */
    private function extractLines(array $stop): array
    {
        $lines = [];

        foreach ($stop['productAtStop'] ?? [] as $product) {
            $line = $product['name'] ?? '';
            if ($line !== '' && !in_array($line, $lines, true)) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function determineType(int $products): string
    {
        $hasTram = ($products & 4) !== 0;
        $hasBus = ($products & 32) !== 0 || ($products & 64) !== 0;

        if ($hasTram && $hasBus) {
            return 'mixed';
        }
        if ($hasTram) {
            return 'tram';
        }

        return 'bus';
    }
}
