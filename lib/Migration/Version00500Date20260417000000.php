<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ImapManager\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version00500Date20260417000000 extends SimpleMigrationStep
{
    public function __construct(
        private IDBConnection $connection,
    ) {}

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $table_name = 'imap_manager_users';

        if (!$schema->hasTable($table_name)) {
            return null;
        }

        [$has_oversize, $has_duplicates] = $this->inspectExistingData($table_name);

        $table = $schema->getTable($table_name);
        $name_column = $table->getColumn('name');

        if ($has_oversize) {
            $output->warning(
                'imap_manager: existing rows in imap_manager_users have name > 255 chars; '
                . 'leaving name column at its current length. Clean up manually before a future migration shrinks it.'
            );
        } elseif ($name_column->getLength() > 255) {
            $name_column->setLength(255);
        }

        if ($has_duplicates) {
            $output->warning(
                'imap_manager: duplicate (user_id, name) rows exist in imap_manager_users; '
                . 'skipping unique index creation. Deduplicate manually, then re-run the migration.'
            );
        } elseif (!$table->hasIndex('imap_mgr_user_name_uq')) {
            $table->addUniqueIndex(['user_id', 'name'], 'imap_mgr_user_name_uq');
        }

        return $schema;
    }

    /**
     * @return array{0: bool, 1: bool} [hasOversize, hasDuplicates]
     */
    private function inspectExistingData(string $table_name): array
    {
        $qb = $this->connection->getQueryBuilder();
        $qb->select('user_id', 'name')->from($table_name);
        $result = $qb->executeQuery();

        $has_oversize = false;
        $has_duplicates = false;
        $seen = [];
        while ($row = $result->fetch()) {
            if (strlen((string)$row['name']) > 255) {
                $has_oversize = true;
            }
            $key = $row['user_id'] . "\0" . $row['name'];
            if (isset($seen[$key])) {
                $has_duplicates = true;
            }
            $seen[$key] = true;
        }
        $result->closeCursor();
        return [$has_oversize, $has_duplicates];
    }
}
