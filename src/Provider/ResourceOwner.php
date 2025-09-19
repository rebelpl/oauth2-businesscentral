<?php
namespace Rebel\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class ResourceOwner implements ResourceOwnerInterface
{
    protected $response;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function getId(): ?string
    {
        return $this->response['id'] ?? null;
    }

    public function getEmail(): ?string
    {
        return $this->response['mail'] ?? $this->response['userPrincipalName'] ?? null;
    }

    public function getName(): ?string
    {
        return $this->response['displayName'] ?? null;
    }

    public function toArray(): array
    {
        return $this->response;
    }
}