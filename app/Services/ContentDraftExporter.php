<?php

namespace App\Services;

use App\Models\AiContentDraft;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContentDraftExporter
{
    /**
     * @return array{contents: string, filename: string, mime: string}
     */
    public function export(AiContentDraft $draft, string $format): array
    {
        $basename = Str::slug($draft->suggested_slug ?: $draft->seo_title ?: $draft->topic) ?: 'approved-content';

        return match ($format) {
            'text' => [
                'contents' => $this->text($draft),
                'filename' => $basename.'.txt',
                'mime' => 'text/plain; charset=UTF-8',
            ],
            'markdown' => [
                'contents' => $this->markdown($draft),
                'filename' => $basename.'.md',
                'mime' => 'text/markdown; charset=UTF-8',
            ],
            'html' => [
                'contents' => $this->html($draft),
                'filename' => $basename.'.html',
                'mime' => 'text/html; charset=UTF-8',
            ],
            default => throw ValidationException::withMessages(['format' => 'Choose a supported export format.']),
        };
    }

    public function previewHtml(AiContentDraft $draft): string
    {
        return (string) Str::markdown($draft->content ?? '', $this->markdownOptions());
    }

    private function markdown(AiContentDraft $draft): string
    {
        $lines = [
            '# '.($draft->seo_title ?: $draft->topic),
            '',
            '**Content type:** '.$draft->content_type->label(),
            '**Brand:** '.$draft->brand->name,
            '**Suggested slug:** '.($draft->suggested_slug ?: 'Not provided'),
            '**Meta description:** '.($draft->meta_description ?: 'Not provided'),
            '',
        ];

        if ($draft->ai_model_id !== null) {
            $lines[] = '> Human verification required: this content originated from an AI-assisted workflow.';
            $lines[] = '';
        }

        $lines[] = $this->safeMarkdown($draft->content ?? '');

        if ($draft->faq_entries !== []) {
            $lines[] = '';
            $lines[] = '## Frequently Asked Questions';

            foreach ($draft->faq_entries as $faq) {
                $lines[] = '';
                $lines[] = '### '.$this->safeMarkdown($faq['question']);
                $lines[] = '';
                $lines[] = $this->safeMarkdown($faq['answer']);
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function text(AiContentDraft $draft): string
    {
        $html = (string) Str::markdown($this->markdown($draft), $this->markdownOptions());
        $text = html_entity_decode(strip_tags(str_replace(
            ['</p>', '</h1>', '</h2>', '</h3>', '</li>', '<br>', '<br />'],
            ["</p>\n\n", "</h1>\n\n", "</h2>\n\n", "</h3>\n", "</li>\n", "\n", "\n"],
            $html,
        )), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text))."\n";
    }

    private function html(AiContentDraft $draft): string
    {
        $title = e($draft->seo_title ?: $draft->topic);
        $description = e($draft->meta_description ?: '');
        $brand = e($draft->brand->name);
        $content = $this->previewHtml($draft);
        $humanNotice = $draft->ai_model_id !== null
            ? '<aside role="note"><strong>Human verification required.</strong> This content originated from an AI-assisted workflow and must be checked before publication.</aside>'
            : '';
        $faq = $this->faqHtml($draft);

        return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{$description}">
    <title>{$title}</title>
</head>
<body>
    <article>
        <header>
            <p>Brand: {$brand}</p>
            <h1>{$title}</h1>
        </header>
        {$humanNotice}
        <div>{$content}</div>
        {$faq}
    </article>
</body>
</html>
HTML;
    }

    private function faqHtml(AiContentDraft $draft): string
    {
        if ($draft->faq_entries === []) {
            return '';
        }

        $entries = collect($draft->faq_entries)->map(function (array $faq): string {
            $question = e(strip_tags($faq['question']));
            $answer = e(strip_tags($faq['answer']));

            return <<<HTML
<section itemprop="mainEntity" itemscope itemtype="https://schema.org/Question">
    <h3 itemprop="name">{$question}</h3>
    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer">
        <p itemprop="text">{$answer}</p>
    </div>
</section>
HTML;
        })->implode("\n");

        return <<<HTML
<section itemscope itemtype="https://schema.org/FAQPage">
    <h2>Frequently Asked Questions</h2>
    {$entries}
</section>
HTML;
    }

    private function safeMarkdown(string $value): string
    {
        return (string) preg_replace(
            '/\]\(\s*(?:javascript|vbscript|file|data):[^)]*\)/iu',
            '](#)',
            strip_tags($value),
        );
    }

    /** @return array{html_input: string, allow_unsafe_links: bool, max_nesting_level: int, max_delimiters_per_line: int} */
    private function markdownOptions(): array
    {
        return [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
            'max_delimiters_per_line' => 1000,
        ];
    }
}
