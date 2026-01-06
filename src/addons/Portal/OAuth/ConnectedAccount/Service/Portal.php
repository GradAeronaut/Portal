<?php

namespace Portal\OAuth\ConnectedAccount\Service;

use XF\ConnectedAccount\Service\AbstractService;

class Portal extends AbstractService
{
    public function getAuthorizationUrl(): string
    {
        return 'https://gradaeronaut.com/oauth/authorize';
    }

    public function getUserInfo(): array
    {
        return [];
    }
}

