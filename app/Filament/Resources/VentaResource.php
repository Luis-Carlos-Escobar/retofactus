<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Venta;
use App\Models\Producto;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\VentaResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\VentaResource\RelationManagers;

class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Otros campos primero
            Forms\Components\Select::make('cliente_id')
                ->relationship('cliente', 'nombre')
                ->searchable()
                ->required(),
            Forms\Components\DatePicker::make('fecha_venta')
                ->required()
                ->default(now()),
            // SECCIÓN DE PRODUCTOS
            Forms\Components\Section::make('Productos')->schema([
                Forms\Components\Repeater::make('detalles')
                    ->relationship('detalles')
                    ->schema([
                        Forms\Components\Select::make('producto_id')
                            ->label('Producto')
                            ->options(Producto::all()->pluck('nombre_completo', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $producto = Producto::find($state);
                                $cantidad = (float) ($get('cantidad') ?? 0);

                                if ($producto) {
                                    $set('precio_unitario', $producto->precio);
                                    $subtotal = $producto->precio * $cantidad;
                                    $set('subtotal', $subtotal);
                                } else {
                                    $set('precio_unitario', 0);
                                    $set('subtotal', 0);
                                }

                                // Recalcular total general
                                static::recalcularTotales($set, $get);
                            }),

                        Forms\Components\TextInput::make('cantidad')
                            ->label('Cantidad')
                            ->numeric()
                            ->required()
                            ->default(1) // Cambia esto a 1 para mejor funcionamiento
                            ->minValue(1)
                            ->reactive()
                            ->debounce(500) // 300ms de espera
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $precio = (float) ($get('precio_unitario') ?? 0);
                                $cantidad = (float) $state;
                                $subtotal = $precio * $cantidad;
                                $set('subtotal', $subtotal);

                                // Recalcular total general
                                static::recalcularTotales($set, $get);
                            }),

                        Forms\Components\TextInput::make('precio_unitario')
                            ->label('Precio Unitario')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(4)
                    ->createItemButtonLabel('Agregar Producto')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Recalcular cuando se agregan o eliminan productos
                        static::recalcularTotales($set, $get);
                    })
                    ->deleteAction(
                        fn ($action) => $action->after(
                            fn ($set, $get) => static::recalcularTotales($set, $get)
                        ),
                    )
                    ->reorderable(false),

                Forms\Components\TextInput::make('total')
                    ->label('Total Venta')
                    ->numeric()
                    ->disabled()
                    ->reactive()
                    ->default(0)
                    ->dehydrated(true),

                Forms\Components\TextInput::make('pagado')
                    ->label('Pagado')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->minValue(0)
                    ->reactive()
                    ->debounce(500)
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $total = (float) ($get('total') ?? 0);
                        $pagado = (float) ($state ?? 0);

                        // Calcular la diferencia
                        $diferencia = $total - $pagado;
                        $cambio = $diferencia * -1;
                        // Si es positivo: restante por pagar
                        // Si es negativo: cambio a devolver (valor absoluto)
                        $set('restante', $cambio);
                    }),

                Forms\Components\TextInput::make('restante')
                    ->label('Saldo / Cambio')
                    ->numeric()
                    ->disabled()
                    ->reactive()
                    ->default(0)
                    ->dehydrated(true),
            ]), // ← Esta línea cierra el schema de la Section
        ]); // ← Esta línea cierra el schema del form
    }

    // Función corregida para calcular totales
    private static function recalcularTotales(callable $set, callable $get): void
    {
        // Obtener todos los detalles del repeater
        $detalles = $get('../../detalles') ?? [];
        $total = 0;

        // Calcular total sumando todos los subtotales
        foreach ($detalles as $detalle) {
            $subtotal = (float) ($detalle['subtotal'] ?? 0);
            $total += $subtotal;
        }

        // Actualizar el campo total (este valor se guardará)
        $set('../../total', $total);

        // Obtener el pagado actual
        $pagado = (float) ($get('../../pagado') ?? 0);

        // Calcular saldo/cambio (este valor se guardará)
        $saldo = $total - $pagado;
        $set('../../restante', $saldo);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente.nombre'),
                Tables\Columns\TextColumn::make('fecha_venta')
                    ->date(),
                Tables\Columns\TextColumn::make('total')
                    ->money('COP'),
                Tables\Columns\TextColumn::make('pagado')
                    ->money('COP'),
                Tables\Columns\TextColumn::make('restante')
                    ->money('COP'),
                // En VentaResource.php - método table()

            Tables\Columns\TextColumn::make('factus_numero')
                ->label('N° Factura')
                ->searchable()
                ->toggleable()
                ->sortable()
                ->color(fn ($state): string => $state ? 'success' : 'gray')
                ->formatStateUsing(fn ($state): string => $state ?? 'No generada'),

            Tables\Columns\IconColumn::make('facturada')
                ->label('Facturada')
                ->boolean()
                ->toggleable()
                ->sortable(),

            Tables\Columns\TextColumn::make('estado_dian')  // Columna existente
                ->label('Estado DIAN')
                ->badge()
                ->color(fn ($state): string => match($state) {
                    'ACEPTADA' => 'success',
                    'RECHAZADA' => 'danger',
                    'EN_PROCESO' => 'warning',
                    default => 'gray',
                })
                ->toggleable(),

            Tables\Columns\TextColumn::make('factus_fecha_generacion')
                ->label('Fecha Factura')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generarFactura')
                    ->label('Facturar')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(fn (Venta $record): bool => !$record->facturada)
                    ->action(function (Venta $record, Tables\Actions\Action $action) {
                    try {
                        // Deshabilitar el botón mientras se procesa
                        $action->disabled();

                        // Generar factura
                        $resultado = $record->generarFacturaElectronica();

                        if ($resultado['success']) {
                            Notification::make()
                                ->title('✅ Factura generada exitosamente')
                                ->body('Número: ' . ($resultado['factura']['numero'] ?? 'N/A'))
                                ->success()
                                ->send();

                            // Recargar la fila
                            $action->success();
                        } else {
                            Notification::make()
                                ->title('❌ Error al generar factura')
                                ->body($resultado['error'] ?? 'Error desconocido')
                                ->danger()
                                ->send();

                            $action->failure();
                        }

                    } catch (\Exception $e) {
                        Log::error('Error generando factura', [
                            'venta_id' => $record->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        Notification::make()
                            ->title('❌ Error del sistema')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();

                        $action->failure();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Generar Factura Electrónica')
                ->modalDescription('¿Estás seguro de generar la factura electrónica para esta venta?')
                ->modalSubmitActionLabel('Sí, generar factura')
                ->after(fn () => sleep(2)), // Pequeña pausa para ver la notificación
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentas::route('/'),
            'create' => Pages\CreateVenta::route('/create'),
            'edit' => Pages\EditVenta::route('/{record}/edit'),
        ];
    }
}
