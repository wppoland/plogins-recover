<?php

declare(strict_types=1);

namespace Recover\Service;

use Recover\Contract\HasHooks;
use Recover\Model\AbandonedCart;
use Recover\Repository\CartRepository;
use WP_User;

defined('ABSPATH') || exit;

/**
 * Personal data exporter and eraser for Recover abandoned carts.
 */
final class RecoverPrivacyService implements HasHooks
{
    private const PAGE_SIZE = 100;

    public function __construct(
        private readonly CartRepository $repository,
    ) {
    }

    public function registerHooks(): void
    {
        add_filter('wp_privacy_personal_data_exporters', [$this, 'registerExporters']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'registerErasers']);
    }

    /**
     * @param array<string, array<string, mixed>> $exporters
     * @return array<string, array<string, mixed>>
     */
    public function registerExporters(array $exporters): array
    {
        $exporters['recover-abandoned-carts'] = [
            'exporter_friendly_name' => __('Abandoned Carts', 'plogins-recover'),
            'callback'               => [$this, 'exportCarts'],
        ];

        return $exporters;
    }

    /**
     * @param array<string, array<string, mixed>> $erasers
     * @return array<string, array<string, mixed>>
     */
    public function registerErasers(array $erasers): array
    {
        $erasers['recover-abandoned-carts'] = [
            'eraser_friendly_name' => __('Abandoned Carts', 'plogins-recover'),
            'callback'             => [$this, 'eraseCarts'],
        ];

        return $erasers;
    }

    /**
     * @return array{data: list<array<string, mixed>>, done: bool}
     */
    public function exportCarts(string $email, int $page = 1): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * self::PAGE_SIZE;

        $items = [];
        $emailRows = $this->repository->findByEmail($email, self::PAGE_SIZE, $offset);

        foreach ($emailRows as $cart) {
            $items[] = $this->formatCart($cart);
        }

        $user = get_user_by('email', $email);
        $userCount = 0;
        if ($user instanceof WP_User) {
            $userRows = $this->repository->findByUser((int) $user->ID, self::PAGE_SIZE, $offset);
            $userCount = count($userRows);
            foreach ($userRows as $cart) {
                if (strtolower((string) $cart->email) === strtolower($email)) {
                    continue;
                }
                $items[] = $this->formatCart($cart);
            }
        }

        $done = count($emailRows) < self::PAGE_SIZE && $userCount < self::PAGE_SIZE;

        return [
            'data' => $items,
            'done' => $done,
        ];
    }

    /**
     * @return array{items_removed: int, items_retained: int, messages: list<string>, done: bool}
     */
    public function eraseCarts(string $email, int $page = 1): array
    {
        $removed = $this->repository->deleteByEmail($email);

        $user = get_user_by('email', $email);
        if ($user instanceof WP_User) {
            $removed += $this->repository->deleteByUser((int) $user->ID);
        }

        return [
            'items_removed'  => $removed,
            'items_retained' => 0,
            'messages'       => [],
            'done'           => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCart(AbandonedCart $cart): array
    {
        return [
            'group_id'    => 'recover-abandoned-carts',
            'group_label' => __('Abandoned Carts', 'plogins-recover'),
            'item_id'     => 'abandoned-cart-' . $cart->id,
            'data'        => [
                ['name' => __('Cart Total', 'plogins-recover'), 'value' => sprintf('%.2f %s', $cart->cartTotal, (string) $cart->currency)],
                ['name' => __('Item Count', 'plogins-recover'), 'value' => (string) $cart->itemCount],
                ['name' => __('Status', 'plogins-recover'), 'value' => $cart->status],
                ['name' => __('Created At', 'plogins-recover'), 'value' => (string) $cart->createdAt],
            ],
        ];
    }
}
