<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ImapManager\Db;

use OCA\ImapManager\Db\SyncManager;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;

/**
 * @template-extends QBMapper<SyncManager>
 */
class SyncManagerMapper extends QBMapper
{
  public const TABLE_NAME = 'imap_manager_syncjobs';

  public function __construct(
    private ITimeFactory $timeFactory,
    private IUserManager $userManager,
    IDBConnection $db
  ) {
    parent::__construct($db, self::TABLE_NAME);
  }

  /**
   * @param int $id
   * @return SyncManager
   * @throws \InvalidArgumentException
   */
  public function get(int $id): SyncManager
  {
    /**
     * @var IQueryBuilder $qb
     */
    $qb = $this->db->getQueryBuilder();
    $query = $qb->select('*')
      ->from(self::TABLE_NAME)
      ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));
    $syncManager = $this->findEntity($query);
    if (!$syncManager) {
      throw new \InvalidArgumentException("SyncManager $id not found");
    }
    return $syncManager;
  }

  /**
   * @param string $userId
   * @param string $frequency
   * @param string $calendar
   * @param string $contacts
   * @param string $email
   * @param string $primary
   * @return SyncManager
   * @throws \InvalidArgumentException
   */
  public function set(string $userId, string $frequency, bool $calendar, bool $contacts, bool $email, string $primary): SyncManager
  {
    $user = $this->userManager->get($userId);
    if (!$user) {
      throw new \InvalidArgumentException("User " . $userId . " not found");
    }
    $source = 'm365';
    $destination = 'sunet';
    if ($primary == 'sunet') {
      $source = 'sunet';
      $destination = 'm365';
    }
    $values = $this->getValues($userId);
    $now = intval($this->timeFactory->getDateTime()->getTimestamp());
    if ($values) {
      $syncManager = $this->get($values['id']);
      $syncManager->setCalendarEnabled(boolval($calendar));
      $syncManager->setContactsEnabled(boolval($contacts));
      $syncManager->setDestination(strval($destination));
      $syncManager->setEmailEnabled(boolval($email));
      $syncManager->setFrequency(strval($frequency));
      $syncManager->setSource(strval($source));
      $syncManager->setUpdatedAt($now);
      $this->update($syncManager);
      if (!$syncManager) {
        throw new \InvalidArgumentException("SyncManager " . $values['id'] . " not found");
      }
    } else {
      $syncManager = new SyncManager();
      $syncManager->setCalendarEnabled(boolval($calendar));
      $syncManager->setContactsEnabled(boolval($contacts));
      $syncManager->setCreatedAt($now);
      $syncManager->setDestination(strval($destination));
      $syncManager->setEmail(strval($user->getPrimaryEMailAddress()));
      $syncManager->setEmailEnabled(boolval($email));
      $syncManager->setFrequency(strval($frequency));
      $syncManager->setSource(strval($source));
      $syncManager->setUpdatedAt($now);
      $syncManager->setUserId(strval($userId));
      $this->insert($syncManager);
    }
    return $syncManager;
  }
  /**
   * Returns the values for the given user
   * @param string $userId
   * @return ?array
   */
  public function getValues(string $userId): ?array
  {
    $qb = $this->db->getQueryBuilder();
    $query = $qb->select('*')
      ->from($this->tableName)
      ->where('user_id = :userId')
      ->setParameter('userId', $userId);
    $result = $query->executeQuery($this->db);
    $row = $result->fetchAll();
    if ($row) {
      $syncManager = $this->mapRowToEntity($row[0]);
      return array(
        'id' => intval($syncManager->getId()),
        'calendar_enabled' => boolval($syncManager->getCalendarEnabled()),
        'contacts_enabled' => boolval($syncManager->getContactsEnabled()),
        'created_at' => $syncManager->getCreatedAt(),
        'destination' => $syncManager->getDestination(),
        'email' => $syncManager->getEmail(),
        'email_enabled' => boolval($syncManager->getEmailEnabled()),
        'frequency' => $syncManager->getFrequency(),
        'source' => $syncManager->getSource(),
        'updated_at' => $syncManager->getUpdatedAt(),
        'user_id' => $syncManager->getUserId(),
      );
    }
    return null;
  }
}
