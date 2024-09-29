<?php

declare(strict_types=1);

namespace Belkacar\GaugeExporterClient\Tests;

use Belkacar\GaugeExporterClient\Exception\BadResponseException;
use Belkacar\GaugeExporterClient\GaugeExporterClient;
use Belkacar\GaugeExporterClient\MetricBag;
use GuzzleHttp\Psr7\Response;
use Http\Message\RequestMatcher\RequestMatcher;
use Http\Mock\Client as MockClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class GaugeExporterClientTest extends TestCase
{
    public function testGaugeExporterClientHostTrailingSlashTrimmed(): void
    {
        // Arrange
        $psrClientMock = new MockClient();
        $exporterClient = new GaugeExporterClient($psrClientMock, 'https://example.com///');

        // Act
        $exporterClient->send(new MetricBag('metric.name'), 100);

        // Assert
        $expected = 'https://example.com/gauge/metric.name';
        $actual = (string)$psrClientMock->getLastRequest()->getUri();
        $this->assertSame($expected, $actual);
    }

    public function testWholeRequest(): void
    {
        // Arrange
        $psrClientMock = new MockClient();
        $exporterClient = new GaugeExporterClient($psrClientMock, 'https://example.com');

        // Act
        $metricBag = new MetricBag('metric.name');
        $metricBag->set(['key1' => 'b', 'key2' => 'd'], 10);
        $metricBag->set(['key1' => 'b', 'key2' => 'e'], 10);
        $exporterClient->send($metricBag, 100);

        // Assert
        $this->assertSame(
            [
                'method' => 'PUT',
                'url'  => 'https://example.com/gauge/metric.name',
                'data' => '{"ttl":100,"data":[{"labels":{"key1":"b","key2":"d"},"value":10},{"labels":{"key1":"b","key2":"e"},"value":10}]}',
            ],
            $this->simplifyRequest($psrClientMock->getLastRequest()),
        );
    }

    public function testWholeRequestWithEmptyLabelsMetric(): void
    {
        // Arrange
        $psrClientMock = new MockClient();
        $exporterClient = new GaugeExporterClient($psrClientMock, 'https://example.com');

        // Act
        $metricBag = new MetricBag('metric.name');
        $metricBag->set([], 10);
        $exporterClient->send($metricBag, 100);

        // Assert
        $this->assertSame(
            [
                'method' => 'PUT',
                'url'  => 'https://example.com/gauge/metric.name',
                'data' => '{"ttl":100,"data":[{"labels":{},"value":10}]}',
            ],
            $this->simplifyRequest($psrClientMock->getLastRequest()),
        );
    }

    public function testWholeRequestWithEmptyBag(): void
    {
        // Arrange
        $psrClientMock = new MockClient();
        $exporterClient = new GaugeExporterClient($psrClientMock, 'https://example.com');

        // Act
        $metricBag = new MetricBag('metric.name');
        $exporterClient->send($metricBag, 100);

        // Assert
        $this->assertSame(
            [
                'method' => 'PUT',
                'url'  => 'https://example.com/gauge/metric.name',
                'data' => '{"ttl":100,"data":[]}',
            ],
            $this->simplifyRequest($psrClientMock->getLastRequest()),
        );
    }

    public function testWholeRequestWithDefaultLabels(): void
    {
        // Arrange
        $psrClientMock = new MockClient();
        $exporterClient = new GaugeExporterClient(
            $psrClientMock,
            'https://example.com',
            [
                'key2' => 'default_key2',
                'key3' => 'default_key3',
            ]
        );

        // Act
        $metricBag = new MetricBag('metric.name');
        $metricBag->set(['key1' => 'b', 'key2' => 'd'], 10);
        $metricBag->set(['key1' => 'b'], 12);
        $exporterClient->send($metricBag, 100);

        // Assert
        $expectedData = [
            ['labels' => ['key2' => 'd', 'key3' => 'default_key3', 'key1' => 'b'], 'value' => 10],
            ['labels' => ['key2' => 'default_key2', 'key3' => 'default_key3', 'key1' => 'b'], 'value' => 12],
        ];
        $this->assertSame(
            [
                'method' => 'PUT',
                'url'  => 'https://example.com/gauge/metric.name',
                'data' => '{"ttl":100,"data":' . json_encode($expectedData) . '}',
            ],
            $this->simplifyRequest($psrClientMock->getLastRequest()),
        );
    }

    public function testWholeRequestWithEmptyBagAndDefaultLabels(): void
    {
        // Arrange
        $psrClientMock = new MockClient();
        $exporterClient = new GaugeExporterClient(
            $psrClientMock,
            'https://example.com',
            [
                'key2' => 'default_key2',
                'key3' => 'default_key3',
            ]
        );

        // Act
        $metricBag = new MetricBag('metric.name');
        $exporterClient->send($metricBag, 100);

        // Assert
        $this->assertSame(
            [
                'method' => 'PUT',
                'url'  => 'https://example.com/gauge/metric.name',
                'data' => '{"ttl":100,"data":[]}',
            ],
            $this->simplifyRequest($psrClientMock->getLastRequest()),
        );
    }

    public function testGaugeExporterClientThrowsExceptionWhenResponseCodeOtherThan200(): void
    {
        // Arrange
        $psrClientMock = new MockClient();
        $psrClientMock->on(
            new RequestMatcher('gauge/metric.name', 'example.com', ['PUT'], ['https']),
            new Response(500, ['Content-Type' => 'application/json'], '{"error": "Unexpected error"}')
        );
        $exporterClient = new GaugeExporterClient($psrClientMock, 'https://example.com');

        // Act
        $exception = null;
        try {
            $exporterClient->send(new MetricBag('metric.name'), 100);
        } catch (BadResponseException $e) {
            $exception = $e;
        }

        // Assert
        $this->assertSame(BadResponseException::class, get_class($exception));
        $this->assertSame($exception->getResponse()->getBody()->getContents(), '{"error": "Unexpected error"}');
    }

    private function simplifyRequest(RequestInterface $request): array
    {
        return [
            'method' => $request->getMethod(),
            'url' => (string)$request->getUri(),
            'data' => $request->getBody()->getContents(),
        ];
    }
}
