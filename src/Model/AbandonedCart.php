<?php

declare(strict_types=1);

namespace Recover\Model;

defined('ABSPATH') || exit;

/**
 * Abandoned-cart value object.
 */
final class AbandonedCart
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_RECOVERED = 'recovered';

    /**
     * @param array<int, array<string, mixed>> $cartContents
     */
    public function __construct(
        public readonly int $id,
        public readonly string $token,
        public readonly ?string $sessionKey,
        public readonly ?int $userId,
        public readonly ?string $email,
        public readonly array $cartContents,
        public readonly ?string $currency,
        public readonly float $cartTotal,
        public readonly int $itemCount,
        public readonly string $status,
        public readonly bool $consent,
        public readonly int $emailsSent,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $updatedAt,
        public readonly ?\DateTimeImmutable $abandonedAt,
        public readonly ?\DateTimeImmutable $recoveredAt,
        public readonly ?\DateTimeImmutable $lastEmailAt,
    ) {
    }

    /**
     * @param \stdClass $row Database row (wpdb).
     */
    public static function fromRow(\stdClass $row): self
    {
        $contents = [];
        if (! empty($row->cart_contents)) {
            $decoded = json_decode((string) $row->cart_contents, true);
            if (is_array($decoded)) {
                /** @var array<int, array<string, mixed>> $decoded */
                $contents = $decoded;
            }
        }

        return new self(
            id: (int) $row->id,
            token: (string) $row->token,
            sessionKey: ! empty($row->session_key) ? (string) $row->session_key : null,
            userId: ! empty($row->user_id) ? (int) $row->user_id : null,
            email: ! empty($row->email) ? (string) $row->email : null,
            cartContents: $contents,
            currency: ! empty($row->currency) ? (string) $row->currency : null,
            cartTotal: (float) $row->cart_total,
            itemCount: (int) $row->item_count,
            status: (string) $row->status,
            consent: (bool) $row->consent,
            emailsSent: (int) $row->emails_sent,
            createdAt: new \DateTimeImmutable((string) $row->created_at),
            updatedAt: new \DateTimeImmutable((string) $row->updated_at),
            abandonedAt: ! empty($row->abandoned_at) ? new \DateTimeImmutable((string) $row->abandoned_at) : null,
            recoveredAt: ! empty($row->recovered_at) ? new \DateTimeImmutable((string) $row->recovered_at) : null,
            lastEmailAt: ! empty($row->last_email_at) ? new \DateTimeImmutable((string) $row->last_email_at) : null,
        );
    }
}
