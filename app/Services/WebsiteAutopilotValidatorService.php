<?php

namespace App\Services;

class WebsiteAutopilotValidatorService
{
    public function validateNormalizedPayload(array $normalizedPayload): array
    {
        $missing = [];

        $checks = [
            'identity.website_title' => $this->stringValue($normalizedPayload, ['identity', 'website_title']),
            'identity.website_path' => $this->stringValue($normalizedPayload, ['identity', 'website_path']),
            'identity.theme_template' => $this->stringValue($normalizedPayload, ['identity', 'theme_template']),
            'hero.hero_headline' => $this->stringValue($normalizedPayload, ['hero', 'hero_headline']),
            'hero.hero_subhead' => $this->stringValue($normalizedPayload, ['hero', 'hero_subhead']),
            'story.about_content' => $this->stringValue($normalizedPayload, ['story', 'about_content']),
            'contact.contact_email_or_phone' => $this->contactReady($normalizedPayload),
            'seo.meta_title' => $this->stringValue($normalizedPayload, ['seo', 'meta_title']),
            'seo.meta_description' => $this->stringValue($normalizedPayload, ['seo', 'meta_description']),
            'blog.blog_title' => $this->stringValue($normalizedPayload, ['blog', 'blog_title']),
            'blog.blog_body' => $this->stringValue($normalizedPayload, ['blog', 'blog_body']),
            'blog.blog_featured_image' => $this->blogImageReady($normalizedPayload),
        ];

        foreach ($checks as $field => $value) {
            if ($value === '' || $value === false) {
                $missing[] = $field;
            }
        }

        $catalogItems = $this->arrayValue($normalizedPayload, ['catalog', 'items']);
        if (count($catalogItems) < 1) {
            $missing[] = 'catalog.items';
        }

        $faqItems = $this->arrayValue($normalizedPayload, ['trust', 'faq_items']);
        if (count($faqItems) < 1) {
            $missing[] = 'trust.faq_items';
        }

        $testimonialItems = $this->arrayValue($normalizedPayload, ['trust', 'testimonials']);
        if (count($testimonialItems) < 1) {
            $missing[] = 'trust.testimonials';
        }

        if (!$this->mediaReady($normalizedPayload)) {
            $missing[] = 'media.media_assets';
        }

        return [
            'ok' => $missing === [],
            'missing' => $missing,
        ];
    }

    private function stringValue(array $payload, array $path): string
    {
        $value = $payload;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return '';
            }
            $value = $value[$segment];
        }

        return trim((string) $value);
    }

    private function arrayValue(array $payload, array $path): array
    {
        $value = $payload;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return [];
            }
            $value = $value[$segment];
        }

        return is_array($value) ? array_values(array_filter($value, fn ($item) => $item !== null && $item !== '')) : [];
    }

    private function contactReady(array $payload): bool
    {
        $email = $this->stringValue($payload, ['contact', 'contact_email']);
        $phone = $this->stringValue($payload, ['contact', 'contact_phone']);

        return $email !== '' || $phone !== '';
    }

    private function mediaReady(array $payload): bool
    {
        $mediaAssets = $this->arrayValue($payload, ['media', 'media_assets']);
        $mediaQueries = $this->arrayValue($payload, ['media', 'media_queries']);

        return count($mediaAssets) >= 1 || count($mediaQueries) >= 1;
    }

    private function blogImageReady(array $payload): bool
    {
        $featured = $this->stringValue($payload, ['blog', 'blog_featured_image']);
        if ($featured !== '') {
            return true;
        }

        return $this->mediaReady($payload);
    }
}
