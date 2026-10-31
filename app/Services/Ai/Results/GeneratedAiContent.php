<?php

namespace App\Services\Ai\Results;

use App\Enums\AiProviderErrorCategory;
use App\Services\Ai\Exceptions\AiProviderException;

final readonly class GeneratedAiContent
{
    /**
     * @param  list<string>  $outline
     * @param  list<array{question: string, answer: string}>  $faqEntries
     * @param  list<string>  $internalLinkOpportunities
     * @param  list<string>  $externalResearchTopics
     * @param  list<string>  $verificationNotes
     */
    public function __construct(
        public string $seoTitle,
        public string $metaDescription,
        public string $suggestedSlug,
        public array $outline,
        public string $content,
        public array $faqEntries,
        public array $internalLinkOpportunities,
        public array $externalResearchTopics,
        public array $verificationNotes,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            seoTitle: self::string($payload, 'seo_title', 120),
            metaDescription: self::string($payload, 'meta_description', 320),
            suggestedSlug: self::slug($payload),
            outline: self::stringList($payload, 'outline', 40, 240),
            content: self::string($payload, 'content', 100000),
            faqEntries: self::faqEntries($payload),
            internalLinkOpportunities: self::stringList($payload, 'internal_link_opportunities', 30, 500),
            externalResearchTopics: self::stringList($payload, 'external_research_topics', 30, 500),
            verificationNotes: self::stringList($payload, 'verification_notes', 30, 1000),
        );
    }

    /** @param array<string, mixed> $payload */
    private static function string(array $payload, string $key, int $maximumLength): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || trim($value) === '' || mb_strlen($value) > $maximumLength || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
            self::malformed();
        }

        return trim($value);
    }

    /** @param array<string, mixed> $payload */
    private static function slug(array $payload): string
    {
        $slug = self::string($payload, 'suggested_slug', 191);

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug) !== 1) {
            self::malformed();
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private static function stringList(array $payload, string $key, int $maximumItems, int $maximumLength): array
    {
        $values = $payload[$key] ?? null;

        if (! is_array($values) || ! array_is_list($values) || count($values) > $maximumItems) {
            self::malformed();
        }

        $normalized = [];

        foreach ($values as $value) {
            if (! is_string($value) || trim($value) === '' || mb_strlen($value) > $maximumLength) {
                self::malformed();
            }

            $normalized[] = trim($value);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{question: string, answer: string}>
     */
    private static function faqEntries(array $payload): array
    {
        $entries = $payload['faq_entries'] ?? null;

        if (! is_array($entries) || ! array_is_list($entries) || count($entries) > 30) {
            self::malformed();
        }

        $normalized = [];

        foreach ($entries as $entry) {
            if (! is_array($entry) || array_is_list($entry)) {
                self::malformed();
            }

            $normalized[] = [
                'question' => self::string($entry, 'question', 500),
                'answer' => self::string($entry, 'answer', 2000),
            ];
        }

        return $normalized;
    }

    private static function malformed(): never
    {
        throw new AiProviderException(
            AiProviderErrorCategory::MalformedResponse,
            'Gemini returned an invalid structured content response.',
        );
    }
}
