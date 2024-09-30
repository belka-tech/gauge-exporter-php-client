<?php

declare(strict_types=1);

namespace BelkaTech\GaugeExporterClient\Tests;

use BelkaTech\GaugeExporterClient\MetricBag;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MetricBagTest extends TestCase
{
    public function testCannotCreateMetricWithEmptyName(): void
    {
        // Arrange, Act
        $exceptionClass = null;
        try {
            new MetricBag('');
        } catch (Exception $e) {
            $exceptionClass = get_class($e);
        }

        // Assert
        $this->assertSame(InvalidArgumentException::class, $exceptionClass);
    }

    /**
     * @dataProvider invalidMetricsNamesProvider
     */
    public function testCannotCreateMetricWithWrongName(string $metricName): void
    {
        // Arrange, Act
        $exceptionClass = null;
        try {
            new MetricBag($metricName);
        } catch (Exception $e) {
            $exceptionClass = get_class($e);
        }

        // Assert
        $this->assertSame(InvalidArgumentException::class, $exceptionClass);
    }

    public function invalidMetricsNamesProvider(): array
    {
        return [
            ['123abc'],
            ['/abc/'],
            ['-abc'],
            ['abc!'],
        ];
    }

    public function testEmptyMetricWithNoValuesIsValid(): void
    {
        // Arrange, Act
        $metricBag = new MetricBag('metric.name');

        // Assert
        $actual = $this->convertMetricBagToArray($metricBag);
        $this->assertSame([], $actual);
    }

    public function testSetCorrectlyAddsNewMetricValues(): void
    {
        // Arrange
        $metricBag = new MetricBag('metric.name');

        // Act
        $metricBag->set(['label1' => 'value1', 'label2' => 'value2'], 123);
        $metricBag->set(['label1' => 'value3', 'label2' => 'value4'], 123.4);

        // Assert
        $expected = [
            ['labels' => ['label1' => 'value1', 'label2' => 'value2'], 'value' => 123.0],
            ['labels' => ['label1' => 'value3', 'label2' => 'value4'], 'value' => 123.4],
        ];
        $actual = $this->convertMetricBagToArray($metricBag);
        $this->assertSame($expected, $actual);
    }

    public function testSetCorrectlyOverwritesExistingMetricValue(): void
    {
        // Arrange
        $metricBag = new MetricBag('metric.name');
        $metricBag->set(['label1' => 'value1', 'label2' => 'value2'], 123);
        $metricBag->set(['label1' => 'value3', 'label2' => 'value4'], 123.4);

        // Act
        $metricBag->set(['label1' => 'value3', 'label2' => 'value4'], 789);

        // Assert
        $expected = [
            ['labels' => ['label1' => 'value1', 'label2' => 'value2'], 'value' => 123.0],
            ['labels' => ['label1' => 'value3', 'label2' => 'value4'], 'value' => 789.0],
        ];
        $actual = $this->convertMetricBagToArray($metricBag);
        $this->assertSame($expected, $actual);
    }

    public function testIncrementCorrectlyChangesMetricValue(): void
    {
        // Arrange
        $metricBag = new MetricBag('metric.name');
        $metricBag->set(['label1' => 'value1', 'label2' => 'value2'], 123);
        $metricBag->set(['label1' => 'value3', 'label2' => 'value4'], 123.4);
        $metricBag->set(['label1' => 'value5', 'label2' => 'value6'], 123);

        // Act
        $metricBag->increment(['label1' => 'value3', 'label2' => 'value4'], 123.4);

        // Assert
        $expected = [
            ['labels' => ['label1' => 'value1', 'label2' => 'value2'], 'value' => 123.0],
            ['labels' => ['label1' => 'value3', 'label2' => 'value4'], 'value' => 246.8],
            ['labels' => ['label1' => 'value5', 'label2' => 'value6'], 'value' => 123.0],
        ];
        $actual = $this->convertMetricBagToArray($metricBag);
        $this->assertSame($expected, $actual);
    }

    public function testIncrementCorrectlyChangesMissingMetricValue(): void
    {
        // Arrange
        $metricBag = new MetricBag('metric.name');

        // Act
        $metricBag->increment(['label1' => 'value3', 'label2' => 'value4'], 123.4);

        // Assert
        $expected = [
            ['labels' => ['label1' => 'value3', 'label2' => 'value4'], 'value' => 123.4],
        ];
        $actual = $this->convertMetricBagToArray($metricBag);
        $this->assertSame($expected, $actual);
    }

    public function testOnlyAssocLabelsAreAllowedWithinSet(): void
    {
        // Arrange
        $metricBag = new MetricBag('metric.name');
        $exceptionClassForSet = null;
        $exceptionClassForInc = null;

        // Act
        try {
            $metricBag->set(['label1:value1', 'label2:value2'], 123.4);
        } catch (Exception $e) {
            $exceptionClassForSet = get_class($e);
        }
        try {
            $metricBag->increment(['label1:value1', 'label2:value2'], 123.4);
        } catch (Exception $e) {
            $exceptionClassForInc = get_class($e);
        }

        // Assert
        $this->assertSame(InvalidArgumentException::class, $exceptionClassForSet);
        $this->assertSame(InvalidArgumentException::class, $exceptionClassForInc);
    }

    public function testDifferentSortForLabelsAreTreatedTheSame(): void
    {
        // Arrange
        $metricBag = new MetricBag('metric.name');

        // Act
        $metricBag->increment(['label1' => 'value3', 'label2' => 'value4'], 123.4);
        $metricBag->increment(['label2' => 'value4', 'label1' => 'value3'], 123.4);

        $metricBag->set(['label3' => 'value3', 'label4' => 'value4'], 123.4);
        $metricBag->set(['label4' => 'value4', 'label3' => 'value3'], 200);

        // Assert
        $expected = [
            ['labels' => ['label1' => 'value3', 'label2' => 'value4'], 'value' => 246.8],
            ['labels' => ['label3' => 'value3', 'label4' => 'value4'], 'value' => 200.0],
        ];
        $actual = $this->convertMetricBagToArray($metricBag);
        $this->assertSame($expected, $actual);
    }

    public function testEmptyLabelsAreAllowed(): void
    {
        // Arrange
        $metricBag = new MetricBag('metric.name');
        $exceptionClassForSet = null;
        $exceptionClassForInc = null;

        // Act
        $metricBag->set([], 123.4);
        $metricBag->increment([], 123.4);

        // Assert
        $expected = [
            ['labels' => [], 'value' => 246.8],
        ];
        $actual = $this->convertMetricBagToArray($metricBag);
        $this->assertSame($expected, $actual);
    }

    public function testDifferentLabelsCardinalityWithinSameMetricBagIsAllowed(): void
    {
        // Arrange
        $metricBag = new MetricBag('metric.name');

        // Act
        $metricBag->increment(['label1' => 'value3', 'label2' => 'value4'], 123.4);
        $metricBag->increment(['label1' => 'value3', 'label2' => 'value4', 'label3' => 'value5'], 123.4);

        $metricBag->set(['label3' => 'value3', 'label4' => 'value4'], 123.4);
        $metricBag->set(['label3' => 'value3', 'label4' => 'value4', 'label5' => 'value5'], 123.4);

        // Assert
        $expected = [
            ['labels' => ['label1' => 'value3', 'label2' => 'value4'], 'value' => 123.4],
            ['labels' => ['label1' => 'value3', 'label2' => 'value4', 'label3' => 'value5'], 'value' => 123.4],
            ['labels' => ['label3' => 'value3', 'label4' => 'value4'], 'value' => 123.4],
            ['labels' => ['label3' => 'value3', 'label4' => 'value4', 'label5' => 'value5'], 'value' => 123.4],
        ];
        $actual = $this->convertMetricBagToArray($metricBag);
        $this->assertSame($expected, $actual);
    }

    private function convertMetricBagToArray(MetricBag $metricBag): array
    {
        $result = [];
        foreach ($metricBag->getLogLines() as $logLine) {
            $result[] = ['labels' => $logLine->getLabels(), 'value' => $logLine->getValue()];
        }
        return $result;
    }
}
