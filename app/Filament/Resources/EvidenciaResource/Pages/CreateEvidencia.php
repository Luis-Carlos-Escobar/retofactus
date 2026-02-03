<?php

namespace App\Filament\Resources\EvidenciaResource\Pages;

use App\Filament\Resources\EvidenciaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvidencia extends CreateRecord
{
    protected static string $resource = EvidenciaResource::class;

    public ?int $procesoId = null;

    public function mount(): void
    {
        parent::mount();

        $this->procesoId = request()->query('proceso_id');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['proceso_id'] = $this->procesoId;

        return $data;
    }
}
