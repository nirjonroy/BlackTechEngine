<?php

namespace App\Http\Requests;

use App\Models\Brand;

class UpdateBrandRequest extends StoreBrandRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $brand = $this->route('brand');

        return $brand instanceof Brand
            && ($this->user()?->can('update', $brand) ?? false);
    }
}
