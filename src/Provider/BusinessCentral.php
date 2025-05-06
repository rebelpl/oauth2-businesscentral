<?php
namespace Rebel\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class BusinessCentral extends AbstractProvider
{
    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'id';

    protected string $tenantId;

    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->tenantId = $options['tenantId'] ?? 'common';
        parent::__construct($options, $collaborators);
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getAdminConsentUrl(): string
    {
        return "https://login.microsoftonline.com/{$this->tenantId}/adminconsent?"
            . http_build_query([
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUri,
            ]);
    }

    public function getBaseAuthorizationUrl(): string
    {
        return "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/authorize";
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";
    }

    protected function getDefaultScopes(): array
    {
        return [
            'https://api.businesscentral.dynamics.com/user_impersonation offline_access',
        ];
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (isset($data['error'])) {
            throw new IdentityProviderException(
                ($data['error']['message'] ?? $response->getReasonPhrase()),
                $response->getStatusCode(),
                $response
            );
        }
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        // You need to add 'User.Read' to API permissions to be able to run that
        return 'https://graph.microsoft.com/v1.0/me';
    }

    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwner
    {
        return new ResourceOwner($response);
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json'
        ];
    }

    protected function getAuthorizationHeaders($token = null): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
        ];
    }
}
