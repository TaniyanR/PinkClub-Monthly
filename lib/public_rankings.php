<?php

declare(strict_types=1);

/**
 * PinkClub Monthly では公開ランキング機能を使用しません。
 * 既存ページからの互換呼び出しだけを受け、DB集計やキャッシュ更新は行わないようにします。
 */
function pcf_public_ranking_period_start(string $period): string
{
    return date('Y-m-d 00:00:00');
}

function pcf_public_weighted_ranking(string $type, string $period, int $limit = 200, bool $forceRefresh = false): array
{
    return [];
}

function pcf_public_ranking_refresh_queue(): array
{
    return [];
}

function pcf_public_ranking_item_score_sql(): string
{
    return '';
}

function pcf_public_ranking_sql(string $type, string $scoreSql, int $limit): string
{
    return '';
}
