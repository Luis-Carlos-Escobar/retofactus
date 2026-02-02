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
                Forms\Components\Select::make('marca_id')
                    ->label('Marca')
                    ->relationship('marca', 'nombre')
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre de la marca')
                            ->required()
                            ->unique(ignoreRecord: true)
                    ]),
                Forms\Components\Select::make('modelo_id')
                    ->label('Modelo')
                    ->relationship('modelo', 'nombre')
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre del modelo')
                            ->required()
                            ->unique(ignoreRecord: true)
                    ])
                    ->createOptionUsing(function (array $data, callable $get) {
                        return \App\Models\Modelo::create([
                            'nombre' => $data['nombre'],
                            'marca_id' => $get('marca_id'),
                        ])->getKey();
                    }),
                Forms\Components\Select::make('tipo_id')
                    ->label('Tipo')
                    ->relationship('tipo', 'nombre')
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre del tipo')
                            ->required()
                            ->unique(ignoreRecord: true)
                    ]),
                Forms\Components\Select::make('pulgada_id')
                    ->label('Pulgada')
                    ->relationship('pulgada', 'medida')
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('Medida')
                            ->label('Medida de la pulgada')
                            ->required()
                            ->unique(ignoreRecord: true)
                    ]),
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
                Tables\Columns\TextColumn::make('marca_id')
                    ->label('Marca')
                    ->getStateUsing(fn (Producto $record) => $record->marca->nombre)
                    ->searchable(),
                Tables\Columns\TextColumn::make('modelo_id')
                    ->label('Modelo')
                    ->getStateUsing(fn (Producto $record) => $record->modelo->nombre)
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_id')
                    ->label('Tipo')
                    ->getStateUsing(fn (Producto $record) => $record->tipo->nombre)
                    ->searchable(),
                Tables\Columns\TextColumn::make('pulgada_id')
                    ->label('Pulgada')
                    ->getStateUsing(fn (Producto $record) => $record->pulgada->medida)
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
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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
