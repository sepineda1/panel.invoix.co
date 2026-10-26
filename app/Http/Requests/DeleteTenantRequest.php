<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $slug = $this->route('tenant')?->slug;

        return [
            'confirm' => ['required', 'string', 'in:'.$slug],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'confirm.in' => 'Escribe el subdominio exacto para confirmar.',
            'confirm.required' => 'Escribe el subdominio para confirmar.',
        ];
    }
}
