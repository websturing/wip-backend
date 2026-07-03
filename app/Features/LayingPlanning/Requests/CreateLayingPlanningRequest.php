<?php

namespace App\Features\LayingPlanning\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLayingPlanningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.po_number' => ['nullable', 'string', 'max:255'],
            '*.color_alias' => ['nullable', 'string', 'max:255'],
            '*.fabric_alias' => ['nullable', 'string', 'max:255'],
            '*.lot_id' => ['required', 'uuid', 'exists:lots,id'],
            '*.laying_planning_type_id' => ['required', 'uuid', 'exists:laying_planning_types,id'],
            '*.laying_planning_parent_id' => ['nullable', 'uuid', 'exists:laying_plannings,id'],
            '*.color_id' => ['required', 'uuid', 'exists:colors,id'],
            '*.fabric_id' => ['required', 'uuid', 'exists:fabrics,id'],
            '*.plan_date' => ['required', 'date'],
            '*.fabric_pattern' => ['required', 'string', 'max:255'],
            '*.is_combine' => ['sometimes', 'boolean'],
            '*.is_set_item' => ['sometimes', 'boolean'],
            '*.parts' => ['nullable', 'array'],
            '*.parts.*.item_part' => ['required_with:*.parts', 'string', 'max:255'],
            '*.parts.*.item_part_group_code' => ['nullable', 'string', 'max:255'],
            '*.sizes' => ['required', 'array', 'min:1'],
            '*.sizes.*.size_id' => ['required', 'uuid', 'exists:sizes,id'],
            '*.sizes.*.order_qty' => ['required', 'integer', 'min:0'],
        ];
    }
}
