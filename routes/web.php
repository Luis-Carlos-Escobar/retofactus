<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

// routes/web.php

// Prueba 100% SEGURA usando EXACTAMENTE el ejemplo de documentación
Route::get('/factus-exact-example-test', function () {

    // Payload EXACTO como la documentación
    $exactExamplePayload = [
        'document' => '01',
        'numbering_range_id' => 8,
        'reference_code' => 'fact0022025',
        'observation' => '',
        'payment_method_code' => '10',
        'establishment' => [
            'name' => 'SuperMarket',
            'address' => 'calle 10 # 3-13',
            'phone_number' => '0987654321',
            'email' => 'supermarket@gmail.com',
            'municipality_id' => 980
        ],
        'customer' => [
            'identification' => '123456789',
            'dv' => '3',
            'company' => '',
            'trade_name' => '',
            'names' => 'Alan Turing',
            'address' => 'calle 1 # 2-68',
            'email' => 'alanturing@enigmasas.com',
            'phone' => '1234567890',
            'legal_organization_id' => '2',
            'tribute_id' => '21',
            'identification_document_id' => 3,
            'municipality_id' => '980'
        ],
        'items' => [
            [
                'code_reference' => '12345',
                'name' => 'producto de prueba',
                'quantity' => 1,
                'discount_rate' => 20,
                'price' => 50000,
                'tax_rate' => '19.00',
                'unit_measure_id' => 70,
                'standard_code_id' => 1,
                'is_excluded' => 0,
                'tribute_id' => 1,
                'withholding_taxes' => [
                    [
                        'code' => '06',
                        'withholding_tax_rate' => 7.38
                    ],
                    [
                        'code' => '05',
                        'withholding_tax_rate' => 15.12
                    ]
                ]
            ],
            [
                'code_reference' => '54321',
                'name' => 'producto de prueba 2',
                'quantity' => 1,
                'discount_rate' => 0,
                'price' => 50000,
                'tax_rate' => '5.00',
                'unit_measure_id' => 70,
                'standard_code_id' => 1,
                'is_excluded' => 0,
                'tribute_id' => 1,
                'withholding_taxes' => []
            ]
        ],
        'allowance_charges' => [
            [
                'concept_type' => '03',
                'is_surcharge' => true,
                'reason' => 'Propina',
                'base_amount' => '90000.00',
                'amount' => '9000.00'
            ]
        ]
    ];

    // Obtener token manualmente
    $token = Http::asForm()->post('https://api-sandbox.factus.com.co/oauth/token', [
        'grant_type' => 'password',
        'client_id' => config('services.factus.client_id'),
        'client_secret' => config('services.factus.client_secret'),
        'username' => config('services.factus.user'),
        'password' => config('services.factus.password'),
    ])->json()['access_token'];

    $response = Http::withToken($token)
        ->post('https://api-sandbox.factus.com.co/v1/bills/validate', $exactExamplePayload);

    return response()->json([
        'status' => $response->status(),
        'response' => $response->json(),
        'note' => 'Si esto falla, el problema NO es nuestro código, es la API o configuración'
    ]);
});

// Prueba SIMPLIFICADA pero con valores correctos
Route::get('/factus-minimal-correct', function () {

    // Payload MÍNIMO pero con valores VÁLIDOS
    $minimalValidPayload = [
        'document' => '01',
        'numbering_range_id' => 8,
        'reference_code' => 'TEST-' . time(),
        'observation' => '',
        'payment_method_code' => '10',
        'establishment' => [
            'name' => 'Mi Tienda',
            'address' => 'Calle 123',
            'phone_number' => '3001234567',
            'email' => 'tienda@test.com',
            'municipality_id' => 980
        ],
        'customer' => [
            'identification' => '987654321',
            'dv' => '0',
            'company' => '',
            'trade_name' => '',
            'names' => 'Cliente Test',
            'address' => 'Calle Test 456',
            'email' => 'cliente@test.com',
            'phone' => '3209876543',
            'legal_organization_id' => '2',
            'tribute_id' => '21',
            'identification_document_id' => 3,
            'municipality_id' => '980'
        ],
        'items' => [
            [
                'code_reference' => 'ITEM-001',
                'name' => 'Producto Test',
                'quantity' => 1,
                'discount_rate' => 0,
                'price' => 10000,
                'tax_rate' => '19.00',
                'unit_measure_id' => 70,
                'standard_code_id' => 1,
                'is_excluded' => 0,
                'tribute_id' => 1,
                'withholding_taxes' => []
            ]
        ],
        // CLAVE: Usar valores VÁLIDOS (no ceros)
        'allowance_charges' => [
            [
                'concept_type' => '03', // Válido según ejemplo
                'is_surcharge' => true, // Booleano verdadero
                'reason' => 'Servicio',
                'base_amount' => '10000.00', // Mayor que 0
                'amount' => '1000.00' // Mayor que 0
            ]
        ]
    ];

    $token = Http::asForm()->post('https://api-sandbox.factus.com.co/oauth/token', [
        'grant_type' => 'password',
        'client_id' => config('services.factus.client_id'),
        'client_secret' => config('services.factus.client_secret'),
        'username' => config('services.factus.user'),
        'password' => config('services.factus.password'),
    ])->json()['access_token'];

    $response = Http::withToken($token)
        ->post('https://api-sandbox.factus.com.co/v1/bills/validate', $minimalValidPayload);

    return response()->json([
        'status' => $response->status(),
        'success' => $response->successful(),
        'errors' => $response->successful() ? null : ($response->json()['data']['errors'] ?? $response->body()),
        'payload_summary' => [
            'allowance_charges_used' => $minimalValidPayload['allowance_charges'][0],
            'note' => 'Valores mayores a 0 y concept_type válido'
        ]
    ]);
});
Route::get('/factus-no-allowance-test', function () {

    // Intentar SIN allowance_charges completamente
    $payloadWithoutAllowance = [
        'document' => '01',
        'numbering_range_id' => 8,
        'reference_code' => 'NO-ALLOW-' . time(),
        'observation' => '',
        'payment_method_code' => '10',
        'establishment' => [
            'name' => 'Test Store',
            'address' => 'Address 123',
            'phone_number' => '3001111111',
            'email' => 'store@test.com',
            'municipality_id' => 980
        ],
        'customer' => [
            'identification' => '111111111',
            'dv' => '1',
            'company' => '',
            'trade_name' => '',
            'names' => 'Test Client',
            'address' => 'Client Address',
            'email' => 'client@test.com',
            'phone' => '3202222222',
            'legal_organization_id' => '2',
            'tribute_id' => '21',
            'identification_document_id' => 3,
            'municipality_id' => '980'
        ],
        'items' => [
            [
                'code_reference' => 'TEST-001',
                'name' => 'Test Product',
                'quantity' => 1,
                'discount_rate' => 0,
                'price' => 5000,
                'tax_rate' => '19.00',
                'unit_measure_id' => 70,
                'standard_code_id' => 1,
                'is_excluded' => 0,
                'tribute_id' => 1,
                'withholding_taxes' => []
            ]
        ]
        // SIN allowance_charges - eliminado completamente
    ];

    $token = Http::asForm()->post('https://api-sandbox.factus.com.co/oauth/token', [
        'grant_type' => 'password',
        'client_id' => config('services.factus.client_id'),
        'client_secret' => config('services.factus.client_secret'),
        'username' => config('services.factus.user'),
        'password' => config('services.factus.password'),
    ])->json()['access_token'];

    $response = Http::withToken($token)
        ->post('https://api-sandbox.factus.com.co/v1/bills/validate', $payloadWithoutAllowance);

    return response()->json([
        'status' => $response->status(),
        'response' => $response->json(),
        'note' => 'Probando SIN allowance_charges completamente'
    ]);
});


