<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ImapManager\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method bool getCalendarEnabled()
 * @method bool getContactsEnabled()
 * @method bool getEmailEnabled()
 * @method int getCreatedAt()
 * @method int getUpdatedAt()
 * @method string getDestination()
 * @method string getEmail()
 * @method string getFrequency()
 * @method string getSource()
 * @method string getUserId()
 * @method void setCalendarEnabled(bool $calendar)
 * @method void setContactsEnabled(bool $contacts)
 * @method void setCreatedAt(int $createdAt)
 * @method void setDestination(string $source)
 * @method void setEmail(string $email)
 * @method void setEmailEnabled(bool $email)
 * @method void setFrequency(string $frequency)
 * @method void setSource(string $source)
 * @method void setUpdatedAt(int $updatedAt)
 * @method void setUserId(string $userId)
 */

class SyncManager extends Entity
{
  /**
   * @var bool $calendarEnabled
   */
  protected $calendarEnabled;
  /**
   * @var bool $contactsEnabled
   */
  protected $contactsEnabled;
  /**
   * @var int $createdAt
   */
  protected $createdAt;
  /**
   * @var string $destination
   */
  protected $destination;
  /**
   * @var string $email
   */
  protected $email;
  /**
   * @var bool $emailEnabled
   */
  protected $emailEnabled;
  /**
   * @var string $frequency
   */
  protected $frequency;
  /**
   * @var string $source
   */
  protected $source;
  /**
   * @var int $updatedAt
   */
  protected $updatedAt;
  /**
   * @var string $userId
   */
  protected $userId;

  public function __construct()
  {
    $this->addType('calendar_enabled', 'boolean');
    $this->addType('contacts_enabled', 'boolean');
    $this->addType('created_at', 'integer');
    $this->addType('destination', 'string');
    $this->addType('email', 'string');
    $this->addType('email_enabled', 'boolean');
    $this->addType('frequency', 'string');
    $this->addType('source', 'string');
    $this->addType('updated_at', 'integer');
    $this->addType('user_id', 'string');
  }
}
