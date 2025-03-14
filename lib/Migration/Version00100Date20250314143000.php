<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ImapManager\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version00100Date20250314143000 extends SimpleMigrationStep
{
    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options)
    {
        /** @var ISchemaWrapper $schema */


        $schema = $schemaClosure();
        $table_name = 'imap_manager_users';

        if (! $schema->hasTable($table_name)) {
            $table = $schema->createTable($table_name);
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 11,
                'unsigned' => true,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,

            ]);
            // https://www.directedignorance.com/blog/maximum-length-of-email-address
            $table->addColumn('email', Types::STRING, [
                'notnull' => false,
                'length' => 320,
            ]);
            $table->addColumn('hash', Types::STRING, [
                'notnull' => true,
                'length' => 1024,
            ]);
            $table->addColumn('hash_type', Types::STRING, [
                'notnull' => true,
                'length' => 256,
            ]);
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 1024,
            ]);
            $table->addColumn('salt', Types::STRING, [
                'notnull' => true,
                'length' => 1024,
            ]);
            $table->addColumn('created_at', Types::BIGINT, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::BIGINT, [
                'notnull' => false,
            ]);


            $table->setPrimaryKey(['id']);
            return $schema;
        }

        return null;

    }
}
