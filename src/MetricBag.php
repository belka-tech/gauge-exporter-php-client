<?php

declare(strict_types=1);

namespace BelkaTech\GaugeExporterClient;

use InvalidArgumentException;

final class MetricBag
{
    private const METRIC_NAME_PATTERN = '/^[a-zA-Z_:][a-zA-Z0-9_:.]*$/';

    private string $metric;

    /**
     * @var array<string, float>
     */
    private array $labelsToValuePair;

    public function __construct(string $metricName)
    {
        if (!preg_match(self::METRIC_NAME_PATTERN, $metricName)) {
            throw new InvalidArgumentException('Metric name "' . $metricName . '" does not match ' . self::METRIC_NAME_PATTERN);
        }
        $this->metric = $metricName;
        $this->labelsToValuePair = [];
    }

    public function set(array $labels, float $value): void
    {
        $labels = MetricLine::normalizeLabels($labels);

        $this->labelsToValuePair[json_encode($labels)] = $value;
    }

    public function increment(array $labels, float $value = 1): void
    {
        $labels = MetricLine::normalizeLabels($labels);

        $key = json_encode($labels);
        if (!array_key_exists($key, $this->labelsToValuePair)) {
            $this->labelsToValuePair[$key] = 0;
        }
        $this->labelsToValuePair[$key] += $value;
    }

    public function getMetricName(): string
    {
        return $this->metric;
    }

    /**
     * @return list<MetricLine>
     */
    public function getLogLines(): array
    {
        $result = [];
        foreach ($this->labelsToValuePair as $labels => $value) {
            $result[] = new MetricLine(json_decode($labels, true), $value);
        }
        return $result;
    }
}
