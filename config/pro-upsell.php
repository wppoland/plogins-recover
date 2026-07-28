<?php
/**
 * PRO upsell content, generated from the plogins.com registry by
 * scripts/gen-pro-upsell.mjs. The admin upsell renders this; curate the
 * feature list to fit this plugin's settings screen (do not invent features).
 *
 * @package plogins-recover-pro
 */

defined('ABSPATH') || exit;

return [
    'name'       => 'Recover Pro',
    'url'        => 'https://plogins.com/plogins-recover-pro/pricing/',
    'sellable'   => true,
    'price_from' => 29,
    'currency'   => 'EUR',
    'price_pln'  => 129,
    'lead'       => [
        'en' => 'Recovery coupons, multi-step sequences and conversion analytics. Feature-complete at version 0.3.0.',
        'pl' => 'Kupon odzyskiwania, wieloetapowe sekwencje i analityka konwersji. Feature-complete w wersji 0.3.0.',
    ],
    'features'   => [
        [
            'en' => ['title' => 'Multi-step sequences', 'desc' => 'A timed series of emails with per-step delays, copy overrides and optional coupons on selected steps.'],
            'pl' => ['title' => 'Wieloetapowe sekwencje', 'desc' => 'Zaplanowana seria e-maili z opóźnieniami, własną treścią i opcjonalnym kuponem na wybranych etapach.'],
        ],
        [
            'en' => ['title' => 'Discount codes', 'desc' => 'A unique, expiring coupon in the recovery email to give hesitant shoppers a reason to finish.'],
            'pl' => ['title' => 'Kody rabatowe', 'desc' => 'Unikalny, wygasający kupon w e-mailu odzyskującym, by dać niezdecydowanym powód do zakupu.'],
        ],
        [
            'en' => ['title' => 'Conversion analytics', 'desc' => 'A breakdown of recovered revenue, recovery rate by step and coupon email impact.'],
            'pl' => ['title' => 'Analityka konwersji', 'desc' => 'Rozbicie odzyskanego przychodu, wskaźnika odzysku na etap i wpływu e-maili z kuponem.'],
        ],
    ],
];
