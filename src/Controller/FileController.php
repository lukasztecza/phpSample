<?php
namespace PhpSample\Controller;

use LightApp\Controller\ControllerAbstract;
use LightApp\Model\System\Request;
use LightApp\Model\System\Response;
use PhpSample\Model\Service\FileService;
use LightApp\Model\Service\SessionService;
use LightApp\Model\Validator\ValidatorFactory;
use PhpSample\Model\Validator\FilesUploadValidator;
use PhpSample\Model\Validator\FilesDeleteValidator;

class FileController extends ControllerAbstract
{
    private $fileService;
    private $sessionService;

    public function __construct(FileService $fileService, ValidatorFactory $validatorFactory, SessionService $sessionService)
    {
        $this->fileService = $fileService;
        $this->validatorFactory = $validatorFactory;
        $this->sessionService = $sessionService;
    }

    public function upload(Request $request) : Response
    {
        $validator = $this->validatorFactory->create(FilesUploadValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $files = $request->getFiles(['files'])['files'];
                $result = $this->fileService->uploadFiles($files, (bool)$request->getPayload(['public'])['public']);
                if (!empty($result)) {
                    $this->sessionService->set(['flash' => ['type' => 'success', 'text' => 'Files are added']]);

                    return $this->redirectResponse('/file');
                }
                $error = 'Failed to upload files';
            }
        }

        return $this->htmlResponse(
            'files/upload.php',
            ['error' => isset($error) ? $error : $validator->getError(), 'csrfToken' => $validator->getCsrfToken()],
            ['error' => 'html']
        );
    }

    public function list(Request $request) : Response
    {
        return $this->htmlResponse(
            'files/list.php',
            ['types' => $this->fileService->getTypes(), 'flash' => $this->sessionService->get(['flash'], true)['flash']],
            ['types' => 'html', 'flash' => 'html']
        );
    }

    public function listPaginated(Request $request) : Response
    {
        $type = $request->getAttributes(['type'])['type'];
        $page = $request->getAttributes(['page'])['page'];

        $validator = $this->validatorFactory->create(FilesDeleteValidator::class);
        if ($request->getMethod() === 'POST') {
            if ($validator->check($request)) {
                $ids = $request->getPayload(['ids'])['ids'];
                if (!empty($ids)) {
                    if ($this->fileService->deleteFiles($ids)) {
                        $this->sessionService->set(['flash' => ['type' => 'success', 'text' => 'Files are deleted']]);
                    } else {
                        $this->sessionService->set(['flash' => ['type' => 'error', 'text' => 'Files are not deleted']]);
                    }

                    return $this->redirectResponse('/file/list/' . (int) $type . '/' . (int) $page);
                }
            }
        }

        $filesPack = $this->fileService->getByType($type, $page);
        if (empty($filesPack['files'])) {
            $this->sessionService->set(['flash' => ['type' => 'error', 'text' => 'There is no files under selected category and page']]);
            return $this->redirectResponse('/file');
        }

        $rules = ['error' => 'html', 'flash' => 'html'];
        foreach ($filesPack['files'] as $key => $file) {
            $rules['files.' . $key . '.name'] = 'file';
        }

        return $this->htmlResponse(
            $this->fileService->isTypeImage($type) ? 'files/listImages.php' : 'files/listFiles.php',
            [
                'files' => $filesPack['files'],
                'type' => $type,
                'page' => $filesPack['page'],
                'pages' => $filesPack['pages'],
                'private' => $this->fileService->isTypePrivate($type),
                'flash' => $this->sessionService->get(['flash'], true)['flash'],
                'error' => isset($error) ? $error : $validator->getError(),
                'csrfToken' => $validator->getCsrfToken()
            ],
            $rules
        );
    }
}
