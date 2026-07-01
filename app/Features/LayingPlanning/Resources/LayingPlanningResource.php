<?php

namespace App\Features\LayingPlanning\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LayingPlanningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'serial_number'  => $this->serial_number,
            'plan_date'      => $this->plan_date?->toDateString(),
            'fabric_pattern' => $this->fabric_pattern,

            // --- Structure / Parent-Child Hierarchy ---
            'planning_type'             => $this->whenLoaded('layingPlanningType', fn() => $this->layingPlanningType->type),
            'laying_planning_parent_id' => $this->laying_planning_parent_id,
            'parent'                    => $this->whenLoaded('parent', fn() => $this->parent ? [
                'id'            => $this->parent->id,
                'serial_number' => $this->parent->serial_number,
            ] : null),
            'children'                  => $this->whenLoaded('children', fn() =>
                $this->children->map(fn($child) => [
                    'id'            => $child->id,
                    'serial_number' => $child->serial_number,
                ])
            ),

            // --- Combine Group ---
            'is_combine'     => $this->is_combine,
            'combine_number' => $this->relationLoaded('combineGroup') && $this->combineGroup ? $this->combineGroup->combine_number : null,
            'combine_group'  => $this->when(
                ($request->boolean('layingPlanningCombine') || $request->boolean('layingPlanningCombines')) && $this->relationLoaded('combineGroup'),
                fn() => $this->combineGroup ? [
                    'id'             => $this->combineGroup->id,
                    'combine_number' => $this->combineGroup->combine_number,
                    'remarks'        => $this->combineGroup->remarks,
                ] : null
            ),

            // --- Set Item ---
            'is_set_item'    => $this->is_set_item,
            'parts'          => $this->whenLoaded('parts', fn() =>
                $this->parts->map(fn($part) => [
                    'id'                   => $part->id,
                    'item_part'            => $part->item_part,
                    'item_part_group_code' => $part->item_part_group_code,
                ])
            ),
            'group_parts'    => $this->whenLoaded('groupParts', fn() =>
                $this->groupParts->map(fn($gp) => [
                    'id'                 => $gp->id,
                    'item_part'          => $gp->item_part,
                    'laying_planning_id' => $gp->laying_planning_id,
                ])
            ),

            // --- Flat fields: selalu tampil ---
            'lot_code'       => $this->whenLoaded('lot', fn() => $this->lot->lot_code),
            'color_name'     => $this->whenLoaded('color', fn() => $this->color->standard_name),
            'fabric_content' => $this->whenLoaded('fabric', fn() => $this->fabric->standard_content),

            // --- Object relasi: hanya muncul jika query param `layingPlanning*` bernilai true ---
            'type' => $this->when(
                $request->boolean('layingPlanningTypes') && $this->relationLoaded('layingPlanningType'),
                fn() => [
                    'id'   => $this->layingPlanningType->id,
                    'type' => $this->layingPlanningType->type,
                ]
            ),

            'lot' => $this->when(
                $request->boolean('layingPlanningLots') && $this->relationLoaded('lot'),
                fn() => [
                    'id'            => $this->lot->id,
                    'lot_code'      => $this->lot->lot_code,
                    'lot_number'    => $this->lot->lot_number,
                    'style_no'      => $this->lot->style_no,
                    'brand'         => $this->lot->brand,
                    'gmt_qty'       => $this->lot->gmt_qty,
                    'delivery_date' => $this->lot->delivery_date,
                    'order_date'    => $this->lot->order_date,
                    'gl_group'      => $this->lot->relationLoaded('glGroup') ? [
                        'id'        => $this->lot->glGroup->id,
                        'gl_number' => $this->lot->glGroup->gl_number,
                    ] : null,
                ]
            ),

            'color' => $this->when(
                $request->boolean('layingPlanningColors') && $this->relationLoaded('color'),
                fn() => [
                    'id'            => $this->color->id,
                    'code'          => $this->color->code,
                    'standard_name' => $this->color->standard_name,
                ]
            ),

            'fabric' => $this->when(
                $request->boolean('layingPlanningFabrics') && $this->relationLoaded('fabric'),
                fn() => [
                    'id'               => $this->fabric->id,
                    'standard_content' => $this->fabric->standard_content,
                ]
            ),

            // --- Sizes (via sizeDetails pivot) ---
            'sizes' => $this->whenLoaded('sizeDetails', fn() =>
                LayingPlanningSizeResource::collection($this->sizeDetails)
            ),

            // --- Details (laying planning detail items) ---
            'details' => $this->whenLoaded('details', fn() =>
                LayingPlanningDetailResource::collection($this->details)
            ),

            // --- Timestamps ---
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
