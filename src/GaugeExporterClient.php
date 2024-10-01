<?php

declare(strict_types=1);

namespace BelkaTech\GaugeExporterClient;

use BelkaTech\GaugeExporterClient\Exception\BadResponseException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use GuzzleHttp\Psr7\Request;
use stdClass;

final class GaugeExporterClient
{
    private string $apiDomain;
    private ClientInterface $client;
    private array $systemLabels;

    public function __construct(ClientInterface $client, string $apiDomain, array $systemLabels = [])
    {
        $this->client = $client;
        $this->apiDomain = trim($apiDomain, " \n\r\t\v\0/");
        $this->systemLabels = MetricLine::normalizeLabels($systemLabels);
    }

    /**
     * @throws BadResponseException
     * @throws ClientExceptionInterface
     */
    public function send(MetricBag $metricBag, int $ttlSec): void
    {
        $body = [
            'ttl' => $ttlSec,
            'data' => $this->generateData($metricBag),
            'system_labels' => !empty($this->systemLabels) ? $this->systemLabels : new stdClass(),
        ];
        $request = new Request(
            'PUT',
            sprintf('%s/gauge/%s', $this->apiDomain, $metricBag->getMetricName()),
            ['Content-Type' => 'application/json'],
            json_encode($body),
        );

        $response = $this->client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw new BadResponseException($response);
        }
    }

    private function generateData(MetricBag $metricBag): array
    {
        $result = [];
        foreach ($metricBag->getLogLines() as $logLine) {
            $result[] = [
                'labels' => !empty($logLine->getLabels()) ? $logLine->getLabels() : new stdClass(),
                'value' => $logLine->getValue()
            ];
        }
        return $result;
    }
}
