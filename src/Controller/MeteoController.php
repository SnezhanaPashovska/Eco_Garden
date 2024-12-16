<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeteoController extends AbstractController
{

    #[Route('/api/external/meteo', name: 'meteo', methods: [Request::METHOD_GET])]
    #[Route('/api/external/meteo/{city}', name: 'meteo_by_city', methods: [Request::METHOD_GET])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Vous devez être authentifié pour accéder à cette ressource')]
    public function getMeteo(HttpClientInterface $httpClient, UserInterface $user, ?string $city = null): JsonResponse
    {
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $city = $city ?: $user->getCity();

        if (!$city) {
            return new JsonResponse(['error' => 'City not available'], 400);
        }
        $cache = new FilesystemAdapter();
        $cacheKey = 'weather_' . $city;

        $cachedData = $cache->getItem($cacheKey);

        if ($cachedData->isHit()) {
            return new JsonResponse($cachedData->get(), 200);
        }

        try {
            $response = $httpClient->request(
                'GET',
                'https://api.openweathermap.org/data/2.5/weather',
                [
                    'query' => [
                        'q' => $city,
                        'appid' => $this->getParameter('open_weather_api_key'),
                        'units' => 'metric',
                        'lang' => 'fr',
                    ],
                ]
            );

            $data = $response->toArray();

            $weatherInfo = [
                'city' => $data['name'],
                'country' => $data['sys']['country'],
                'temperature' => $data['main']['temp'] . ' °C',
                'weather' => $data['weather'][0]['description'],
                'humidity' => $data['main']['humidity'] . ' %',
                'wind_speed' => ($data['wind']['speed'] * 3.6) . ' km-h',
            ];

            $cachedData->set($weatherInfo);
            $cachedData->expiresAfter(5);
            //$cachedData->expiresAfter(1800);
            $cache->save($cachedData);
            $cache->clear();

            return new JsonResponse($weatherInfo, 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
