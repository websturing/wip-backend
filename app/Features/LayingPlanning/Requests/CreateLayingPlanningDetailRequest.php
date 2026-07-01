<?php

namespace App\Features\LayingPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLayingPlanningDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'laying_planning_detail_type_id' => ['nullable', 'uuid', 'exists:laying_planning_detail_types,id'],
            'layer_qty' => ['required', 'integer'],
            'marker_code' => ['required', 'string', 'max:255'],
            'marker_yard' => ['required', 'integer'],
            'marker_inch' => ['required', 'numeric'],
            'allowance_inch' => ['required', 'numeric'],
            'is_pilot_run' => ['sometimes', 'boolean'],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*.size_id' => ['required', 'uuid', 'exists:sizes,id'],
            'sizes.*.ratio_per_size' => ['required', 'integer', 'min:1'],
        ];
    }
}
