<?php

declare(strict_types=1);

namespace BelkaTech\GaugeExporterClient;

use InvalidArgumentException;

final class MetricLine
{
    private array $labels;
    private float $value;

    public function __construct(array $labels, float $value)
    {
        $this->labels = self::normalizeLabels($labels);
        $this->value = $value;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public static function normalizeLabels(array $labels): array
    {
        if (count(array_filter(array_keys($labels), 'is_int')) > 0) {
            throw new InvalidArgumentException('Labels must be specified as associative array');
        }
        asort($labels);

        return $labels;
    }
}
