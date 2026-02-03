<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Venta;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\FactusService;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\VentaResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\VentaResource\RelationManagers\DetallesRelationManager;


class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\DateTimePicker::make('fecha_venta')
                    ->required(),
                Forms\Components\TextInput::make('total')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('pagado')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('restante')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente_id')
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
                }),

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
