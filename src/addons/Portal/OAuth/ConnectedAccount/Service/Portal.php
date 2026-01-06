<?php

namespace Portal\OAuth\ConnectedAccount\Service;

use XF\ConnectedAccount\Service\AbstractService;

class Portal extends AbstractService
{
    public function getAuthorizationUrl(): string
    {
        return 'https://example.com/';
    }

    public function getUserInfo(): array
    {
        return [];
    }
}

