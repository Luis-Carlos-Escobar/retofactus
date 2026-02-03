<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvidenciaResource\Pages;
use App\Filament\Resources\EvidenciaResource\RelationManagers;
use App\Models\Evidencia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EvidenciaResource extends Resource
{
    protected static ?string $model = Evidencia::class;
    protected static ?string $navigationGroup = "Gestión";
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('proceso_id'),
                Forms\Components\FileUpload::make('imagen')
                    ->image()
                    ->directory('evidencias')
                    ->disk('public')
                    ->required()
                    ->imagePreviewHeight('200')
                    ->openable()
                    ->downloadable(),
                Forms\Components\Textarea::make('comentario')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proceso_id')
                    ->label('Proceso')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('imagen')
                    ->disk('public')
                    ->height(60)
                    ->width(60)
                    ->square()
                    ->visibility('public')
                    ->extraImgAttributes([
                            'class' => 'cursor-pointer',
                        ])
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('comentario')
                    ->limit(50),
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
            'index' => Pages\ListEvidencias::route('/'),
            'create' => Pages\CreateEvidencia::route('/create'),
            'edit' => Pages\EditEvidencia::route('/{record}/edit'),
        ];
    }
}
