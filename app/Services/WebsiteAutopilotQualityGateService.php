<?php

namespace App\Services;

class WebsiteAutopilotQualityGateService
{
    public function summarize(array $normalizedPayload, array $validation): array
    {
        $missing = array_values(array_unique(array_map('strval', (array) ($validation['missing'] ?? []))));
        $issues = [];

        if ($this->hasAny($missing, ['hero.hero_headline', 'hero.hero_subhead'])) {
            $issues[] = 'The homepage promise is incomplete.';
        }

        if (in_array('story.about_content', $missing, true)) {
            $issues[] = 'The founder or brand story is missing.';
        }

        if (in_array('catalog.items', $missing, true)) {
            $issues[] = 'No services or products were generated yet.';
        }

        if (in_array('trust.faq_items', $missing, true)) {
            $issues[] = 'The FAQ section is missing.';
        }

        if (in_array('trust.testimonials', $missing, true)) {
            $issues[] = 'The trust layer is too thin because testimonials are missing.';
        }

        if ($this->hasAny($missing, ['blog.blog_title', 'blog.blog_body'])) {
            $issues[] = 'The long-form blog article is missing or incomplete.';
        }

        if (in_array('blog.blog_featured_image', $missing, true)) {
            $issues[] = 'The blog image is missing.';
        }

        if (in_array('media.service_images', $missing, true)) {
            $issues[] = 'The services are missing dedicated images.';
        }

        if (in_array('media.distinct_images', $missing, true)) {
            $issues[] = 'The media pack is too repetitive and needs more distinct images.';
        }

        if (in_array('media.media_assets', $missing, true)) {
            $issues[] = 'The visible website media pack was not generated.';
        }

        if (in_array('contact.contact_email_or_phone', $missing, true)) {
            $issues[] = 'The contact section is missing a reliable way to reach the business.';
        }

        if ($this->hasAny($missing, ['seo.meta_title', 'seo.meta_description'])) {
            $issues[] = 'The SEO metadata is incomplete.';
        }

        if ($issues === []) {
            $issues[] = 'The autopilot build is not complete yet.';
        }

        $summary = 'We could not publish the website yet because ' . strtolower($issues[0]);
        if (count($issues) > 1) {
            $summary .= ' We also found ' . strtolower($issues[1]);
        }

        return [
            'ok' => empty($missing),
            'issues' => $issues,
            'summary' => $summary,
            'missing' => $missing,
            'readiness_score' => $this->readinessScore($missing),
        ];
    }

    private function hasAny(array $missing, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (in_array($needle, $missing, true)) {
                return true;
            }
        }

        return false;
    }

    private function readinessScore(array $missing): int
    {
        $score = 100 - (count($missing) * 8);
        return max(0, min(100, $score));
    }
}
