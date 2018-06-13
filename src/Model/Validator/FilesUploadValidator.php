<?php
namespace PhpSample\Model\Validator;

use LightApp\Model\Validator\RequestValidatorAbstract;
use LightApp\Model\System\Request;

class FilesUploadValidator extends RequestValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $files = $request->getFiles(['files'])['files'];

        if (empty($files[0]['name'])) {
            $this->error = 'Needs at least one file';

            return false;
        }
        foreach ($files as $file) {
            if (empty($file['size'])) {
                $this->error = 'Wrong file';

                return false;
            }
        }

        return true;
    }
}
