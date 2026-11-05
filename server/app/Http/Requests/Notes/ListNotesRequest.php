<?php

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;

class ListNotesRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'Размер страницы должен быть целым числом.',
            'per_page.min' => 'Размер страницы должен быть не меньше 1.',
            'per_page.max' => 'Размер страницы должен быть не больше 100.',
        ];
    }
}
