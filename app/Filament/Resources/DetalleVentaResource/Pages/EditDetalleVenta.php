<?php

namespace App\Filament\Resources\DetalleVentaResource\Pages;

use App\Filament\Resources\DetalleVentaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDetalleVenta extends EditRecord
{
    protected static string $resource = DetalleVentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
