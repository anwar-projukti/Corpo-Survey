<?php

namespace App\Http\Requests\Imports;

use App\Models\SurveyResponse;
use Illuminate\Foundation\Http\FormRequest;

class StoreRespondentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import', SurveyResponse::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 'mimes' validates by sniffing the file's actual contents, not
            // the client-supplied extension/MIME string (spec §5.9) — never
            // trust the browser-reported type for an upload.
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:10240'],
        ];
    }
}
