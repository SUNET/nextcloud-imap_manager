<?php

namespace OCA\ImapManager\Settings;

use OCA\ImapManager\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class PersonalSettings implements ISettings
{
    public function __construct(
    ) {
    }

    /**
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse
    {
        return new TemplateResponse(Application::APP_ID, 'personalSettings');
    }

    public function getSection(): string
    {
        return 'security';
    }

    public function getPriority(): int
    {
        return 10;
    }
}
