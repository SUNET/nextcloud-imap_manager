<?php

declare(strict_types=1);

namespace OCA\ImapManager\Controller;

use OCA\ImapManager\Db\ImapManagerMapper;
use OCA\ImapManager\Db\SyncManagerMapper;
use OCA\ImapManager\Service\StalwartService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AdminRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class ApiController extends Controller
{
  public function __construct(
    protected $appName,
    private string $userId,
    private ImapManagerMapper $imapManagerMapper,
    private SyncManagerMapper $syncManagerMapper,
    private LoggerInterface $logger,
    private IAppConfig $appConfig,
    private StalwartService $stalwartService,
    private IUserManager $userManager,
    IRequest $request,
  ) {
    parent::__construct($appName, $request);
  }

  private function getUserEmail(): string
  {
    $user = $this->userManager->get($this->userId);
    if ($user === null) {
      return $this->userId;
    }
    return $user->getEMailAddress() ?? $this->userId;
  }

  /**
   *
   * @return JSONResponse
   **/
  #[NoAdminRequired]
  public function get(): JSONResponse
  {
    $ids = $this->imapManagerMapper->getIdsAndNames($this->userId);
    $values = $this->syncManagerMapper->getValues($this->userId);
    $dovecotEnabled = $this->appConfig->getValueBool('imap_manager', 'dovecot_enabled', true);
    $stalwartEnabled = $this->appConfig->getValueBool('imap_manager', 'stalwart_enabled', false);
    return new JSONResponse([
      'success' => true,
      'ids' => $ids,
      'values' => $values,
      'dovecot_enabled' => $dovecotEnabled,
      'stalwart_enabled' => $stalwartEnabled,
    ], Http::STATUS_OK);
  }

  /**
   *
   * @return JSONResponse
   **/
  #[NoAdminRequired]
  #[PasswordConfirmationRequired]
  public function set(): JSONResponse
  {
    $params = $this->request->getParams();
    $name = strval($params['name'] ?? '');
    $token = strval($params['token'] ?? '');
    if ($name === '' || $token === '') {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
    }
    try {
      $imapManager = $this->imapManagerMapper->set($this->userId, $token, $name);
    } catch (\InvalidArgumentException $e) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
    }
    return new JSONResponse(['success' => true, 'id' => $imapManager->getId()], Http::STATUS_OK);
  }

  /**
   *
   * @return JSONResponse
   **/
  #[NoAdminRequired]
  public function setSync(): JSONResponse
  {
    $params = $this->request->getParams();
    $frequency = strval($params['frequency'] ?? '');
    if (!in_array($frequency, ['daily', 'hourly', 'minutely'], true)) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
    }
    $primary = strval($params['primary'] ?? '');
    if (!in_array($primary, ['sunet', 'm365'], true)) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
    }
    $calendar  = boolval($params['calendar'] ?? false);
    $contacts  = boolval($params['contacts'] ?? false);
    $email     = boolval($params['email'] ?? false);
    $enabled   = boolval($params['enabled'] ?? false);
    try {
      $syncManager = $this->syncManagerMapper->set($this->userId, $frequency, $calendar, $contacts, $email, $primary, $enabled);
    } catch (\InvalidArgumentException $e) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
    }
    return new JSONResponse(['success' => true, 'id' => $syncManager->getId()], Http::STATUS_OK);
  }

  /**
   *
   * @return JSONResponse
   **/
  #[NoAdminRequired]
  #[PasswordConfirmationRequired]
  public function delete(int $id): JSONResponse
  {
    try {
      $imapManager = $this->imapManagerMapper->get($id);
    } catch (\InvalidArgumentException $e) {
      return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
    }
    if ($imapManager->getUserId() !== $this->userId) {
      return new JSONResponse(['success' => false], Http::STATUS_FORBIDDEN);
    }
    $this->imapManagerMapper->delete($imapManager);
    return new JSONResponse(['success' => true], Http::STATUS_OK);
  }

  /**
   *
   * @return JSONResponse
   **/
  #[AdminRequired]
  public function getConfig(): JSONResponse
  {
    $config = [
      'stalwart_url' => $this->appConfig->getValueString('imap_manager', 'stalwart_url', ''),
      'stalwart_admin_user' => $this->appConfig->getValueString('imap_manager', 'stalwart_admin_user', ''),
      'stalwart_admin_password' => $this->appConfig->getValueString('imap_manager', 'stalwart_admin_password') !== '' ? '********' : '',
      'dovecot_enabled' => $this->appConfig->getValueBool('imap_manager', 'dovecot_enabled', true),
      'stalwart_enabled' => $this->appConfig->getValueBool('imap_manager', 'stalwart_enabled', false),
    ];
    return new JSONResponse($config, Http::STATUS_OK);
  }

  /**
   *
   * @return JSONResponse
   **/
  #[AdminRequired]
  #[PasswordConfirmationRequired]
  public function setConfig(): JSONResponse
  {
    $params = $this->request->getParams();
    $stalwartUrl = strval($params['stalwart_url'] ?? '');
    if ($stalwartUrl !== '' && !$this->isValidStalwartUrl($stalwartUrl)) {
      return new JSONResponse(['success' => false, 'error' => 'invalid_url'], Http::STATUS_BAD_REQUEST);
    }
    $this->appConfig->setValueString('imap_manager', 'stalwart_url', $stalwartUrl);
    $this->appConfig->setValueString('imap_manager', 'stalwart_admin_user', strval($params['stalwart_admin_user'] ?? ''));
    if (isset($params['stalwart_admin_password']) && $params['stalwart_admin_password'] !== '********') {
      $this->appConfig->setValueString('imap_manager', 'stalwart_admin_password', strval($params['stalwart_admin_password']), sensitive: true);
    }
    $this->appConfig->setValueBool('imap_manager', 'dovecot_enabled', boolval($params['dovecot_enabled'] ?? true));
    $this->appConfig->setValueBool('imap_manager', 'stalwart_enabled', boolval($params['stalwart_enabled'] ?? false));
    return new JSONResponse(['status' => 'success'], Http::STATUS_OK);
  }

  private function isValidStalwartUrl(string $url): bool
  {
    $parsed = parse_url($url);
    if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
      return false;
    }
    return in_array(strtolower($parsed['scheme']), ['http', 'https'], true);
  }

  /**
   *
   * @return JSONResponse
   **/
  #[AdminRequired]
  public function testConnection(): JSONResponse
  {
    $success = $this->stalwartService->testConnection();
    return new JSONResponse(
      ['status' => $success ? 'success' : 'error'],
      $success ? Http::STATUS_OK : Http::STATUS_BAD_GATEWAY
    );
  }

  #[NoAdminRequired]
  public function getStalwart(): JSONResponse
  {
    if (!$this->appConfig->getValueBool('imap_manager', 'stalwart_enabled', false)) {
      return new JSONResponse(['success' => false], Http::STATUS_FORBIDDEN);
    }
    $email = $this->getUserEmail();
    try {
      $passwords = $this->stalwartService->listPasswords($email);
      return new JSONResponse(['success' => true, 'passwords' => $passwords], Http::STATUS_OK);
    } catch (\Exception $e) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_GATEWAY);
    }
  }

  #[NoAdminRequired]
  #[PasswordConfirmationRequired]
  public function setStalwart(): JSONResponse
  {
    if (!$this->appConfig->getValueBool('imap_manager', 'stalwart_enabled', false)) {
      return new JSONResponse(['success' => false], Http::STATUS_FORBIDDEN);
    }
    $params = $this->request->getParams();
    $name = strval($params['name'] ?? '');
    $password = strval($params['password'] ?? '');
    if (empty($name) || empty($password)) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
    }
    if (str_contains($name, '$') || str_contains($password, '$')) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
    }
    $email = $this->getUserEmail();
    try {
      $this->stalwartService->createPassword($email, $name, $password);
      return new JSONResponse(['success' => true], Http::STATUS_OK);
    } catch (\Exception $e) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_GATEWAY);
    }
  }

  #[NoAdminRequired]
  #[PasswordConfirmationRequired]
  public function deleteStalwart(): JSONResponse
  {
    if (!$this->appConfig->getValueBool('imap_manager', 'stalwart_enabled', false)) {
      return new JSONResponse(['success' => false], Http::STATUS_FORBIDDEN);
    }
    $params = $this->request->getParams();
    $name = strval($params['name'] ?? '');
    $password = strval($params['password'] ?? '');
    if (empty($name) || empty($password)) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
    }
    if (str_contains($name, '$') || str_contains($password, '$')) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_REQUEST);
    }
    $email = $this->getUserEmail();
    try {
      $this->stalwartService->deletePassword($email, $name, $password);
      return new JSONResponse(['success' => true], Http::STATUS_OK);
    } catch (\Exception $e) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_GATEWAY);
    }
  }
}
