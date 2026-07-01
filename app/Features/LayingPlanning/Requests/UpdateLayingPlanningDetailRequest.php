<?php

namespace App\Features\LayingPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLayingPlanningDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'laying_planning_detail_type_id' => ['sometimes', 'nullable', 'uuid', 'exists:laying_planning_detail_types,id'],
            'layer_qty' => ['sometimes', 'required', 'integer'],
            'marker_code' => ['sometimes', 'required', 'string', 'max:255'],
            'marker_yard' => ['sometimes', 'required', 'integer'],
            'marker_inch' => ['sometimes', 'required', 'numeric'],
            'allowance_inch' => ['sometimes', 'required', 'numeric'],
            'is_pilot_run' => ['sometimes', 'boolean'],
            'sizes' => ['sometimes', 'required', 'array', 'min:1'],
            'sizes.*.size_id' => ['required_with:sizes', 'uuid', 'exists:sizes,id'],
            'sizes.*.ratio_per_size' => ['required_with:sizes', 'integer', 'min:1'],
        ];
    }
}
