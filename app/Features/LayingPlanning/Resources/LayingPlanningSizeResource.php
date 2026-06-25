<?php

namespace App\Features\LayingPlanning\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayingPlanningSizeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $full = $request->boolean('layingPlanningSizes');

        return $full ? [
            'id'        => $this->pivot->id ?? $this->id,
            'size_id'   => $this->id,
            'size'      => $this->size,
            'order_qty' => (int) ($this->pivot->order_qty ?? 0),
        ] : [
            'size'      => $this->size,
            'order_qty' => (int) ($this->pivot->order_qty ?? 0),
        ];
    }
}
