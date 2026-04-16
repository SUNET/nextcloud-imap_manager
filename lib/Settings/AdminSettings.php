<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2025 Mikael Nordin <kano@sunet.se>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\ImapManager\Settings;

use OCA\ImapManager\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings
{
    public function __construct() {}

    public function getForm(): TemplateResponse
    {
        return new TemplateResponse(Application::APP_ID, 'adminSettings');
    }

    public function getSection(): string
    {
        return 'connected-accounts';
    }

    public function getPriority(): int
    {
        return 10;
    }
}
