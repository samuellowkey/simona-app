<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRealisasiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Otorisasi detail (kepemilikan) akan di-handle di Controller,
        // tapi secara umum harus Operator atau Admin.
        return $this->user()->hasAnyRole(['Operator', 'Admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tanggal_realisasi'    => 'required|date',
            'progres_fisik'        => 'required|numeric|min:0|max:100',
            'nominal_realisasi'    => 'required|numeric|min:1',
            'keterangan'           => 'required|string|max:500',
            'bukti_nota'           => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ];
    }
}
