# Gauge Exporter PHP Client

PHP Client for [Gague Exporter](https://github.com/belka-car/gague-exporter). 


## Usage example
```php
<?php

use Belkacar\GaugeExporterClient\GaugeExporterClient;
use Belkacar\GaugeExporterClient\MetricBag;
use GuzzleHttp\Client;

require_once "vendor/autoload.php";

$bag = new MetricBag('metric-name');
$bag->increment(['a' => 'b'], 100);
$bag->increment(['a' => 'b', 'c' => 'd'], 500);

$client = new GaugeExporterClient(new Client(), 'https://127.0.0.1:8181', ['env' => 'prod']);
$client->send($bag, 150);
```
