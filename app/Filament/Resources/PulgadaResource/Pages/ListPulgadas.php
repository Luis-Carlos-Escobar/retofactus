<?php

namespace App\Filament\Resources\PulgadaResource\Pages;

use App\Filament\Resources\PulgadaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPulgadas extends ListRecords
{
    protected static string $resource = PulgadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
