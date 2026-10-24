<?php

namespace App\Http\Requests\Admin;

class UpdateSiteSettingRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:255'],
            'logo_dark' => $this->imageRules(),
            'logo_light' => $this->imageRules(),
            'favicon' => $this->faviconRules(),
        ];
    }
}
