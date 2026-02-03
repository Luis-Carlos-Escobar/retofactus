<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Venta;
use App\Models\Producto;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\FactusService;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\VentaResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\VentaResource\RelationManagers;


class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la venta')
                ->schema([

                    Forms\Components\Select::make('cliente_id')
                        ->label('Cliente')
                        ->relationship('cliente', 'nombre')
                        ->searchable()
                        ->required(),

                    Forms\Components\DateTimePicker::make('fecha_venta')
                        ->default(now())
                        ->required(),

                ])->columns(2),


            Forms\Components\Section::make('Productos')
                ->schema([

                    Forms\Components\Repeater::make('detalles')
                        ->relationship()
                        ->schema([

                            Forms\Components\Select::make('producto_id')
                                ->label('Producto')
                                ->options(function () {
                                    return Producto::all()->pluck('nombre_completo', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    $producto = Producto::find($state);
                                    if ($producto) {
                                        $set('precio_unitario', $producto->precio);
                                        $set('subtotal', $producto->precio * $get('cantidad', 1));
                                    } else {
                                        $set('precio_unitario', 0);
                                        $set('subtotal', 0);
                                    }
                                }),

                            Forms\Components\TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    $set('subtotal', $get('precio_unitario') * $state);
                                }),

                            Forms\Components\TextInput::make('precio_unitario')
                                ->label('Precio Unitario')
                                ->numeric()
                                ->disabled(),

                            Forms\Components\TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->numeric()
                                ->disabled()
                                ->reactive(),
                        ])
                        ->columns(4)
                        ->createItemButtonLabel('Agregar Producto'),
                    Forms\Components\TextInput::make('total')
                        ->label('Total Venta')
                        ->numeric()
                        ->disabled(),
                    Forms\Components\TextInput::make('pagado')
                        ->label('Pagado')
                        ->numeric(),
                    Forms\Components\TextInput::make('restante')
                        ->label('Restante')
                        ->numeric()
                        ->disabled(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente_id')
                    ->label('Cliente')
                    ->getStateUsing(fn (Venta $record) => $record->cliente->nombre)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_venta')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pagado')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('restante')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('factus_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cufe')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado_dian')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pdf_url')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('facturar')
                ->label('Enviar a Factus')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')

                ->visible(fn ($record) => !$record->factus_id) // evita doble envío

                ->action(function ($record) {

                    try {

                        $factus = app(FactusService::class);

                        $response = $factus->crearFactura(
                            $record->toFactusPayload()
                        );

                        $record->update([
                            'factus_id' => $response['id'] ?? null,
                            'cufe' => $response['cufe'] ?? null,
                            'estado_dian' => $response['status'] ?? 'ENVIADO',
                            'pdf_url' => $response['pdf_url'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Factura enviada a DIAN correctamente')
                            ->success()
                            ->send();

                    } catch (\Throwable $e) {

                        Notification::make()
                            ->title('Error enviando factura')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
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
