<?php

namespace App\Features\IeLayout\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateIeLayoutRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'lot_id' => ['nullable', 'exists:lots,id'],
            'price' => ['required', 'numeric'],
            'department' => ['required', 'string', 'max:255'],
            'total_smv' => ['sometimes', 'numeric'],
            'man_power_sewer' => ['sometimes', 'numeric'],
            'man_power_matching' => ['sometimes', 'numeric'],
            'man_power_qc' => ['sometimes', 'numeric'],
            'man_power_others' => ['sometimes', 'numeric'],
            'efficiency_constant' => ['sometimes', 'numeric'],
            'details' => ['nullable', 'array'],
            'details.*.operation_id' => ['nullable', 'required_without:details.*.operation_name'],
            'details.*.operation_name' => ['nullable', 'string', 'max:255'],
            'details.*.section' => ['required_with:details', 'string'],
            'details.*.man_power' => ['sometimes', 'numeric'],
            'details.*.handling_position' => ['required_with:details', 'string', 'max:255'],
            'details.*.handling_position_value' => ['sometimes', 'numeric'],
            'details.*.length' => ['required_with:details', 'numeric'],
            'details.*.sequence' => ['required_with:details', 'integer'],
            'details.*.machine_type' => ['required_with:details', 'string', 'max:255'],
            'details.*.machine_turn' => ['required_with:details', 'numeric'],
        ];
    }
}
