<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TahunUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $tahunId = $this->route('tahun')->id ?? $this->route('tahun');

        return [
            'tahun' => [
                'required', 
                'digits:4', 
                'integer', 
                'min:1901', 
                'max:2155', 
                'unique:tahun,tahun,' . $tahunId
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.digits' => 'Tahun harus terdiri dari 4 digit.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.min' => 'Tahun minimal adalah 1901.',
            'tahun.max' => 'Tahun maksimal adalah 2155.',
            'tahun.unique' => 'Tahun ini sudah ada di database.',
        ];
    }
}
