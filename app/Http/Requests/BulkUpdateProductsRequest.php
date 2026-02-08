<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateProductsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check(); // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'case_pack' => 'nullable|integer|min:1',
            'srp' => 'nullable|numeric|min:0',
            'allocation_per_case' => 'nullable|integer|min:0',
            'cash_bank_card_scheme' => 'nullable|string|max:255',
            'po15_scheme' => 'nullable|string|max:255',
            'freebie_sku' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'product_ids.required' => 'Please select at least one product.',
            'product_ids.*.exists' => 'One or more selected products do not exist.',
            'case_pack.integer' => 'Case pack must be a valid number.',
            'case_pack.min' => 's must be at least 1.',
            'srp.numeric' => 'SRP must be a valid number.',
            'srp.min' => 'SRP cannot be negative.',
            'allocation_per_case.integer' => 'Allocation per case must be a valid number.',
            'allocation_per_case.min' => 'Allocation per case cannot be negative.',
        ];
    }
}