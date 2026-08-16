<?php

declare(strict_types=1);

/**
 * Helpers for presenting monthly subscription content separately from one-off purchases.
 */
function monthly_channel_targets(): array
{
    $settings = settings_get();
    $targets = $settings['catalog_targets'] ?? [];
    return is_array($targets) ? array_values(array_filter($targets, 'is_array')) : [];
}

function monthly_channel_key_from_item(array $item): string
{
    $raw = [];
    if (is_string($item['raw_json'] ?? null) && trim((string)$item['raw_json']) !== '') {
        $decoded = json_decode((string)$item['raw_json'], true);
        if (is_array($decoded)) {
            $raw = $decoded;
        }
    }

    $haystack = mb_strtolower(implode(' ', [
        (string)($item['service_code'] ?? ''),
        (string)($item['floor_code'] ?? ''),
        (string)($item['category_name'] ?? ''),
        (string)($raw['service_code'] ?? ''),
        (string)($raw['service'] ?? ''),
        (string)($raw['floor_code'] ?? ''),
        (string)($raw['floor'] ?? ''),
        (string)($raw['floor_name'] ?? ''),
    ]));

    if (str_contains($haystack, 'vr')) {
        return 'vr';
    }
    if (str_contains($haystack, 'videoc') || str_contains($haystack, 'deluxe') || str_contains($haystack, 'デラックス')) {
        return 'deluxe';
    }
    return 'standard';
}

function monthly_channel_label_from_item(array $item): string
{
    return match (monthly_channel_key_from_item($item)) {
        'vr' => 'VRch',
        'deluxe' => '見放題ch デラックス',
        default => '見放題ch',
    };
}

function monthly_channel_cta_label(array $item): string
{
    return monthly_channel_label_from_item($item) . 'で見る';
}

function monthly_item_affiliate_url(array $item): string
{
    foreach (['affiliate_url', 'url'] as $key) {
        $value = trim((string)($item[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }
    return '';
}
