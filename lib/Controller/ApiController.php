<?php

namespace OCA\ImapManager\Controller;

use OCA\ImapManager\Db\ImapManager;
use OCA\ImapManager\Db\ImapManagerMapper;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\IRequest;
use Psr\Log\LoggerInterface;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;

class ApiController extends Controller
{
  public function __construct(
    protected $appName,
    private string $userId,
    private ImapManagerMapper $imapManagerMapper,
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
  public function set()
  {
    $params = $this->request->getParams();
    $name = $params['name'];
    $token = $params['token'];
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
    return new JSONResponse(array('success' => $response), $status);
  }
}
