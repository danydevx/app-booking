<?php

namespace Modules\Billing\Support;

final class Plan
{
    /**
     * @param  array<string, int>  $limits
     * @param  array<string, bool>  $features
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $description,
        public readonly ?string $stripePriceId,
        public readonly string $interval,
        public readonly bool $active,
        public readonly array $limits,
        public readonly array $features,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $slug, array $config): self
    {
        return new self(
            slug: $slug,
            name: (string) ($config['name'] ?? ucfirst($slug)),
            description: (string) ($config['description'] ?? ''),
            stripePriceId: isset($config['stripe_price_id']) && $config['stripe_price_id'] !== ''
                ? (string) $config['stripe_price_id']
                : null,
            interval: (string) ($config['interval'] ?? 'month'),
            active: (bool) ($config['active'] ?? true),
            limits: array_map('intval', (array) ($config['limits'] ?? [])),
            features: array_map('boolval', (array) ($config['features'] ?? [])),
        );
    }

    public function isFree(): bool
    {
        return $this->slug === 'free';
    }

    public function limit(string $key): int
    {
        return (int) ($this->limits[$key] ?? 0);
    }

    public function allows(string $feature): bool
    {
        return (bool) ($this->features[$feature] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'interval' => $this->interval,
            'active' => $this->active,
            'is_free' => $this->isFree(),
            'price_configured' => $this->stripePriceId !== null,
            'limits' => $this->limits,
            'features' => $this->features,
        ];
    }
}
