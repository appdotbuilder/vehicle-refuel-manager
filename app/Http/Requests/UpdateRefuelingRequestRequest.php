<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefuelingRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $request = $this->route('refueling_request');
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // If this is an action request, check role-based permissions
        if ($this->has('action')) {
            $action = $this->input('action');
            
            switch ($action) {
                case 'approve':
                case 'reject':
                    return $user->isSales() && $request->canBeReviewed();
                case 'complete':
                    return $user->isShift() && $request->canBeCompleted();
                default:
                    return false;
            }
        }
        
        // Regular update - only distributor can edit their own pending requests
        return $user->isDistributor() && 
               $request->created_by === $user->id &&
               $request->canBeEdited();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // If this is an action request (approve, reject, complete)
        if ($this->has('action')) {
            $rules = ['action' => 'required|string|in:approve,reject,complete'];
            
            if ($this->input('action') === 'reject') {
                $rules['rejection_reason'] = 'required|string|max:1000';
            }
            
            return $rules;
        }
        
        // Regular update rules
        return [
            'no_do' => 'required|string|max:255|unique:refueling_requests,no_do,' . $this->route('refueling_request')->id,
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