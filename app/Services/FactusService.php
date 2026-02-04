<?php
// app/Services/FactusService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FactusService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.factus.base_url');
    }

    /**
     * Obtener token de acceso
     */
    public function getAccessToken(): string
    {
        return Cache::remember('factus_access_token', 55 * 60, function () {
            $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
                'grant_type' => 'password',
                'client_id' => config('services.factus.client_id'),
                'client_secret' => config('services.factus.client_secret'),
                'username' => config('services.factus.user'),
                'password' => config('services.factus.password'),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Factus auth failed: ' . $response->body());
            }

            return $response->json()['access_token'];
        });
    }

    /**
     * Cliente HTTP autenticado
     */
    private function httpClient()
    {
        return Http::withToken($this->getAccessToken())
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Crear factura electrónica (VALIDACIÓN + CREACIÓN)
     */
    public function crearFacturaElectronica(array $datosVenta)
    {
        // 1. Construir payload
        $payload = $this->construirPayloadFactura($datosVenta);

        Log::info('Creando factura electrónica', [
            'cliente' => $payload['customer']['names'],
            'total_items' => count($payload['items'])
        ]);

        // 2. Validar y crear (el endpoint /v1/bills/validate CREA directamente)
        $response = $this->httpClient()->post('/v1/bills/validate', $payload);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'message' => 'Factura creada exitosamente',
                'factura' => [
                    'id' => $data['data']['bill']['id'] ?? null,
                    'numero' => $data['data']['bill']['number'] ?? null,
                    'cufe' => $data['data']['bill']['cufe'] ?? null,
                    'qr' => $data['data']['bill']['qr'] ?? null,
                    'total' => $data['data']['bill']['total'] ?? null,
                    'pdf_url' => $this->generarUrlPdf($data['data']['bill']['id'] ?? null),
                    'public_url' => $data['data']['bill']['public_url'] ?? null,
                ],
                'respuesta_completa' => $data
            ];
        }

        return [
            'success' => false,
            'status' => $response->status(),
            'error' => $response->body(),
            'errors' => $response->json()['data']['errors'] ?? null
        ];
    }

    /**
     * Construir payload para factura
     */
    private function construirPayloadFactura(array $datosVenta): array
    {
        // Convertir datos de tu sistema a formato Factus
        return [
            'document' => '01', // Factura electrónica
            'numbering_range_id' => 8, // ID para Factura de Venta (SETP)
            'reference_code' => $this->generarCodigoReferencia($datosVenta),
            'observation' => $datosVenta['observacion'] ?? '',
            'payment_method_code' => '10', // Contado

            'establishment' => [
                'name' => config('services.factus.company_name', 'Mi Empresa'),
                'address' => config('services.factus.company_address', 'Dirección'),
                'phone_number' => config('services.factus.company_phone', '3001234567'),
                'email' => config('services.factus.company_email', 'empresa@correo.com'),
                'municipality_id' => (int) config('services.factus.municipality_id', 980)
            ],

            'customer' => [
                'identification' => $datosVenta['cliente']['identificacion'],
                'dv' => $this->calcularDigitoVerificacion($datosVenta['cliente']['identificacion']),
                'company' => '',
                'trade_name' => '',
                'names' => $datosVenta['cliente']['nombres'],
                'address' => $datosVenta['cliente']['direccion'] ?? 'No especificada',
                'email' => $datosVenta['cliente']['email'] ?? 'cliente@correo.com',
                'phone' => $datosVenta['cliente']['telefono'] ?? '3000000000',
                'legal_organization_id' => '2',
                'tribute_id' => '21',
                'identification_document_id' => $this->determinarTipoDocumento($datosVenta['cliente']['identificacion']),
                'municipality_id' => (string) config('services.factus.municipality_id', '980')
            ],

            'items' => array_map(function ($item) {
                return [
                    'code_reference' => $item['codigo'] ?? 'PROD' . ($item['producto_id'] ?? '001'),
                    'name' => $item['descripcion'] ?? 'Producto',
                    'quantity' => (float) $item['cantidad'],
                    'discount_rate' => (float) ($item['descuento_porcentaje'] ?? 0),
                    'price' => (float) $item['precio_unitario'],
                    'tax_rate' => $this->determinarTarifaIVA($item),
                    'unit_measure_id' => 70, // Unidad
                    'standard_code_id' => 1,
                    'is_excluded' => 0,
                    'tribute_id' => 1,
                    'withholding_taxes' => $this->determinarRetenciones($item)
                ];
            }, $datosVenta['items']),

            // IMPORTANTE: Si no hay cargos adicionales, enviar array vacío
            'allowance_charges' => $datosVenta['allowance_charges'] ?? []
        ];
    }

    /**
     * Generar código de referencia único
     */
    private function generarCodigoReferencia(array $datosVenta): string
    {
        return 'FAC-' . date('Ymd') . '-' .
               ($datosVenta['numero_venta'] ?? str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Generar URL para PDF
     */
    private function generarUrlPdf(?int $facturaId): ?string
    {
        if (!$facturaId) {
            return null;
        }

        return $this->baseUrl . '/v1/bills/' . $facturaId . '/pdf';
    }

    /**
     * Calcular dígito de verificación (Colombia)
     */
    private function calcularDigitoVerificacion(string $nit): string
    {
        if (!is_numeric($nit) || strlen($nit) < 6) {
            return '0';
        }

        $sum = 0;
        $factors = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43];

        for ($i = 0; $i < strlen($nit); $i++) {
            $digit = (int) $nit[strlen($nit) - $i - 1];
            $sum += $digit * ($factors[$i] ?? 1);
        }

        $remainder = $sum % 11;
        return $remainder > 1 ? (string) (11 - $remainder) : '0';
    }

    /**
     * Determinar tipo de documento
     */
    private function determinarTipoDocumento(string $identificacion): int
    {
        if (strlen($identificacion) === 10 && is_numeric($identificacion)) {
            return 1; // NIT
        }

        if (strlen($identificacion) >= 6 && strlen($identificacion) <= 10 && is_numeric($identificacion)) {
            return 3; // Cédula de ciudadanía
        }

        return 5; // Documento extranjero
    }

    /**
     * Determinar tarifa de IVA
     */
    private function determinarTarifaIVA(array $item): string
    {
        // Lógica para determinar IVA según producto
        $tarifas = [
            '19.00', // General
            '5.00',  // Reducida
            '0.00'   // Exento
        ];

        return number_format($item['iva_porcentaje'] ?? 19.00, 2, '.', '');
    }

    /**
     * Determinar retenciones
     */
    private function determinarRetenciones(array $item): array
    {
        // Solo agregar retenciones si el precio es significativo
        if (($item['precio_unitario'] ?? 0) > 100000) {
            return [
                [
                    'code' => '06',
                    'withholding_tax_rate' => 7.38
                ],
                [
                    'code' => '05',
                    'withholding_tax_rate' => 15.12
                ]
            ];
        }

        return [];
    }

    /**
     * Obtener PDF de factura
     */
    public function obtenerPdfFactura(int $facturaId)
    {
        try {
            $response = $this->httpClient()
                ->get('/v1/bills/' . $facturaId . '/pdf', [
                    'Accept' => 'application/pdf'
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'pdf_content' => $response->body(),
                    'content_type' => 'application/pdf'
                ];
            }

            return ['success' => false, 'error' => 'No se pudo obtener PDF'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
 * Método para debug - verificar qué se está enviando
 */
    public function debugEnvio(array $datosVenta)
    {
        $payload = $this->construirPayloadFactura($datosVenta);

        // Log detallado
        Log::info('Payload completo:', $payload);
        Log::info('allowance_charges:', $payload['allowance_charges'] ?? []);
        Log::info('=== DEBUG FACTUS SERVICE ===');
        Log::info('allowance_charges count:', count($payload['allowance_charges'] ?? []));

        // Verificar estructura de allowance_charges
        if (!empty($payload['allowance_charges'])) {
            foreach ($payload['allowance_charges'] as $i => $item) {
                Log::info("Item {$i}:", [
                    'concept_type' => $item['concept_type'] ?? 'NO DEFINIDO',
                    'is_surcharge' => isset($item['is_surcharge']) ? ($item['is_surcharge'] ? 'true' : 'false') : 'NO DEFINIDO',
                    'reason' => $item['reason'] ?? 'NO DEFINIDO',
                    'base_amount' => $item['base_amount'] ?? 'NO DEFINIDO',
                    'amount' => $item['amount'] ?? 'NO DEFINIDO'
                ]);
            }
        }

        return $payload;
    }
}
