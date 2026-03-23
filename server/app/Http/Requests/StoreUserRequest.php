<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
        return [
            'name'     => 'required|string|unique:users,name|max:255',
            'email'    => 'required|email|unique:users,email|max:255',
            'password' => 'required|string',
            // Itt a változtatás
            'role'     => 'nullable|integer|between:1,3' //role kihagyható, de ha meg van adva, akkor csak 1, 2 vagy 3 lehet

        ];
    }


    public function messages(): array
    {
        return [
        'name.required'     => 'The name field is required.',
        'name.string'       => 'The name must be a string.',
        'name.unique'       => 'This name is already in use.',
        'name.max'          => 'The name may not be greater than 255 characters.',

        'email.required'    => 'The email field is required.',
        'email.email'       => 'Please provide a valid email address.',
        'email.unique'      => 'This email is already in use.',
        'email.max'         => 'The email may not be greater than 255 characters.',

        'password.required' => 'The password field is required.',
        'password.string'   => 'The password format is invalid.',
        'password.min'      => 'The password must be at least 8 characters.',

        'role.integer'      => 'The role must be a number.',
        'role.between'      => 'The role must be a number between 1 and 3.',
            ];
    }
}
