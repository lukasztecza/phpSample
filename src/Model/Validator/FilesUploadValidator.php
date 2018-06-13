<?php
namespace PhpSample\Model\Validator;

use LightApp\Model\Validator\RequestValidatorAbstract;
use LightApp\Model\System\Request;

class FilesUploadValidator extends RequestValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $files = $request->getFiles();
        if (empty($files['someFile']['name'])) {
            $this->error = 'Needs at least one file';

            return false;
        }

        return true;
    }
}
