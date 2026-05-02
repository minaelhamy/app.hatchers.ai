<?php

namespace App\Services;

use App\Models\Founder;

class WebsiteAutopilotMapperService
{
    public function mapWebsiteUpdatePayload(Founder $founder, array $draft, array $websiteBuild = []): array
    {
        $normalized = is_array($draft['normalized_payload'] ?? null) ? $draft['normalized_payload'] : [];
        $identity = is_array($normalized['identity'] ?? null) ? $normalized['identity'] : [];
        $brand = is_array($normalized['brand'] ?? null) ? $normalized['brand'] : [];
        $hero = is_array($normalized['hero'] ?? null) ? $normalized['hero'] : [];
        $story = is_array($normalized['story'] ?? null) ? $normalized['story'] : [];
        $trust = is_array($normalized['trust'] ?? null) ? $normalized['trust'] : [];
        $contact = is_array($normalized['contact'] ?? null) ? $normalized['contact'] : [];
        $seo = is_array($normalized['seo'] ?? null) ? $normalized['seo'] : [];
        $media = is_array($normalized['media'] ?? null) ? $normalized['media'] : [];
        $toggles = is_array($normalized['toggles'] ?? null) ? $normalized['toggles'] : [];

        return [
            'website_engine' => (string) ($identity['website_engine'] ?? $draft['website_engine'] ?? ''),
            'website_mode' => (string) ($identity['website_mode'] ?? $draft['website_mode'] ?? ''),
            'website_title' => (string) ($identity['website_title'] ?? $draft['website_title'] ?? ''),
            'website_path' => (string) ($identity['website_path'] ?? $draft['website_path'] ?? ''),
            'theme_template' => (string) ($identity['theme_template'] ?? $draft['theme_template'] ?? ''),
            'description' => (string) ($brand['brand_description'] ?? ''),
            'meta_title' => (string) ($seo['meta_title'] ?? ''),
            'meta_description' => (string) ($seo['meta_description'] ?? ''),
            'contact_email' => (string) ($contact['contact_email'] ?? $founder->email ?? ''),
            'contact_phone' => (string) ($contact['contact_phone'] ?? $founder->phone ?? ''),
            'business_address' => (string) ($contact['business_address'] ?? ''),
            'business_hours' => (string) ($contact['business_hours'] ?? ''),
            'whatsapp_number' => (string) ($contact['whatsapp_number'] ?? ''),
            'google_review_url' => (string) ($trust['google_review_url'] ?? ''),
            'enable_online_booking' => (bool) ($toggles['enable_online_booking'] ?? false),
            'enable_service_menu' => (bool) ($toggles['enable_service_menu'] ?? false),
            'enable_shop_menu' => (bool) ($toggles['enable_shop_menu'] ?? false),
            'about_content' => (string) ($story['about_content'] ?? ''),
            'faq_items' => array_values(array_filter((array) ($trust['faq_items'] ?? []), fn ($item) => is_array($item))),
            'social_links' => array_values(array_filter((array) ($trust['social_links'] ?? []), fn ($item) => is_array($item))),
            'feature_items' => array_values(array_filter((array) ($trust['feature_items'] ?? []), fn ($item) => is_array($item))),
            'testimonials' => array_values(array_filter((array) ($trust['testimonials'] ?? []), fn ($item) => is_array($item))),
            'story_items' => array_values(array_filter((array) ($story['story_items'] ?? []), fn ($item) => is_array($item))),
            'story_title' => (string) ($story['story_title'] ?? ''),
            'story_subtitle' => (string) ($story['story_subtitle'] ?? ''),
            'story_description' => (string) ($story['story_description'] ?? ''),
            'hero_headline' => (string) ($hero['hero_headline'] ?? ''),
            'hero_subhead' => (string) ($hero['hero_subhead'] ?? ''),
            'hero_brief' => (string) ($hero['hero_brief'] ?? ''),
            'media_assets' => array_values(array_filter((array) ($media['media_assets'] ?? []), fn ($item) => is_array($item))),
            'media_queries' => array_values(array_filter((array) ($media['media_queries'] ?? []), fn ($item) => is_array($item))),
            'custom_domain' => trim((string) ($identity['custom_domain'] ?? $websiteBuild['custom_domain'] ?? '')),
        ];
    }

    public function mapStarterRecordPayload(Founder $founder, array $draft, int $offset = 0, ?array $catalogItem = null): array
    {
        $normalized = is_array($draft['normalized_payload'] ?? null) ? $draft['normalized_payload'] : [];
        $identity = is_array($normalized['identity'] ?? null) ? $normalized['identity'] : [];
        $conversion = is_array($normalized['conversion'] ?? null) ? $normalized['conversion'] : [];
        $media = is_array($normalized['media'] ?? null) ? $normalized['media'] : [];
        $catalog = is_array($normalized['catalog'] ?? null) ? $normalized['catalog'] : [];

        $mode = (string) ($catalog['mode'] ?? $identity['website_mode'] ?? $draft['website_mode'] ?? 'service');
        $starterOffer = is_array($conversion['starter_offer'] ?? null) ? $conversion['starter_offer'] : [];
        $item = is_array($catalogItem) ? $catalogItem : $starterOffer;
        $mediaAssets = array_values(array_filter((array) ($media['media_assets'] ?? []), fn ($asset) => is_array($asset)));

        return [
            'website_engine' => (string) ($identity['website_engine'] ?? $draft['website_engine'] ?? ''),
            'starter_mode' => $mode === 'product' ? 'product' : 'service',
            'starter_title' => trim((string) ($item['title'] ?? '')),
            'starter_description' => trim((string) ($item['description'] ?? '')),
            'starter_price' => trim((string) ($item['price'] ?? '')),
            'media_assets' => $this->recordMediaFromNormalizedAssets($mediaAssets, $offset),
        ];
    }

    public function mapStarterBlogPayload(Founder $founder, array $draft): array
    {
        $normalized = is_array($draft['normalized_payload'] ?? null) ? $draft['normalized_payload'] : [];
        $identity = is_array($normalized['identity'] ?? null) ? $normalized['identity'] : [];
        $blog = is_array($normalized['blog'] ?? null) ? $normalized['blog'] : [];
        $media = is_array($normalized['media'] ?? null) ? $normalized['media'] : [];
        $mediaAssets = array_values(array_filter((array) ($media['media_assets'] ?? []), fn ($asset) => is_array($asset)));

        $featuredImage = trim((string) ($blog['blog_featured_image'] ?? ''));
        if ($featuredImage === '') {
            $featuredImage = trim((string) ($mediaAssets[0]['source_url'] ?? ''));
        }

        return [
            'website_engine' => (string) ($identity['website_engine'] ?? $draft['website_engine'] ?? ''),
            'title' => trim((string) ($blog['blog_title'] ?? '')),
            'description' => trim((string) ($blog['blog_body'] ?? '')),
            'media_assets' => $featuredImage !== '' ? [[
                'target' => 'blog_primary',
                'source_url' => $featuredImage,
            ]] : [],
        ];
    }

    private function recordMediaFromNormalizedAssets(array $mediaAssets, int $offset = 0): array
    {
        $preferredTargets = ['service_primary', 'service_detail', 'service_support', 'hero', 'section_one', 'section_two', 'section_three'];
        $categoryTargets = ['category', 'section_one', 'hero'];

        $serviceAssets = array_values(array_filter($mediaAssets, function (array $asset) use ($preferredTargets): bool {
            return in_array(trim((string) ($asset['target'] ?? '')), $preferredTargets, true)
                && trim((string) ($asset['source_url'] ?? '')) !== '';
        }));

        $slice = array_slice($serviceAssets, $offset, 3);
        if ($slice === []) {
            $slice = array_slice($serviceAssets, 0, 3);
        }

        $categoryAsset = null;
        foreach ($mediaAssets as $asset) {
            if (
                in_array(trim((string) ($asset['target'] ?? '')), $categoryTargets, true)
                && trim((string) ($asset['source_url'] ?? '')) !== ''
            ) {
                $categoryAsset = $asset;
                break;
            }
        }

        $mapped = [];
        if (is_array($categoryAsset)) {
            $mapped[] = [
                'target' => 'category',
                'source_url' => trim((string) ($categoryAsset['source_url'] ?? '')),
            ];
        }

        foreach ($slice as $asset) {
            $mapped[] = [
                'target' => 'service',
                'source_url' => trim((string) ($asset['source_url'] ?? '')),
            ];
        }

        return $mapped;
    }
}
