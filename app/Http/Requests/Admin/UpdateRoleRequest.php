<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateRoleRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'admin')
                    ->ignore($this->route('role')),
            ],
            'permissions' => ['nullable', 'array', 'max:500'],
            'permissions.*' => [
                'string',
                'distinct',
                Rule::exists('permissions', 'name')->where('guard_name', 'admin'),
            ],
        ];
    }
}
