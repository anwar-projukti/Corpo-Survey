<?php

namespace App\Http\Requests\PricingPlans;

use App\Models\PricingPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePricingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PricingPlan::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:100'],
            'name_bn' => ['required', 'string', 'max:100'],
            'price_en' => ['required', 'string', 'max:50'],
            'price_bn' => ['required', 'string', 'max:50'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'description_bn' => ['nullable', 'string', 'max:500'],
            'features_en' => ['nullable', 'array'],
            'features_en.*' => ['string', 'max:200'],
            'features_bn' => ['nullable', 'array'],
            'features_bn.*' => ['string', 'max:200'],
            'button_text_en' => ['required', 'string', 'max:50'],
            'button_text_bn' => ['required', 'string', 'max:50'],
            'button_target' => ['required', 'string', Rule::in(['register', 'contact'])],
            'highlighted' => ['sometimes', 'boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }
}
