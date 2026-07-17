<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRealisasiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya Operator atau Admin yang boleh menginput realisasi
        return $this->user()->hasAnyRole(['Operator', 'Admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'kegiatan_id'          => 'required|integer|exists:kegiatan,id',
            'tanggal_realisasi'    => 'required|date',
            'nominal_realisasi'    => 'required|numeric|min:1',
            'progres_fisik_persen' => 'required|integer|min:0|max:100',
            'keterangan'           => 'nullable|string|max:500',
            'bukti_nota'           => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ];
    }
}
