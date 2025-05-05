# Business Central OAuth2 Client

# Prerequisites
The app must be defined in Entra admin center > Identity > Applications > App registrations:
- Authentication: Web + Redirect URI
- Client secret
- API permissions > Delegated permissions: Financials.ReadWrite.All, user_impersonation

## Authorize user to obtain access token (first time)
```php
require 'vendor/autoload.php';
$oauth = new Rebel\BCOAuth2([
    'tenantId' => 'mydomain.com',
    'clientId' => 'xxxxx-yyyy-zzzz-xxxx-yyyyyyyyyyyy',
    'clientSecret' => '***********************',
    'tokenFile' => __DIR__ . '/tokens.json',
]);

// as defined in App registrations
$redirectUri = 'https://mycompany.com/callback.php';

// Redirect user to login page
if (!isset($_GET['code'])) {
    // crsf protection, save state in a cookie
    $state = bin2hex(random_bytes(16));
    $authUrl = $oauth->getAuthorizationUrl($redirectUri, $state);
    
    header('Location: ' . $authUrl);
    exit;
}

// Get access token from redirect
$accessToken = $oauth->getAccessTokenFromAuthCode($_GET['code'], $redirectUri);
// ...
```

## Get saved access token (with refresh)
```php
require 'vendor/autoload.php';
$oauth = new Rebel\BCOAuth2([
    'tenantId' => 'mydomain.com',
    'clientId' => 'xxxxx-yyyy-zzzz-xxxx-yyyyyyyyyyyy',
    'clientSecret' => '***********************',
    'tokenFile' => __DIR__ . '/tokens.json',
]);

$accessToken = $oauth->getAccessTokenFromFile();
// ...
```

## Use access token - API v2
```php
// ...
$tenantId = $oauth->getTenantId();
$environment = 'production';

// API v2.0:
$apiUrl = "https://api.businesscentral.dynamics.com/v2.0/$tenantId/$environment/api/v2.0";

$client = new GuzzleHttp\Client();
$response = $client->get($apiUrl . '/companies', [
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
        'Accept' => 'application/json'
    ]
]);

$data = json_decode($response->getBody(), true);
if (!isset($data['value'])) {
    throw new \Exception('No data returned from API.');
}

echo "Available companies (API):\n";
foreach ($data['value'] as $company) {
    echo " - {$company['name']}:\t{$company['id']}\n";
}

```

## Use access token - OData v4
```php
// ...
$tenantId = $oauth->getTenantId();
$environment = 'production';

// OData v4:
$oDataUrl = "https://api.businesscentral.dynamics.com/v2.0/$tenantId/$environment/ODataV4";

$client = new GuzzleHttp\Client();
$response = $client->get($oDataUrl . '/Company', [
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
        'Accept' => 'application/json'
    ]
]);

$data = json_decode($response->getBody(), true);
if (!isset($data['value'])) {
    throw new \Exception('No data returned from API.');
}

echo "Available companies (OData):\n";
foreach ($data['value'] as $company) {
    echo " - {$company['Name']}:\t{$company['Id']}\n";
}
```
