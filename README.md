# iMemento HTTP Service
[![Build Status](https://travis-ci.org/mementohub/http.svg?branch=master)](https://travis-ci.org/mementohub/http)
[![Latest Stable Version](https://poser.pugx.org/imemento/http/v/stable)](https://packagist.org/packages/imemento/http)
[![License](https://poser.pugx.org/imemento/http/license)](https://packagist.org/packages/imemento/http)
[![Total Downloads](https://poser.pugx.org/imemento/http/downloads)](https://packagist.org/packages/imemento/http)

Abstracts the communication between API and clients. 
It is framework independent.

## Install
```bash
composer require imemento/http
```

## Usage

Service.php is an abstract class extended by all the other SDKs and exposes useful REST methods. It manages service and user permissions behind the scenes.

```php
use iMemento\Http\Service;

// Example:
class DestinationsService extends Service
{
	public function getDestinations()
	{
		return $this->_get('/destinations');
	}
}

$config = [
	'service_id' => 'destinations',
	'endpoint' => 'http://api-destinations.dev/api/v1/',
	'host' => 'api-destinations.dev',
];

$destinations = new DestinationsService(Issuer $issuer, array $config);
$destinations->getDestinations(); //returns an array of top destinations
```
