<?php

namespace OCA\ImapManager\Controller;

use OCA\ImapManager\Db\ImapManager;
use OCA\ImapManager\Db\ImapManagerMapper;
use OCA\ImapManager\Db\SyncManager;
use OCA\ImapManager\Db\SyncManagerMapper;
use OCA\ImapManager\Service\StalwartService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
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
  public function set(): JSONResponse
  {
    $params = $this->request->getParams();
    $name = strval($params['name']);
    $token = strval($params['token']);
    /**
     * @var ImapManager
     */
    $imapManager = $this->imapManagerMapper->set($this->userId, $token, $name);
    $response = true;
    $status = Http::STATUS_OK;
    if (!$imapManager) {
      $response = false;
      $status = Http::STATUS_BAD_GATEWAY;
    }
    return new JSONResponse(array('success' => $response, 'id' => $imapManager->getId()), $status);
  }

  /**
   *
   * @return JSONResponse
   **/
  #[NoAdminRequired]
  public function setSync(): JSONResponse
  {
    $params = $this->request->getParams();
    $frequency = strval($params['frequency']);
    $calendar  = boolval($params['calendar']);
    $contacts  = boolval($params['contacts']);
    $email     = boolval($params['email']);
    $enabled   = boolval($params['enabled']);
    $primary   = strval($params['primary']);
    /**
     * @var SyncManager
     */
    $syncManager = $this->syncManagerMapper->set($this->userId, $frequency, $calendar, $contacts, $email, $primary, $enabled);
    $response = true;
    $status = Http::STATUS_OK;
    if (!$syncManager) {
      $response = false;
      $status = Http::STATUS_BAD_GATEWAY;
    }
    return new JSONResponse(array('success' => $response, 'id' => $syncManager->getId()), $status);
  }

  /**
   *
   * @return JSONResponse
   **/
  #[NoAdminRequired]
  public function delete(int $id): JSONResponse
  {
    $imapManager = $this->imapManagerMapper->get($id);
    if ($imapManager) {
      $this->imapManagerMapper->delete($imapManager);
      return new JSONResponse(array('success' => true), Http::STATUS_OK);
    }
    return new JSONResponse(array('success' => false), Http::STATUS_BAD_GATEWAY);
  }

  /**
   *
   * @return JSONResponse
   **/
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
  public function setConfig(): JSONResponse
  {
    $params = $this->request->getParams();
    $this->appConfig->setValueString('imap_manager', 'stalwart_url', strval($params['stalwart_url'] ?? ''));
    $this->appConfig->setValueString('imap_manager', 'stalwart_admin_user', strval($params['stalwart_admin_user'] ?? ''));
    if (isset($params['stalwart_admin_password']) && $params['stalwart_admin_password'] !== '********') {
      $this->appConfig->setValueString('imap_manager', 'stalwart_admin_password', strval($params['stalwart_admin_password']), sensitive: true);
    }
    $this->appConfig->setValueBool('imap_manager', 'dovecot_enabled', boolval($params['dovecot_enabled'] ?? true));
    $this->appConfig->setValueBool('imap_manager', 'stalwart_enabled', boolval($params['stalwart_enabled'] ?? false));
    return new JSONResponse(['status' => 'success'], Http::STATUS_OK);
  }

  /**
   *
   * @return JSONResponse
   **/
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
    if (str_contains($name, '$')) {
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
  public function deleteStalwart(): JSONResponse
  {
    if (!$this->appConfig->getValueBool('imap_manager', 'stalwart_enabled', false)) {
      return new JSONResponse(['success' => false], Http::STATUS_FORBIDDEN);
    }
    $params = $this->request->getParams();
    $name = strval($params['name'] ?? '');
    $password = strval($params['password'] ?? '');
    $email = $this->getUserEmail();
    try {
      $this->stalwartService->deletePassword($email, $name, $password);
      return new JSONResponse(['success' => true], Http::STATUS_OK);
    } catch (\Exception $e) {
      return new JSONResponse(['success' => false], Http::STATUS_BAD_GATEWAY);
    }
  }
}
