<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassificacaoFinanceiraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajuste se tiver policy/auth
    }

    public function rules(): array
    {
        $id = $this->route('classificacao_financeira')?->id;

        return [
            'user_id' => ['required','integer'],
            'nome' => ['required','string','max:255'],

            'codigo_contabil' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classificacao_financeira', 'codigo_contabil')->ignore($id),
            ],

            'tipo_natureza' => ['required', Rule::in(['receita','despesa'])],
            'nivel' => ['required', Rule::in(['sintetico','analitico'])],

            'id_pai' => [
                'nullable',
                'integer',
                // garante que o pai existe
                Rule::exists('classificacao_financeira', 'id'),
            ],

            'area_finalidade' => ['nullable', Rule::in(['marketing','vendas','producao','administrativo','rh','financeiro','geral','outros'])],
            'frequencia' => ['nullable', Rule::in(['regular','extraordinaria'])],
            'descricao' => ['nullable','string'],
        ];
    }
}