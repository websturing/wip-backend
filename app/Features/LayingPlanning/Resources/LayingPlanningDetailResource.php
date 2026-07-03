<?php

namespace App\Features\LayingPlanning\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayingPlanningDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'laying_planning_id' => $this->laying_planning_id,
            'laying_planning_detail_type_id' => $this->laying_planning_detail_type_id,
            'table_number' => $this->table_number,
            'layer_qty' => $this->layer_qty,
            'marker_code' => $this->marker_code,
            'marker_yard' => $this->marker_yard,
            'marker_inch' => $this->marker_inch,
            'allowance_inch' => $this->allowance_inch,
            'marker_length' => $this->marker_length,
            'total_length' => $this->total_length,
            'is_pilot_run' => $this->is_pilot_run,
            'type' => $this->whenLoaded('type', fn() => [
                'id' => $this->type->id,
                'detail_type' => $this->type->detail_type,
                'description' => $this->type->description,
            ]),
            'sizes' => $this->whenLoaded('sizes', fn() =>
                $this->sizes->map(fn($size) => [
                    'id' => $size->id,
                    'size_id' => $size->size_id,
                    'ratio_per_size' => $size->ratio_per_size,
                    'size_name' => $size->relationLoaded('size') ? $size->size?->size : null,
                ])
            ),
            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'updated_by' => $this->whenLoaded('updatedBy', fn() => [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
