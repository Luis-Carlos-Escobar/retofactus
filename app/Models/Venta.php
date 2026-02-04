<?php

namespace App\Models;

use App\Services\FactusService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'fecha_venta', 'total', 'pagado', 'restante',
        'factus_id', 'factus_cufe', 'estado_dian', 'factus_pdf_url',
        'facturada', 'factus_numero', 'factus_qr', 'factus_public_url',
        'factus_respuesta', 'factus_error', 'factus_fecha_generacion',
    ];

    protected $casts = [
        'facturada' => 'boolean',
        'fecha_venta' => 'datetime',
        'factus_fecha_generacion' => 'datetime',
        'factus_respuesta' => 'array',
        'total' => 'decimal:2', 'pagado' => 'decimal:2', 'restante' => 'decimal:2',
    ];

    // Relaciones
    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function detalles() { return $this->hasMany(DetalleVenta::class); }
    public function productos() {
        return $this->belongsToMany(Producto::class, 'detalle_ventas')
            ->withPivot('cantidad', 'precio_unitario', 'subtotal');
    }

    /**
     * Generar factura electrónica - BASADO EN DOCUMENTACIÓN FACTUS
     */
    public function generarFacturaElectronica()
    {
        $factus = new FactusService();

        $datosFactus = [
            'numero_venta' => $this->id,
            'observacion' => 'Venta #' . $this->id,

            'cliente' => [
                'identificacion' => $this->cliente->numero_documento ?? '123456789',
                'nombres' => $this->cliente->nombre ?? 'CLIENTE GENERAL',
                'direccion' => $this->cliente->direccion ?? 'DIRECCIÓN NO ESPECIFICADA',
                'email' => $this->cliente->email ?? 'cliente@correo.com',
                'telefono' => $this->cliente->telefono ?? '3000000000'
            ],

            'items' => $this->detalles->map(function ($detalle) {
                return [
                    'codigo' => $detalle->producto->codigo ?? 'PROD' . $detalle->producto_id,
                    'descripcion' => $detalle->producto->nombre ?? 'Producto ' . $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'iva_porcentaje' => 19.00,
                    'descuento_porcentaje' => 0
                ];
            })->toArray(),

            // ¡¡¡ESTRUCTURA EXACTA DE LA DOCUMENTACIÓN FACTUS!!!
            'allowance_charges' => $this->getAllowanceChargesFactus()
        ];

        Log::info('Enviando factura según documentación Factus:', $datosFactus);
        $resultado = $factus->crearFacturaElectronica($datosFactus);

        $this->procesarResultadoFacturacion($resultado);
        return $resultado;
    }

    /**
     * Obtener allowance_charges según documentación Factus
     * Basado en: {"concept_type":"03","is_surcharge":true,"reason":"Propina","base_amount":"90000.00","amount":"9000.00"}
     */
    private function getAllowanceChargesFactus()
    {
        // Siempre enviar al menos un elemento con la estructura EXACTA de Factus
        return [
            [
                'concept_type' => '03', // Tipo de concepto
                'is_surcharge' => true, // true=recargo, false=descuento
                'reason' => 'Propina', // Razón del cargo/descuento
                'base_amount' => (string) number_format($this->total, 2, '.', ''), // Monto base
                'amount' => '1000.00' // Monto del cargo/descuento (siempre > 0)
            ]
        ];
    }

    /**
     * Versión con datos reales de la venta
     */
    private function getAllowanceChargesReal()
    {
        // Si hay restante, usarlo; si no, usar un valor mínimo
        $monto = $this->restante != 0 ? abs($this->restante) : 1000;
        $esRecargo = $this->restante < 0;

        return [
            [
                'concept_type' => $esRecargo ? '01' : '02', // 01=recargo, 02=descuento, 03=otro
                'is_surcharge' => $esRecargo, // true=recargo, false=descuento
                'reason' => $esRecargo ? 'Recargo' : 'Descuento',
                'base_amount' => (string) number_format($this->total, 2, '.', ''),
                'amount' => (string) number_format($monto, 2, '.', '')
            ]
        ];
    }

    /**
     * Método de PRUEBA con payload EXACTO de la documentación
     */
    public function probarPayloadExacto()
    {
        $factus = new FactusService();

        // Payload EXACTO de la documentación Factus
        $payloadExacto = [
            'numero_venta' => 'TEST-' . time(),
            'observacion' => 'Prueba payload exacto Factus',

            'cliente' => [
                'identificacion' => '123456789',
                'nombres' => 'Luis',
                'direccion' => 'av los estudiantes',
                'email' => 'cliente@correo.com',
                'telefono' => '3245640874'
            ],

            'items' => [
                [
                    'codigo' => 'PROD1',
                    'descripcion' => 'Producto 1',
                    'cantidad' => 1,
                    'precio_unitario' => 120000,
                    'iva_porcentaje' => 19.00,
                    'descuento_porcentaje' => 0
                ]
            ],

            // ¡¡¡EXACTAMENTE COMO LA DOCUMENTACIÓN FACTUS!!!
            'allowance_charges' => [
                [
                    'concept_type' => '03',
                    'is_surcharge' => true,
                    'reason' => 'Propina',
                    'base_amount' => '120000.00',
                    'amount' => '5000.00'
                ]
            ]
        ];

        Log::info('=== PRUEBA PAYLOAD EXACTO FACTUS ===', $payloadExacto);

        // 1. Ver qué payload construye FactusService
        $payloadConstruido = $this->debugFactusPayload($payloadExacto);

        // 2. Intentar crear factura
        $resultado = $factus->crearFacturaElectronica($payloadExacto);

        Log::info('Resultado prueba exacta:', $resultado);

        return [
            'payload_enviado' => $payloadExacto,
            'payload_construido' => $payloadConstruido,
            'resultado' => $resultado
        ];
    }

    /**
     * Debug: Ver qué payload construye FactusService
     */
    private function debugFactusPayload(array $datosVenta)
    {
        $factus = new FactusService();

        // Usar reflexión para acceder al método privado
        $reflection = new \ReflectionClass($factus);
        $method = $reflection->getMethod('construirPayloadFactura');
        $method->setAccessible(true);

        $payload = $method->invoke($factus, $datosVenta);

        Log::info('=== PAYLOAD CONSTRUIDO POR FACTUS SERVICE ===', $payload);

        // Verificar allowance_charges específicamente
        if (isset($payload['allowance_charges'])) {
            Log::info('allowance_charges en payload:', $payload['allowance_charges']);
            Log::info('Número de elementos en allowance_charges:', count($payload['allowance_charges']));

            foreach ($payload['allowance_charges'] as $i => $item) {
                Log::info("Elemento {$i}:", [
                    'concept_type' => $item['concept_type'] ?? 'NO',
                    'is_surcharge' => isset($item['is_surcharge']) ? ($item['is_surcharge'] ? 'true' : 'false') : 'NO',
                    'reason' => $item['reason'] ?? 'NO',
                    'base_amount' => $item['base_amount'] ?? 'NO',
                    'amount' => $item['amount'] ?? 'NO'
                ]);
            }
        }

        return $payload;
    }

    /**
     * Procesar resultado de la facturación
     */
    private function procesarResultadoFacturacion($resultado)
    {
        $updateData = [
            'facturada' => $resultado['success'] ?? false,
            'factus_respuesta' => $resultado,
            'factus_error' => $resultado['success'] ? null : ($resultado['error'] ?? 'Error desconocido'),
            'factus_fecha_generacion' => now(),
            'estado_dian' => $resultado['success'] ? 'ACEPTADA' : 'RECHAZADA',
        ];

        if ($resultado['success'] ?? false) {
            $facturaData = $resultado['factura'] ?? [];

            $updateData = array_merge($updateData, [
                'factus_id' => $facturaData['id'] ?? null,
                'factus_numero' => $facturaData['numero'] ?? null,
                'factus_cufe' => $facturaData['cufe'] ?? null,
                'factus_pdf_url' => $facturaData['pdf_url'] ?? null,
                'factus_qr' => $facturaData['qr'] ?? null,
                'factus_public_url' => $facturaData['public_url'] ?? null,
            ]);
        }

        $this->update($updateData);

        Log::info('Venta actualizada después de facturación:', [
            'id' => $this->id,
            'facturada' => $updateData['facturada'],
            'estado_dian' => $updateData['estado_dian']
        ]);
    }

    // Accessors para compatibilidad
    public function getCufeAttribute() { return $this->attributes['factus_cufe'] ?? null; }
    public function setCufeAttribute($value) { $this->attributes['factus_cufe'] = $value; }
    public function getPdfUrlAttribute() { return $this->attributes['factus_pdf_url'] ?? null; }
    public function setPdfUrlAttribute($value) { $this->attributes['factus_pdf_url'] = $value; }

    /**
     * Método para limpiar y probar desde cero
     */
    public function probarDesdeCero()
    {
        // 1. Limpiar todo
        $this->update([
            'factus_id' => null, 'factus_cufe' => null, 'estado_dian' => null,
            'factus_pdf_url' => null, 'facturada' => false, 'factus_numero' => null,
            'factus_qr' => null, 'factus_public_url' => null, 'factus_respuesta' => null,
            'factus_error' => null, 'factus_fecha_generacion' => null
        ]);

        Log::info('=== INICIANDO PRUEBA DESDE CERO PARA VENTA #' . $this->id . ' ===');

        // 2. Probar con payload exacto de documentación
        $resultadoPrueba = $this->probarPayloadExacto();

        // 3. Si falla la prueba exacta, probar con datos reales
        if (!$resultadoPrueba['resultado']['success']) {
            Log::warning('Prueba exacta falló, intentando con datos reales...');
            $resultadoReal = $this->generarFacturaElectronica();
            return $resultadoReal;
        }

        return $resultadoPrueba['resultado'];
    }
}
