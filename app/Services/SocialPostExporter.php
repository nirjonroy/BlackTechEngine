<?php

namespace App\Services;

use App\Models\SocialPost;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SocialPostExporter
{
    public function response(SocialPost $post, string $format): StreamedResponse
    {
        $post->loadMissing(['brand', 'variants']);
        $content = match ($format) {
            'text' => $this->text($post),
            'markdown' => $this->markdown($post),
            'csv' => $this->csv($post),
        };

        return response()->streamDownload(static function () use ($content): void {
            echo $content;
        },
            'social-post-'.$post->getKey().'.'.$format,
            ['Content-Type' => match ($format) {
                'csv' => 'text/csv; charset=UTF-8', 'markdown' => 'text/markdown; charset=UTF-8', default => 'text/plain; charset=UTF-8'
            }],
        );
    }

    private function text(SocialPost $post): string
    {
        return $post->variants->map(fn ($variant) => $variant->platform->label()."\n".$variant->caption."\n".implode(' ', $variant->hashtags ?? [])."\n".collect([$variant->cta_text, $variant->target_url])->filter()->implode(' '))->implode("\n\n---\n\n");
    }

    private function markdown(SocialPost $post): string
    {
        return '# '.$post->title."\n\n".$post->variants->map(fn ($variant) => '## '.$variant->platform->label()."\n\n".$variant->caption."\n\n".implode(' ', $variant->hashtags ?? [])."\n\n".collect([$variant->cta_text, $variant->target_url])->filter()->implode(' '))->implode("\n\n---\n\n");
    }

    private function csv(SocialPost $post): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['platform', 'caption', 'hashtags', 'cta_text', 'target_url', 'media_reference', 'image_alt_text']);
        foreach ($post->variants as $variant) {
            fputcsv($stream, collect([$variant->platform->label(), $variant->caption, implode(' ', $variant->hashtags ?? []), $variant->cta_text, $variant->target_url, $variant->media_reference, $variant->image_alt_text])->map($this->safeCsv(...))->all());
        }
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }

    private function safeCsv(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
