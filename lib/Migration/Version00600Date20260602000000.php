<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ImapManager\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Flag the Stalwart admin credentials as sensitive.
 *
 * Earlier releases stored stalwart_admin_user and stalwart_admin_password as
 * plain app config values, so they were returned in clear text by the
 * provisioning API (GET config/apps/imap_manager). Marking the keys sensitive
 * encrypts them at rest and makes the provisioning API mask them, without the
 * admin having to re-enter the credentials.
 */
class Version00600Date20260602000000 extends SimpleMigrationStep
{
    public function __construct(
        private IAppConfig $appConfig,
    ) {}

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        foreach (['stalwart_admin_user', 'stalwart_admin_password'] as $key) {
            if (!$this->appConfig->hasKey('imap_manager', $key)) {
                continue;
            }
            if ($this->appConfig->isSensitive('imap_manager', $key)) {
                continue;
            }
            $this->appConfig->updateSensitive('imap_manager', $key, true);
            $output->info('imap_manager: marked ' . $key . ' as sensitive');
        }

        return null;
    }
}
