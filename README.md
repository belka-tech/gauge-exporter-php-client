# Gauge Exporter PHP Client

PHP Client for [Gauge Exporter](https://github.com/belka-tech/gauge-exporter). 


## Usage example
```php
<?php

use BelkaTech\GaugeExporterClient\GaugeExporterClient;
use BelkaTech\GaugeExporterClient\MetricBag;
use GuzzleHttp\Client;

require_once "vendor/autoload.php";

$bag = new MetricBag('metric-name');
$bag->increment(['a' => 'b'], 100);
$bag->increment(['a' => 'b', 'c' => 'd'], 500);

$client = new GaugeExporterClient(new Client(), 'https://127.0.0.1:8181', ['env' => 'prod']);
$client->send($bag, 150);
```
