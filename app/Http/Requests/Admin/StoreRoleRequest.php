<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreRoleRequest extends AdminFormRequest
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
                Rule::unique('roles', 'name')->where('guard_name', 'admin'),
            ],
        ];
    }
}
