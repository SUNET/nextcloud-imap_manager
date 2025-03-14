<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Micke Nordin <kano@sunet.se>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ImapManager\Db;
use OCA\ImapManager\Db\ImapManager;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;

/**
 * @template-extends QBMapper<ImapManager>
 */
class ImapManagerMapper extends QBMapper {
	public const TABLE_NAME = 'imap_manager_users';
  public const HASH_TYPE = 'sha3-512';

  public function __construct(
    private IQueryBuilder $queryBuilder,
    private ITimeFactory $timeFactory,
    private IUserManager $userManager,
    IDBConnection $db
  ) {
		parent::__construct($db, self::TABLE_NAME);
	}
  public function set(string $userId, string $token, string $name): ImapManager {
    $user = $this->userManager->get($userId);
    if (!$user) {
      throw new \InvalidArgumentException("User $userId not found");
    }
    $email = $user->getPrimaryEMailAddress();
    $imapManager = new ImapManager();
    $now = $this->timeFactory->getDateTime();
    $created = $imapManager->getCreatedAt() ?? $now;
    $salt = bin2hex(random_bytes(16));
    $hash = hash($this::HASH_TYPE, $salt . $token);
    $imapManager->setCreatedAt($created->getTimestamp());
    $imapManager->setUpdatedAt($now->getTimestamp());
    $imapManager->setUserId($userId);
    $imapManager->setEmail($email);
    $imapManager->setName($name);
    $imapManager->setSalt($salt);
    $imapManager->setHash($hash);
    $imapManager->setHashType($this::HASH_TYPE);
    return $this->insertOrUpdate($imapManager);
  }
  public function getPasswordIds(string $userId): array {
    $qb = $this->queryBuilder;
    $qb->select('*')
      ->from($this->tableName)
      ->where('user_id = :userId')
      ->setParameter('userId', $userId);
    $imapManagers = $this->findEntities($qb);

    $passwords = [];
    foreach ($imapManagers as $imapManager) {
      $passwords[] = array('name' => $imapManager->getName(), 'id' => $imapManager->getId());
    }
    return $passwords;
  }
}






