# Burst TransmitSMS API Package for Laravel
This API Package for Laravel User who want to use Australia based sms service provider TransmitSMS gateway
## Installation
```sh
composer require cserobiul/burst-transmitsms-api
```
## Configuration
```sh
No Configuration Need
```

## Use from Controller
Import TransmitsmsAPI class
```php
use Cserobiul\BurstTransmitsmsApi\TransmitsmsAPI;
```

## Publish configuration
```php
php artisan vendor:publish cserobiul/burst-transmitsms-api
```

### Single Number SEND SMS Example
At Controller Method

```php
// set your api_key and api_secret from account settings
$apiKey = 'b84f52054********f789bfeca87c9f6';
$apiSecret = 'b84f5205466579bb********ca87c9f6';

//create an instance
$api = new TransmitsmsAPI($apiKey, $apiSecret);

//write a message (as per your needed)
$message = 'This sms has been sent from Burst TransmitSMS API throught cserobiul/burst-transmitsms-api package.'
$number = '6104****54**';

//message sent  
$result = $api->sendSms($message, $number);

//check message has been sent or not
 if ($result->error->code == 'SUCCESS') 
    echo "Message Sent Successfully";
 } else {
    echo "Error: {$result->error->description}";
 }

```

### Multiple Number SEND SMS Example

```php
//Coming soon next version

```

### For Raw PHP Client

```php
//follow official github docs
https://github.com/transmitsms/transmitsms-api-php-client

```

## Contribution
Anyone can create any Pull request.




