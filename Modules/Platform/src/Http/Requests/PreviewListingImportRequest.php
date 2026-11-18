<?php

namespace Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewListingImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:csv,txt,json', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Selecciona un archivo CSV o JSON.',
            'file.file' => 'El archivo no se pudo subir.',
            'file.extensions' => 'El archivo debe ser .csv, .txt o .json.',
            'file.max' => 'El archivo no puede superar los 2 MB.',
        ];
    }
}
