<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefuelingRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isDistributor();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'no_do' => 'required|string|max:255|unique:refueling_requests,no_do',
            'nopol' => 'required|string|max:255',
            'distributor_name' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_do.required' => 'Delivery Order Number is required.',
            'no_do.unique' => 'This Delivery Order Number already exists.',
            'nopol.required' => 'Vehicle Registration Number is required.',
            'distributor_name.required' => 'Distributor name is required.',
        ];
    }
}