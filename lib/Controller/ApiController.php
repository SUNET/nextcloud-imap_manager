<?php

namespace OCA\ImapManager\Controller;

use OCA\ImapManager\Db\ImapManager;
use OCA\ImapManager\Db\ImapManagerMapper;
use OCA\ImapManager\Db\SyncManager;
use OCA\ImapManager\Db\SyncManagerMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ApiController extends Controller
{
  public function __construct(
    protected $appName,
    private string $userId,
    private ImapManagerMapper $imapManagerMapper,
    private SyncManagerMapper $syncManagerMapper,
    private LoggerInterface $logger,
    IRequest $request,
  ) {
    parent::__construct($appName, $request);
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
    $response = true;
    $status = Http::STATUS_OK;
    return new JSONResponse(array('success' => $response, 'ids' => $ids, 'values' => $values), $status);
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
    $calendar = boolval($params['calendar']);
    $contacts = boolval($params['contacts']);
    $email    = boolval($params['email']);
    $primary  = strval($params['primary']);
    /**
     * @var SyncManager
     */
    $syncManager = $this->syncManagerMapper->set($this->userId, $frequency, $calendar, $contacts, $email, $primary);
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
}
