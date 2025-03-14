<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2025 Mikael Nordin <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\ImapManager\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'imap_manager';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }
    public function register(IRegistrationContext $context): void
    {
    }
    public function boot(IBootContext $context): void
    {
    }
}
