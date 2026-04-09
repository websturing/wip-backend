<?php

namespace App\Features\productivity\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
