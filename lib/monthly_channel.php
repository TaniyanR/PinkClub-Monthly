<?php

declare(strict_types=1);

function monthly_item_raw(array $item): array
{
    $rawJson = trim((string)($item['raw_json'] ?? ''));
    if ($rawJson === '') {
        return [];
    }
    $decoded = json_decode($rawJson, true);
    return is_array($decoded) ? $decoded : [];
}

function monthly_collect_scalar_text(mixed $value, array &$parts): void
{
    if (is_string($value) || is_numeric($value)) {
        $text = trim((string)$value);
        if ($text !== '') {
            $parts[] = $text;
        }
        return;
    }
    if (!is_array($value)) {
        return;
    }
    foreach ($value as $key => $child) {
        if (is_string($key)) {
            $parts[] = $key;
        }
        monthly_collect_scalar_text($child, $parts);
    }
}

function monthly_item_channel_key(array $item): string
{
    $parts = [];
    monthly_collect_scalar_text(monthly_item_raw($item), $parts);
    foreach (['title', 'category_name'] as $key) {
        $value = trim((string)($item[$key] ?? ''));
        if ($value !== '') {
            $parts[] = $value;
        }
    }
    $haystack = mb_strtolower(implode(' ', $parts));

    if (str_contains($haystack, 'monthly_vr') || preg_match('/(^|[^a-z])vr([^a-z]|$)/i', $haystack) === 1 || str_contains($haystack, 'ｖｒ')) {
        return 'vr';
    }
    if (str_contains($haystack, 'monthly_videoc') || str_contains($haystack, 'デラックス') || str_contains($haystack, 'deluxe')) {
        return 'deluxe';
    }
    return 'standard';
}

function monthly_channel_label(string $key): string
{
    return match ($key) {
        'vr' => 'VRch',
        'deluxe' => '見放題ch デラックス',
        default => '見放題ch',
    };
}

function monthly_item_channel_label(array $item): string
{
    return monthly_channel_label(monthly_item_channel_key($item));
}
