<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FactusService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.factus.base_url');
    }

    /*
    |--------------------------------------------------------------------------
    | Obtener Token (se guarda en cache 55 min)
    |--------------------------------------------------------------------------
    */
    public function getToken(): string
    {
        return Cache::remember('factus_token', 3300, function () {

            $response = Http::asForm()->post(
                $this->baseUrl . '/oauth/token',
                [
                    'grant_type' => 'password',
                    'client_id' => config('services.factus.client_id'),
                    'client_secret' => config('services.factus.client_secret'),
                    'username' => config('services.factus.user'),
                    'password' => config('services.factus.password'),
                ]
            );

            return $response->json()['access_token'];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Cliente autenticado
    |--------------------------------------------------------------------------
    */
    protected function client()
    {
        return Http::withToken($this->getToken())
            ->baseUrl($this->baseUrl)
            ->acceptJson();
    }

    /*
    |--------------------------------------------------------------------------
    | Crear factura / venta
    |--------------------------------------------------------------------------
    */
    public function crearFactura(array $data)
    {
        return $this->client()
            ->post('/v1/bills', $data)
            ->json();
    }
}
