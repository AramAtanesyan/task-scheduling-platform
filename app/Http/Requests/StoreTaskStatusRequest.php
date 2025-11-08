<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskStatusRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255|unique:task_statuses,name',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Status name is required',
            'name.unique' => 'A status with this name already exists',
            'color.required' => 'Status color is required',
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF5733)',
        ];
    }
}

