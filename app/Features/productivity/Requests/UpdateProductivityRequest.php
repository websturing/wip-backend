<?php

namespace App\Features\productivity\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
