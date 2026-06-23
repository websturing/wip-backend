<?php

namespace App\Features\LayingPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLayingPlanningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lot_id' => ['sometimes', 'required', 'uuid', 'exists:lots,id'],
            'laying_planning_type_id' => ['sometimes', 'required', 'uuid', 'exists:laying_planning_types,id'],
            'laying_planning_parent_id' => ['sometimes', 'nullable', 'uuid', 'exists:laying_plannings,id'],
            'color_id' => ['sometimes', 'required', 'uuid', 'exists:colors,id'],
            'fabric_id' => ['sometimes', 'required', 'uuid', 'exists:fabrics,id'],
            'plan_date' => ['sometimes', 'required', 'date'],
            'fabric_pattern' => ['sometimes', 'required', 'string', 'max:255'],
            'is_combine' => ['sometimes', 'boolean'],
            'sizes' => ['sometimes', 'required', 'array', 'min:1'],
            'sizes.*.size_id' => ['required', 'uuid', 'exists:sizes,id'],
            'sizes.*.order_qty' => ['required', 'integer', 'min:0'],
        ];
    }
}


