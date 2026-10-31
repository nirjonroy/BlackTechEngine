<?php

namespace App\Enums;

enum AiContentType: string
{
    case SeoBlogArticle = 'seo_blog_article';
    case GeoExplanatoryArticle = 'geo_explanatory_article';
    case FrequentlyAskedQuestions = 'frequently_asked_questions';
    case LandingPage = 'landing_page';

    public function label(): string
    {
        return match ($this) {
            self::SeoBlogArticle => 'SEO blog article',
            self::GeoExplanatoryArticle => 'GEO-focused explanatory article',
            self::FrequentlyAskedQuestions => 'Frequently Asked Questions content',
            self::LandingPage => 'Landing page content',
        };
    }
}
