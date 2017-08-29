# iMemento HTTP Service

Abstracts the communication between API and clients. 
It is framework independent.

## Install
```bash
composer require imemento/http
```

## Usage

Service.php is an abstract class extended by all the other SDKs and exposes useful REST methods. It manages API and user permissions behind the scenes.

```php
use iMemento\Http\Service;

class DestinationsService extends Service 
{
	// Example:
	public function getDestinations($url)
	{
		return $this->_get($url);
	}
}

$destinations = new DestinationsService(Issuer $issuer, array $config);
$destinations->getTopDestinations(); //returns an array of top destinations

//TODO: config values
```