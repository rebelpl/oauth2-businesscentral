# Business Central OAuth2 Client

This package provides # Business Central OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Pre-requisites
The app you want to connect to BC must be defined in [Entra admin center](https://entra.microsoft.com/#home) > Identity > Applications > [App registrations:](https://entra.microsoft.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade/quickStartType~/null/sourceType/Microsoft_AAD_IAM)
- Authentication: Web + Redirect URI
- Client secret
- API permissions > Delegated permissions: Financials.ReadWrite.All, user_impersonation

## Installation

To install, use composer:

```
composer require rebelpl/oauth2-businesscentral
```

## Usage

Usage is the same as The League's OAuth client, using `\Rebel\OAuth2\Client\Provider\BusinessCentral` as the provider.

### Authorization Code Grant
```php
$provider = new Rebel\OAuth2\Client\Provider\BusinessCentral([
    // Required
    'tenantId'                  => 'mydomain.com',
    'clientId'                  => 'xxxxx-yyyy-zzzz-xxxx-yyyyyyyyyyyy',
    'clientSecret'              => '*************************',
    'redirectUri'               => 'https://example.com/callback-url',
]);

// For CSRF protection
session_start();

// Handle OAuth error message
if (isset($_GET['error'])) {
    echo "Error: " . $_GET['error'] . "<br />";
    echo "Description: " . $_GET['error_description'] . "<br />";
    exit();
}

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    $authorizationUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
    
    // We have an access token, which we may use in authenticated
    // requests against the Business Central API.
    echo 'Access Token: ' . $token->getToken() . "<br>";
    echo 'Refresh Token: ' . $token->getRefreshToken() . "<br>";
    echo 'Expired in: ' . $token->getExpires() . "<br>";
    echo 'Already expired? ' . ($token->hasExpired() ? 'expired' : 'not expired') . "<br>";
    
    // We might save the token somewhere safe for later use
    $filename = __DIR__ . '/tokens.json';
    file_put_contents($filename, json_encode($token->jsonSerialize(), JSON_PRETTY_PRINT));
}
```

## Refreshing a Token
```php
$provider = new Rebel\OAuth2\Client\Provider\BusinessCentral([
    // Required
    'tenantId'                  => 'mydomain.com',
    'clientId'                  => 'xxxxx-yyyy-zzzz-xxxx-yyyyyyyyyyyy',
    'clientSecret'              => '*************************',
]);

// load existing tokens from storage
$filename = __DIR__ . '/tokens.json';
$token = new League\OAuth2\Client\Token(json_decode(file_get_contents($filename), true));

if ($token->hasExpired()) {
    $token = $provider->getAccessToken('refresh_token', [
        'refresh_token' => $token->getRefreshToken()
    ]);

    // Purge old tokens and store new ones to your data store.
    file_put_contents($filename, json_encode($token->jsonSerialize(), JSON_PRETTY_PRINT));
}
```

## Use access token - API v2
```php
// ...
$tenantId = $provider->getTenantId();
$environment = 'production';

// API v2.0:
$apiUrl = "https://api.businesscentral.dynamics.com/v2.0/$tenantId/$environment/api/v2.0";

$client = new GuzzleHttp\Client();
$response = $client->get($apiUrl . '/companies', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token->getToken(),
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
$tenantId = $provider->getTenantId();
$environment = 'production';

// OData v4:
$oDataUrl = "https://api.businesscentral.dynamics.com/v2.0/$tenantId/$environment/ODataV4";

$client = new GuzzleHttp\Client();
$response = $client->get($oDataUrl . '/Company', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token->getToken(),
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

## Testing
````
./vendor/bin/phpunit
````