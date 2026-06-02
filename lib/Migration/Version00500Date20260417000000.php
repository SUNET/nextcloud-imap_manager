<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ImapManager\Migration;

use Closure;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version00500Date20260417000000 extends SimpleMigrationStep
{
    private bool $hasOversize = false;
    private bool $hasDuplicates = false;

    public function __construct(
        private IDBConnection $connection,
    ) {}

    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $tableName = 'imap_manager_users';

        // Inspect the live data only when the table already exists, i.e. on an
        // upgrade. On a fresh install Nextcloud runs migrations in schema-only
        // mode and skips preSchemaChange entirely; the table is empty then, so
        // the optimistic defaults (shrink + unique index) in changeSchema apply.
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $this->hasOversize = $this->hasOversizedNames($tableName);
        $this->hasDuplicates = $this->hasDuplicateUserNames($tableName);
    }

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $tableName = 'imap_manager_users';

        if (!$schema->hasTable($tableName)) {
            return null;
        }

        $table = $schema->getTable($tableName);
        $nameColumn = $table->getColumn('name');

        if ($this->hasOversize) {
            $output->warning(
                'imap_manager: existing rows in imap_manager_users have name > 255 chars; '
                . 'leaving name column at its current length. Clean up manually before a future migration shrinks it.'
            );
        } elseif ($nameColumn->getLength() > 255) {
            $nameColumn->setLength(255);
        }

        if ($this->hasDuplicates) {
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
     * Whether any name exceeds 255 bytes, using a single LIMIT 1 existence
     * query instead of scanning every row.
     */
    private function hasOversizedNames(string $tableName): bool
    {
        $qb = $this->connection->getQueryBuilder();
        $qb->select($qb->createFunction('1'))
            ->from($tableName)
            ->where($qb->expr()->gt(
                $qb->func()->octetLength('name'),
                $qb->createNamedParameter(255, IQueryBuilder::PARAM_INT)
            ))
            ->setMaxResults(1);

        $result = $qb->executeQuery();
        $found = $result->fetchOne() !== false;
        $result->closeCursor();

        return $found;
    }

    /**
     * Whether any (user_id, name) pair occurs more than once, using a grouped
     * LIMIT 1 query instead of building an in-memory set of every row.
     */
    private function hasDuplicateUserNames(string $tableName): bool
    {
        $qb = $this->connection->getQueryBuilder();
        $qb->select('user_id', 'name')
            ->from($tableName)
            ->groupBy('user_id', 'name')
            ->having($qb->expr()->gt(
                $qb->func()->count('user_id'),
                $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)
            ))
            ->setMaxResults(1);

        $result = $qb->executeQuery();
        $found = $result->fetch() !== false;
        $result->closeCursor();

        return $found;
    }
}
