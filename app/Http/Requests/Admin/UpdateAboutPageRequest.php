<?php

namespace App\Http\Requests\Admin;

class UpdateAboutPageRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hero_badge' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:5000'],
            'comparison_title' => ['nullable', 'string', 'max:255'],
            'comparison_description' => ['nullable', 'string', 'max:5000'],
            'architecture_badge' => ['nullable', 'string', 'max:255'],
            'architecture_title' => ['nullable', 'string', 'max:255'],
            'architecture_description' => ['nullable', 'string', 'max:5000'],
            'core_values_title' => ['nullable', 'string', 'max:255'],
            'core_values_description' => ['nullable', 'string', 'max:5000'],
            'cta_title' => ['nullable', 'string', 'max:255'],
            'cta_description' => ['nullable', 'string', 'max:5000'],
            'cta_btn1_text' => ['nullable', 'string', 'max:255'],
            'cta_btn1_link' => ['nullable', 'url:http,https', 'max:2048'],
            'cta_btn2_text' => ['nullable', 'string', 'max:255'],
            'cta_btn2_link' => ['nullable', 'url:http,https', 'max:2048'],
            'stats_submitted' => ['sometimes', 'boolean'],
            'stats' => ['nullable', 'array', 'max:50'],
            'stats.*' => ['array:id,value,label'],
            'stats.*.id' => ['nullable', 'integer', 'distinct', 'exists:about_stats,id'],
            'stats.*.value' => ['nullable', 'string', 'max:100', 'required_with:stats.*.label'],
            'stats.*.label' => ['nullable', 'string', 'max:255', 'required_with:stats.*.value'],
            'comparisons_submitted' => ['sometimes', 'boolean'],
            'comparisons' => ['nullable', 'array', 'max:50'],
            'comparisons.*' => ['array:id,capability,traditional_text,bte_text'],
            'comparisons.*.id' => ['nullable', 'integer', 'distinct', 'exists:about_comparisons,id'],
            'comparisons.*.capability' => ['nullable', 'string', 'max:255', 'required_with:comparisons.*.traditional_text,comparisons.*.bte_text'],
            'comparisons.*.traditional_text' => ['nullable', 'string', 'max:255', 'required_with:comparisons.*.capability,comparisons.*.bte_text'],
            'comparisons.*.bte_text' => ['nullable', 'string', 'max:255', 'required_with:comparisons.*.capability,comparisons.*.traditional_text'],
            'architectures_submitted' => ['sometimes', 'boolean'],
            'architectures' => ['nullable', 'array', 'max:50'],
            'architectures.*' => ['array:id,step_number,title,description'],
            'architectures.*.id' => ['nullable', 'integer', 'distinct', 'exists:about_architectures,id'],
            'architectures.*.step_number' => ['nullable', 'integer', 'min:1', 'max:1000', 'required_with:architectures.*.title,architectures.*.description'],
            'architectures.*.title' => ['nullable', 'string', 'max:255', 'required_with:architectures.*.step_number,architectures.*.description'],
            'architectures.*.description' => ['nullable', 'string', 'max:5000', 'required_with:architectures.*.step_number,architectures.*.title'],
            'core_values_submitted' => ['sometimes', 'boolean'],
            'core_values' => ['nullable', 'array', 'max:50'],
            'core_values.*' => ['array:id,icon,title,description'],
            'core_values.*.id' => ['nullable', 'integer', 'distinct', 'exists:about_core_values,id'],
            'core_values.*.icon' => ['nullable', 'string', 'max:255'],
            'core_values.*.title' => ['nullable', 'string', 'max:255', 'required_with:core_values.*.description'],
            'core_values.*.description' => ['nullable', 'string', 'max:5000', 'required_with:core_values.*.title'],
        ];
    }
}
