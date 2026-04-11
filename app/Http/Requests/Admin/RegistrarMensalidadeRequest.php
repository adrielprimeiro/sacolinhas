<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarMensalidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'competencia' => ['required', 'date_format:Y-m'],
            'valor' => ['required', 'numeric', 'min:0'],
            'data_pagamento' => ['required', 'date'],
        ];
    }
}