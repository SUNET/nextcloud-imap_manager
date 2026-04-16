<?php

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
