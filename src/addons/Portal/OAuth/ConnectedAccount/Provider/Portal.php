<?php

namespace Portal\OAuth\ConnectedAccount\Provider;

use XF\ConnectedAccount\Provider\AbstractProvider;

class Portal extends AbstractProvider
{
    public function getProviderId(): string
    {
        return 'portal';
    }

    public function getTitle(): string
    {
        return 'Portal';
    }

    public function getServiceClass(): string
    {
        return \Portal\OAuth\ConnectedAccount\Service\Portal::class;
    }
}

