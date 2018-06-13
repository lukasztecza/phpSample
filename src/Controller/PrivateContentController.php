<?php
namespace PhpSample\Controller;

use LightApp\Controller\ControllerInterface;
use LightApp\Model\Service\SessionService;
use PhpSample\Model\Service\FileService;
use LightApp\Model\System\Request;
use LightApp\Model\System\Response;

class PrivateContentController implements ControllerInterface
{
    private $sessionService;
    private $fileService;

    public function __construct(SessionService $sessionService, FileService $fileService)
    {
        $this->sessionService = $sessionService;
        $this->fileService = $fileService;
    }

    public function serve(Request $request) : Response
    {
        $attributes = $request->getAttributes(['directory', 'file']);

        $file = $this->fileService->getByName($attributes['file']);
        if (empty($file[0]['name']) || empty($file[0]['type'])) {
            return $this->codeResponse($request, 404);
        }

        $contentType = $this->fileService->getContentTypeByExtension(pathinfo($file[0]['name'], PATHINFO_EXTENSION));
        if (empty($contentType)) {
            return $this->codeResponse($request, 404);
        }

        if (!empty($file)) {
            return new Response(
                $file[0]['name'],
                ['type' => $file[0]['type']],
                [],
                ['Content-Type' => ($this->fileService->isImageContentType($contentType) ? $contentType : 'application/octet-stream')]
            );
        }
    }
}
