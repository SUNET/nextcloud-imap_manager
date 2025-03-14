<?php

namespace OCA\ImapManager\Controller;

use OCA\ImapManager\Db\ImapManager;
use OCA\ImapManager\Db\ImapManagerMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
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
    public function get(): JSONResponse
    {
        $ids = $this->imapManagerMapper->getIdsAndNames($this->userId);
        $response = true;
        $status = Http::STATUS_OK;
        if (!$ids) {
            $response = false;
            $status = Http::STATUS_BAD_GATEWAY;
        }
        return new JSONResponse(array( 'success' => $response, 'ids' => $ids ), $status);
    }

    /**
     *
     * @return JSONResponse
     **/
    #[NoAdminRequired]
    public function set(): JSONResponse
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
        return new JSONResponse(array('success' => $response, 'id' => $imapManager->getId()), $status);
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
