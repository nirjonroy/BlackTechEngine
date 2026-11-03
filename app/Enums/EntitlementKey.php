<?php

namespace App\Enums;

enum EntitlementKey: string
{
    case MaximumWorkspaces = 'max_workspaces';
    case MaximumBrands = 'max_brands';
    case ContentStudio = 'content_studio';
    case ContentDraftsPerPeriod = 'content_drafts_per_period';
    case SocialContent = 'social_content';
    case SocialPostsPerPeriod = 'social_posts_per_period';
    case EditorialReview = 'editorial_review';
    case AiGenerationAttempts = 'ai_generation_attempts';
    case AiTokens = 'ai_tokens';

    public function label(): string
    {
        return match ($this) {
            self::MaximumWorkspaces => 'Maximum workspaces',
            self::MaximumBrands => 'Maximum brands per workspace',
            self::ContentStudio => 'Content Studio access',
            self::ContentDraftsPerPeriod => 'Content drafts per billing period',
            self::SocialContent => 'Social Content Management access',
            self::SocialPostsPerPeriod => 'Social posts per billing period',
            self::EditorialReview => 'Editorial Review access',
            self::AiGenerationAttempts => 'AI generation attempts per billing period',
            self::AiTokens => 'AI input and output tokens per billing period',
        };
    }

    public function isFeature(): bool
    {
        return in_array($this, [self::ContentStudio, self::SocialContent, self::EditorialReview], true);
    }
}
