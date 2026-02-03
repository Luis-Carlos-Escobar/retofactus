<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcesoResource\Pages;
use App\Filament\Resources\ProcesoResource\RelationManagers;
use App\Models\Proceso;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProcesoResource extends Resource
{
    protected static ?string $model = Proceso::class;
    protected static ?string $navigationGroup = "Gestión";
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
                Forms\Components\Select::make('marca_id')
                    ->label('Marca')
                    ->relationship('marca', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('modelo_id')
                    ->label('Modelo')
                    ->relationship('modelo', 'nombre')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('pulgada_id')
                    ->label('Pulgada')
                    ->relationship('pulgada', 'medida')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('falla')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('descripcion')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('estado')
                    ->required()
                    ->maxLength(255)
                    ->default('En proceso'),
                Forms\Components\DatePicker::make('fecha_ingreso')
                    ->required(),
                Forms\Components\DatePicker::make('fecha_salida'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cliente_id')
                    ->label('Cliente')
                    ->getStateUsing(fn (Proceso $record) => $record->cliente->nombre)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('marca_id')
                    ->label('Marca')
                    ->getStateUsing(fn (Proceso $record) => $record->marca->nombre)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('modelo_id')
                    ->label('modelo')
                    ->getStateUsing(fn (Proceso $record) => $record->modelo->nombre)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pulgada_id')
                    ->label('pulgada')
                    ->getStateUsing(fn (Proceso $record) => $record->pulgada->medida)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('falla')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_ingreso')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_salida')
                    ->date()
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
                Tables\Actions\Action::make('crearEvidencia')
                    ->label('Evidencia')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->url(fn (Proceso $record) =>
                        EvidenciaResource::getUrl('create', [
                            'proceso_id' => $record->id,
                        ])
                    ),
                    Tables\Actions\Action::make('verEvidencias')
                    ->label('Ver Evidencias')
                    ->icon('heroicon-o-document')
                    ->color('primary')
                    ->url(fn (Proceso $record) =>
                        EvidenciaResource::getUrl('index', [
                            'proceso_id' => $record->id,
                        ])
                    ),
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
            'index' => Pages\ListProcesos::route('/'),
            'create' => Pages\CreateProceso::route('/create'),
            'edit' => Pages\EditProceso::route('/{record}/edit'),
        ];
    }
}
