<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeteoController extends AbstractController
{

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENWEATHER_API_KEY'];
    }

    #[Route('/api/external/meteo', name: 'meteo', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Vous devez être authentifié pour accéder à cette ressource')]
    public function getMeteo(HttpClientInterface $httpClient, UserInterface $user): JsonResponse
    {
        // Get the authenticated user's city
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $city = $user->getCity();

        if (!$city) {
            return new JsonResponse(['error' => 'User city not available'], 400);
        }

        $cache = new FilesystemAdapter();
        $cacheKey = 'weather_' . $city;

        $cachedData = $cache->getItem($cacheKey);

        // Check if the data is already cached and return it if present
        if ($cachedData->isHit()) {
            return new JsonResponse($cachedData->get(), 200);
        }

        try {
            // Make the API request
            $response = $httpClient->request(
                'GET',
                'https://api.openweathermap.org/data/2.5/weather',
                [
                    'query' => [
                        'q' => $city,
                        'appid' => $this->apiKey,
                        'units' => 'metric',
                        'lang' => 'fr',
                    ],
                ]
            );


            // Convert the response to an array
            $data = $response->toArray();
            //dump($data);

            // Simplify the weather information
            $weatherInfo = [
                'city' => $data['name'],
                'country' => $data['sys']['country'],
                'temperature' => $data['main']['temp'],
                'weather' => $data['weather'][0]['description'],
                'humidity' => $data['main']['humidity'],
                'wind_speed' => $data['wind']['speed'],
            ];

            // Cache the simplified weather data for 30 minutes
            $cachedData->set($weatherInfo);
            $cachedData->expiresAfter(30 * 60); // 30 minutes
            $cache->save($cachedData);

            // Return the simplified weather data as JSON
            return new JsonResponse($weatherInfo, 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
