<?php

namespace App\Features\IeLayout\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIeLayoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'lot_id' => ['nullable', 'exists:lots,id'],
            'price' => ['sometimes', 'required', 'numeric'],
            'department' => ['sometimes', 'required', 'string', 'max:255'],
            'total_smv' => ['sometimes', 'numeric'],
            'man_power_sewer' => ['sometimes', 'numeric'],
            'man_power_matching' => ['sometimes', 'numeric'],
            'man_power_qc' => ['sometimes', 'numeric'],
            'man_power_others' => ['sometimes', 'numeric'],
            'efficiency_constant' => ['sometimes', 'numeric'],
            'details' => ['nullable', 'array'],
            'details.*.id' => ['nullable', 'exists:time_studies,id'],
            'details.*.operation_id' => ['nullable', 'required_without:details.*.operation_name'],
            'details.*.operation_name' => ['nullable', 'string', 'max:255'],
            'details.*.section' => ['required_with:details', 'string'],
            'details.*.man_power' => ['sometimes', 'numeric'],
            'details.*.handling_position' => ['nullable', 'string', 'max:255'],
            'details.*.handling_position_value' => ['nullable', 'numeric'],
            'details.*.length' => ['nullable', 'numeric'],
            'details.*.sequence' => ['nullable', 'integer'],
            'details.*.machine_type' => ['nullable', 'string', 'max:255'],
            'details.*.machine_turn' => ['nullable', 'numeric'],
        ];
    }
}
