<?php

namespace App\Http\Requests;

use App\Rules\attributeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AttributePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // dd($this);
        return [
            'name' => ['required', Rule::unique('attributes')->where(function ($query) {
                return $query->where('module', $this->module);
            })],

            'code' => [
                'required',
                Rule::unique('attributes')->where(function ($query) {

                    return $query->where('module', $this->module);
                })
            ],
            'module' => 'required',
            'input_types' => 'required',


            ///// sub input validations
            'multi' => 'required_if:input_types,multi',
            'multi.*.name' => [
                'required_if:input_types,multi',
                'string',
                'regex:/^[^\d].*/',
            ],
            'multi.*.type' => [
                'required_if:input_types,multi',
            ]

        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'name is required',
            'name.unique' => 'name should be unique in the module',

            'module.required' => 'module is required',

            'input_types.required' => 'attribute type is required',

            'code.unique' => 'code should be unique in this module',
            'code.required' => 'code is required',

            ///sub input validations rules
            'multi.*.name.regex' => 'the multi attribute name should not begin with number'
        ];
    }
}
