<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
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
        $statusId = $this->route('status');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('task_statuses', 'name')->ignore($statusId)
            ],
            'color' => 'sometimes|required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'is_default' => 'nullable|boolean',
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
//            'name.required' => 'Status name is required',
//            'name.unique' => 'A status with this name already exists',
//            'color.required' => 'Status color is required',
//            'color.regex' => 'Color must be a valid hex color code (e.g., #FF5733)',
        ];
    }
}

