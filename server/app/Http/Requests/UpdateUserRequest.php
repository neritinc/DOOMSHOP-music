<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        // Kinyerjük az ID-t a /users/{id} útvonalból
        $userId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'string',
                Rule::unique('users')->ignore($userId), // Ne dobjon hibát a saját nevére
            ],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($userId), // Ne dobjon hibát a saját emailjére
            ],
            'password' => 'sometimes|string|min:8',
            'role' => [
                'sometimes',
                'integer',
                'between:1,3',
                function ($attribute, $value, $fail) use ($userId) {
                    $user = Auth::user();
                    if ($user && $user->id == $userId && $value != $user->role) {
                        // A fail-nek átadott kulcs lesz a kapocs a messages-hez
                        // $fail('not_self_role'); 
                        $fail('Saját magad szerepkörét biztonsági okokból nem módosíthatod.'); 
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
          return [
                'name.string'   => 'The name must be a string.',
                'name.unique'   => 'This name is already taken.',

                'email.email'   => 'Please provide a valid email address.',
                'email.unique'  => 'This email is already taken.',
    
                'password.min'  => 'The password must be at least 8 characters.',
    
                'role.integer'  => 'The role must be an integer.',
                'role.between'  => 'The role must be between 1 and 3.',
                ];
    }
}
