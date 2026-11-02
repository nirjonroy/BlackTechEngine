<?php

namespace App\Http\Requests;

class UpdateSocialPostRequest extends StoreSocialPostRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('socialPost'));
    }

    public function rules(): array
    {
        return ['lock_version' => ['required', 'integer', 'min:0'], ...parent::rules()];
    }

    public function expectedVersion(): int
    {
        return $this->integer('lock_version');
    }
}
