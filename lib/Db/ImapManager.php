<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ImapManager\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method ?int getCreatedAt()
 * @method ?int getUpdatedAt()
 * @method ?string getEmail()
 * @method ?string getUserId()
 * @method string getSalt()
 * @method string getToken()
 * @method void setCreatedAt(int $createdAt)
 * @method void setEmail(string $email)
 * @method void setSalt(string $salt)
 * @method void setToken(string $token)
 * @method void setUpdatedAt(int $updatedAt)
 * @method void setUserId(string $userId)
 */

class ImapManager extends Entity {

	protected $createdAt;
	/**
	* @var $int $updatedAt
	*/
	protected $updatedAt;
	/**
	* @var ?string $email
	*/
	protected $email;
	/**
	* @var string $hash
	*/
	protected $hash;
	/**
	* @var string $hashType
	*/
	protected $hashType;
	/**
	* @var string $name
	*/
	protected $name;
	/**
	* @var string $salt
	*/
	protected $salt;
	/**
	* @var string $userId
	*/
	protected $userId;

  public function __construct(
  ) {
		$this->addType('createdAt', Types::BIGINT);
		$this->addType('email', Types::STRING);
		$this->addType('hash', Types::STRING);
    $this->addType('hashType', Types::STRING);
		$this->addType('name', Types::STRING);
		$this->addType('salt', Types::STRING);
		$this->addType('updatedAt', Types::BIGINT);
		$this->addType('userId', Types::STRING);
  }
}
