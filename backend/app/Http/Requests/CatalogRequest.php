<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
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
            'lang' => ['sometimes', 'string', Rule::in(array_keys(config('fatherstock.languages')))],
        ];
    }

    public function language(): string
    {
        return $this->validated('lang', config('fatherstock.default_language'));
    }
}
