<?php

namespace App\Features\LayingPlanning\Requests;

use App\Features\LayingPlanning\Services\LayingPlanningDetailService;
use Illuminate\Foundation\Http\FormRequest;

class DuplicateLayingPlanningDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'count' => [
                'required',
                'integer',
                'min:1',
                'max:' . LayingPlanningDetailService::MAX_DUPLICATE_COUNT,
            ],
        ];
    }
}
