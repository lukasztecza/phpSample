<?php
namespace PhpSample\Model\Repository;

use LightApp\Model\Repository\RepositoryAbstract;
use LightApp\Model\Repository\DatabaseConnectionInterface;

class FileRepository extends RepositoryAbstract
{
    private const IMAGE_PUBLIC = 1;
    private const FILE_PUBLIC = 2;
    private const IMAGE_PRIVATE = 3;
    private const FILE_PRIVATE = 4;

    private const IMAGES = '/images';
    private const FILES = '/files';

    private const PUBLIC_UPLOAD_PATH = APP_ROOT_DIR . '/public/upload';
    private const PRIVATE_UPLOAD_PATH = APP_ROOT_DIR . '/private/upload';
    private const UPLOAD_TMP_DIR = APP_ROOT_DIR . '/tmp/upload';

    private const IMAGES_MIME = [
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png" => "image/png",
        "gif" => "image/gif"
    ];

    private const FILES_MIME = [
        "txt" => "text/plain",
        "pdf" => "application/pdf",
        "odt" => "application/vnd.oasis.opendocument.text",
        "ods" => "application/vnd.oasis.opendocument.spreadsheet",
        "odp" => "application/vnd.oasis.opendocument.presentation",
        "doc" => "application/msword",
        "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "xls" => "application/vnd.ms-excel",
        "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "ppt" => "application/vnd.ms-powerpoint",
        "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
        "mp3" => "audio/mpeg",
        "wav" => "audio/x-wav"
    ];

    public function __construct(DatabaseConnectionInterface $write, DatabaseConnectionInterface $read)
    {
        parent::__construct($write, $read);
        if (!file_exists(self::UPLOAD_TMP_DIR)) {
            mkdir(self::UPLOAD_TMP_DIR, 0775, true);
        }
    }

    public function getUploadPathByType(int $type) : string
    {
        switch($type) {
            case self::IMAGE_PUBLIC:
                return self::PUBLIC_UPLOAD_PATH . self::IMAGES;
            case self::FILE_PUBLIC:
                return self::PUBLIC_UPLOAD_PATH . self::FILES;
            case self::IMAGE_PRIVATE:
                return self::PRIVATE_UPLOAD_PATH . self::IMAGES;
            case self::FILE_PRIVATE:
                return self::PRIVATE_UPLOAD_PATH . self::FILES;
            default:
                throw new \Exception('Unsupported file type ' . var_export($type, true));
        }
    }

    public function getSupportedMimeByExtension(string $extension) : string
    {
        switch(true) {
            case isset(self::IMAGES_MIME[$extension]):
                return self::IMAGES_MIME[$extension];
            case isset(self::FILES_MIME[$extension]):
                return self::FILES_MIME[$extension];
            default:
                throw new \Exception('Unsupported file extension ' . var_export($extension, true));
        }
    }

    public function getTypes() : array
    {
        return [
            self::IMAGE_PUBLIC => 'Public images',
            self::FILE_PUBLIC => 'Public files',
            self::IMAGE_PRIVATE => 'Private images',
            self::FILE_PRIVATE => 'Private files'
        ];
    }

    public function isTypeImage(int $type) : bool
    {
        return in_array($type, [self::IMAGE_PUBLIC, self::IMAGE_PRIVATE]);
    }

    public function isTypePrivate(int $type) : bool
    {
        return in_array($type, [self::IMAGE_PRIVATE, self::FILE_PRIVATE]);
    }

    public function isImageMime(string $mime) : bool
    {
        return in_array($mime, self::IMAGES_MIME);
    }

    public function getByType(int $type, int $page, int $perPage) : array
    {
        $files = $this->getRead()->fetch(
            'SELECT id, name, type FROM `file` WHERE `type` = :type LIMIT ' . ($page - 1) * $perPage . ', ' . $perPage, ['type' => $type]
        );
        $pages = $this->getPages('SELECT COUNT(id) AS count FROM `file`', [], $perPage);

        return ['files' => $files, 'page' => $page, 'pages' => $pages];
    }

    public function getByName(string $name) : array
    {
        $files = $this->getRead()->fetch(
            'SELECT id, name, type FROM `file` WHERE `name` = :name', ['name' => $name]
        );

        return $files ?? [];
    }

    public function uploadFiles(array $files, bool $public = false) : array
    {
        try {
            $this->getWrite()->begin();
            $uploadedFiles = [];
            foreach ($files as $key => $file) {
                $uploadedFiles[$key] = $this->uploadFile($file, $public);
                $uploadedFiles[$key]['id'] = $this->saveFile(
                    $uploadedFiles[$key]['name'],
                    $uploadedFiles[$key]['type']
                );
            }
            $this->getWrite()->commit();
        } catch(\Throwable $e) {
            trigger_error(
                'Rolling back after failed attempt to upload files with message ' .
                $e->getMessage() . ' with payload ' . var_export([$files, $public], true),
                E_USER_NOTICE
            );

            foreach ($uploadedFiles as $file) {
                unlink($file['path']);
            }
            $this->getWrite()->rollBack();
            throw $e;
        }
        $this->getWrite()->clean();

        return $uploadedFiles;
    }

    public function deleteFiles(array $ids) : bool
    {
        $params = [];
        $placeholders = $this->getInPlaceholdersAndAddParams($ids, $params);

        $files = $this->getWrite()->fetch('SELECT id, name, type FROM `file` WHERE `id` IN(' . $placeholders . ')', $params);
        if (empty($files)) {
            return false;
        }

        $this->getWrite()->prepare('DELETE FROM `file` WHERE `id` = :id');
        foreach ($files as $file) {
            $this->getWrite()->execute(null, ['id' => $file['id']]);
            unlink($this->getUploadPathByType($file['type']) . '/' . $file['name']);
        }
        $this->getWrite()->clean();

        return true;
    }

    private function uploadFile(array $file, bool $public) : array
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $name = date('YdmHis') . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($file['name'], PATHINFO_FILENAME))) . '.' . $extension;
        $mime = mime_content_type($file["tmp_name"]);
        $supportedMime = $this->getSupportedMimeByExtension($extension);

        switch (true) {
            case getimagesize($file['tmp_name']) && $supportedMime === $mime:
                $type = $public ? self::IMAGE_PUBLIC : self::IMAGE_PRIVATE;
                break;
            case $supportedMime === $mime:
                $type = $public ? self::FILE_PUBLIC : self::FILE_PRIVATE;
                break;
            default:
                throw new \Exception(
                    'Unsupported mime content type ' . var_export($mime, true) .
                    ' or does not match mapped ' . var_export($supportedMime, true) .
                    ' by file extension ' . var_export($extension, true)
                );
        }

        $path = $this->getUploadPathByType($type);

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $path .= '/' . $name;
        if (file_exists($path)) {
            throw new \Exception('File already exists ' . var_export($path, true ));
        }

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \Exception('Failed to move uploaded file to ' . var_export($path, true));
        }

        return [
            'name' => $name,
            'type' => $type,
            'path' => $path
        ];
    }

    private function saveFile(string $name, int $type) : int
    {
        $affectedId = $this->getWrite()->execute(
            'INSERT INTO `file` (`name`, `type`) VALUES (:name, :type)', [
                'name' => $name,
                'type' => $type
            ]
        );

        return (int) $affectedId;
    }
}
