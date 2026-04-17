<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ImapManager\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version00500Date20260417000000 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options)
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $table_name = 'imap_manager_users';

        if (!$schema->hasTable($table_name)) {
            return null;
        }
        $table = $schema->getTable($table_name);
        $name_column = $table->getColumn('name');
        if ($name_column->getLength() > 255) {
            $name_column->setLength(255);
        }
        if (!$table->hasIndex('imap_mgr_user_name_uq')) {
            $table->addUniqueIndex(['user_id', 'name'], 'imap_mgr_user_name_uq');
        }
        return $schema;
    }
}
