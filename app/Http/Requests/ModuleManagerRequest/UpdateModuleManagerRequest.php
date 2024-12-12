<?php

namespace App\Http\Requests\ModuleManagerRequest;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModuleManagerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $module = $this->route('module');
        return [
            'name' => [
                'required',
                'min:3',
                'regex:/^[^0-9]*$/',
                Rule::unique('modules')
                    ->where('user_id', $this->user()->id)
                    ->ignore($module),
            ],
            'sidebar_name' => [
                'required',
                'min:3',
                'regex:/^[^0-9]*$/',
                Rule::unique('menus')
                    ->where(function ($query) use ($module) {
                        $query->where('module_id', '!=',  $module);
                    })


            ]
        ];
    }
}
