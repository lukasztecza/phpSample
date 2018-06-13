<?php
namespace PhpSample\Model\Middleware;

use LightApp\Model\Middleware\SimpleOutputMiddleware;
use LightApp\Model\Middleware\MiddlewareInterface;
use PhpSample\Model\Service\FileService;
use LightApp\Model\Service\SessionService;
use LightApp\Model\System\Response;

class OutputMiddleware extends SimpleOutputMiddleware
{
    private const CONTENT_TYPE_STREAM = 'application/octet-stream';

    private $fileService;
    private $assetsVersion;
    private $sessionService;

    public function __construct(
        MiddlewareInterface $next,
        string $defaultContentType,
        FileService $fileService,
        string $assetsVersion,
        SessionService $sessionService
    ) {
        parent::__construct($next, $defaultContentType);
        $this->fileService = $fileService;
        $this->assetsVersion = $assetsVersion;
        $this->sessionService = $sessionService;
    }

    public function handleResponse(Response $response) : Response
    {
        $headers = $response->getHeaders();
        $contentType = $headers['Content-Type'] ?? '';

        switch (true) {
            case $this->fileService->isImageContentType($contentType):
                $this->buildImageResponse($response->getFile(), $response->getVariables(), $headers);
                break;
            case $contentType === self::CONTENT_TYPE_STREAM:
                $this->buildDownloadResponse($response->getFile(), $response->getVariables(), $headers);
                break;
            default:
                parent::handleResponse($response);
        }

        return $response;
    }

    private function buildImageResponse(string $file, array $variables, array $headers) : void
    {
        $path = isset($variables['type']) ? $this->fileService->getUploadPathByType($variables['type']) : null;
        if (empty($path) || !file_exists($path . '/' . $file)) {
            throw new \Exception(
                'Image does not exists or can not be accessed ' . var_export($path . '/' . $file, true) .
                ' for provided variables ' . var_export($variables, true)
            );
        }

        $this->setHeaders($headers);
        readfile($path . '/' . $file);
    }

    private function buildDownloadResponse(string $file, array $variables, array $headers) : void
    {
        $path = isset($variables['type']) ? $this->fileService->getUploadPathByType($variables['type']) : null;
        if (empty($path) || !file_exists($path . '/' . $file)) {
            throw new \Exception(
                'File does not exists or can not be accessed ' . var_export($path . '/' . $file, true) .
                ' for provided variables ' . var_export($variables, true)
            );
        }
        $file = $path . '/' . $file;

        $headers['Content-Description'] = 'File Transfer';
        $headers['Content-Disposition'] = 'attachment; filename="' . basename($file)  . '"';
        $headers['Expires'] = '0';
        $headers['Cache-Control'] = 'must-revalidate';
        $headers['Pragma'] = 'public';
        $headers['Content-Length'] = filesize($file);

        $this->setHeaders($headers);
        readfile($file);
    }

    protected function buildHtmlResponse(string $template, array $variables, array $headers, array $cookies) : void
    {
        $variables['loggedIn'] = $this->sessionService->get(['user'])['user'];
        $variables['assetsVersioning'] = '?v=' . $this->assetsVersion;

        parent::buildHtmlResponse($template, $variables, $headers, $cookies);
    }
}
