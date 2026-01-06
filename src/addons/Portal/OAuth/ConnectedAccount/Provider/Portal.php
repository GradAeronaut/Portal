<?php

namespace Portal\OAuth\ConnectedAccount\Provider;

use XF\ConnectedAccount\Provider\AbstractProvider;

class Portal extends AbstractProvider
{
    public function getTitle(): string
    {
        return 'Portal';
    }

    public function getIconClass(): ?string
    {
        return null;
    }

    public function getServiceClass(): string
    {
        return \Portal\OAuth\ConnectedAccount\Service\Portal::class;
    }
}

