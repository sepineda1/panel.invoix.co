<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use App\Support\Slug;
use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || ($message = Slug::error($value)) !== null) {
                    $fail($message ?? 'El subdominio no es válido.');

                    return;
                }

                if (Tenant::query()->where('slug', $value)->exists()) {
                    $fail('Ese subdominio ya está en uso.');
                }
            }],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:190'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre comercial.',
            'admin_name.required' => 'Escribe el nombre del administrador.',
            'admin_email.required' => 'Escribe el correo del administrador.',
            'admin_email.email' => 'El correo del administrador no es válido.',
        ];
    }
}
