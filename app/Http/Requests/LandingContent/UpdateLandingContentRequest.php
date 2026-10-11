<?php

namespace App\Http\Requests\LandingContent;

use App\Models\LandingContent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('landingContent') ?? LandingContent::current());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $textRules = ['sometimes', 'nullable', 'string', 'max:255'];
        $longTextRules = ['sometimes', 'nullable', 'string', 'max:2000'];

        return [
            'hero_eyebrow_en' => $textRules,
            'hero_eyebrow_bn' => $textRules,
            'hero_tagline_en' => $textRules,
            'hero_tagline_bn' => $textRules,
            'hero_body_en' => $longTextRules,
            'hero_body_bn' => $longTextRules,
            'hero_footnote_en' => $textRules,
            'hero_footnote_bn' => $textRules,

            'features_title_en' => $textRules,
            'features_title_bn' => $textRules,

            'feature_1_title_en' => $textRules,
            'feature_1_title_bn' => $textRules,
            'feature_1_body_en' => $longTextRules,
            'feature_1_body_bn' => $longTextRules,

            'feature_2_title_en' => $textRules,
            'feature_2_title_bn' => $textRules,
            'feature_2_body_en' => $longTextRules,
            'feature_2_body_bn' => $longTextRules,

            'feature_3_title_en' => $textRules,
            'feature_3_title_bn' => $textRules,
            'feature_3_body_en' => $longTextRules,
            'feature_3_body_bn' => $longTextRules,

            'pricing_title_en' => $textRules,
            'pricing_title_bn' => $textRules,
            'pricing_body_en' => $longTextRules,
            'pricing_body_bn' => $longTextRules,
            'pricing_cta_en' => $textRules,
            'pricing_cta_bn' => $textRules,

            'about_title_en' => $textRules,
            'about_title_bn' => $textRules,
            'about_body_en' => $longTextRules,
            'about_body_bn' => $longTextRules,
            'about_stat_response_types_value_en' => $textRules,
            'about_stat_response_types_value_bn' => $textRules,
            'about_stat_languages_value_en' => $textRules,
            'about_stat_languages_value_bn' => $textRules,
            'about_stat_offline_value_en' => $textRules,
            'about_stat_offline_value_bn' => $textRules,

            'contact_title_en' => $textRules,
            'contact_title_bn' => $textRules,
            'contact_body_en' => $longTextRules,
            'contact_body_bn' => $longTextRules,
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact_office_address_en' => $longTextRules,
            'contact_office_address_bn' => $longTextRules,
        ];
    }
}
