<?php

namespace OCA\ImapManager\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection
{
  public function __construct(
    private IURLGenerator $urlGenerator,
    private IL10N $l
  ) {
  }


  public function getID(): string
  {
    return 'imap-manager';
  }
  public function getName(): string
  {
    return $this->l->t('IMAP Manager');
  }

  public function getPriority(): int
  {
    return 90;
  }
  public function getIcon(): string
  {
    return $this->urlGenerator->imagePath('core', 'categories/integration.svg');
  }
}
