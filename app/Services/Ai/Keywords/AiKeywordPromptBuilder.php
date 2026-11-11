<?php

namespace App\Services\Ai\Keywords;

use App\Models\Brand;
use JsonException;

class AiKeywordPromptBuilder
{
    /** @param array<string, mixed> $input */
    public function build(Brand $brand, array $input): string
    {
        $brandData = [
            'name' => $brand->name,
            'website_url' => $brand->website_url,
            'business_description' => $brand->business_description,
            'industry' => $brand->industry,
            'target_audience' => $brand->target_audience,
            'primary_market' => $brand->primary_market,
            'preferred_language' => $brand->preferred_language,
            'voice_tone' => $brand->voice_tone,
            'seo_topic_categories' => $brand->seo_topic_categories,
        ];

        $dna = $brand->brandDna;
        if ($dna !== null && $dna->is_approved) {
            $brandData['brand_dna'] = [
                'key_services' => $dna->key_services,
                'suggested_keywords' => $dna->suggested_keywords,
                'content_themes' => $dna->content_themes,
                'competitor_differentiation' => $dna->competitor_differentiation,
            ];
        }

        $context = [
            'brand' => $brandData,
            'request' => [
                'seed_keyword' => $input['seed_keyword'],
                'niche' => $input['niche'] ?? $brand->industry,
                'country' => $input['country'] ?? $brand->primary_market,
                'language' => $input['language'] ?? $brand->preferred_language,
            ],
        ];

        try {
            $encodedContext = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new \InvalidArgumentException('The keyword brief could not be encoded safely.');
        }

        return <<<PROMPT
You are an advanced SEO strategist for a programmatic and topical authority SEO studio. Follow these instructions strictly.

APPLICATION RULES:
- Generate 3 to 5 logical topic clusters related to the provided seed keyword and brand context.
- For each cluster, provide a descriptive cluster_name, a high-level pillar_keyword, and 3 to 6 long-tail or secondary keywords.
- Classify the search_intent of each keyword strictly as one of: "informational", "commercial", "transactional", or "navigational".
- NEVER fabricate, invent, or estimate numerical search volume, cost-per-click (CPC), or keyword difficulty (KD) numbers.
- Return ONLY a valid JSON object matching the schema below. Do not wrap in Markdown fences or include commentary.

Required JSON Schema:
{
  "clusters": [
    {
      "cluster_name": "Name of topical cluster",
      "pillar_keyword": "Pillar keyword for this cluster",
      "description": "Brief summary of cluster scope",
      "keywords": [
        {
          "keyword": "Keyword query phrase",
          "search_intent": "informational"
        }
      ]
    }
  ]
}

<CUSTOMER_CONTEXT>
{$encodedContext}
</CUSTOMER_CONTEXT>
PROMPT;
    }
}
