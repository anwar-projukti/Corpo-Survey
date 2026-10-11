<?php

namespace App\Http\Requests\PricingPlans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePricingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('pricingPlan'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:100'],
            'name_bn' => ['sometimes', 'required', 'string', 'max:100'],
            'price_en' => ['sometimes', 'required', 'string', 'max:50'],
            'price_bn' => ['sometimes', 'required', 'string', 'max:50'],
            'description_en' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description_bn' => ['sometimes', 'nullable', 'string', 'max:500'],
            'features_en' => ['sometimes', 'nullable', 'array'],
            'features_en.*' => ['string', 'max:200'],
            'features_bn' => ['sometimes', 'nullable', 'array'],
            'features_bn.*' => ['string', 'max:200'],
            'button_text_en' => ['sometimes', 'required', 'string', 'max:50'],
            'button_text_bn' => ['sometimes', 'required', 'string', 'max:50'],
            'button_target' => ['sometimes', 'required', 'string', Rule::in(['register', 'contact'])],
            'highlighted' => ['sometimes', 'boolean'],
            'trial_days' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:365'],
        ];
    }
}
