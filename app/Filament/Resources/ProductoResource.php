<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Filament\Resources\ProductoResource\RelationManagers;
use App\Models\Producto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;
    protected static ?string $navigationGroup = "Inventario";
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tipo_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('modelo_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('pulgada_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('marca_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('precio')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('numero_pieza')
                    ->maxLength(100),
                Forms\Components\Textarea::make('descripcion')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modelo_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pulgada_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('marca_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('precio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('numero_pieza')
                    ->searchable(),
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
            'index' => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'edit' => Pages\EditProducto::route('/{record}/edit'),
        ];
    }
}
