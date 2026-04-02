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
            'price' => ['required', 'numeric'],
            'is_gl_number' => ['required', 'boolean'],
            'gl_number' => ['nullable', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'array'],
            'details.*.operation_id' => ['required_with:details', 'exists:operations,id'],
            'details.*.handling_position' => ['required_with:details', 'string', 'max:255'],
            'details.*.length' => ['required_with:details', 'integer'],
            'details.*.sequence' => ['required_with:details', 'integer'],
            'details.*.machine_type' => ['required_with:details', 'string', 'max:255'],
            'details.*.machine_turn' => ['required_with:details', 'numeric'],
        ];
    }
}
