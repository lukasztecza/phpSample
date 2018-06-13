<?php
namespace PhpSample\Model\Validator;

use LightApp\Model\System\Request;
use LightApp\Model\Validator\RequestValidatorAbstract;

class LoginValidator extends RequestValidatorAbstract
{
    public function validate(Request $request) : bool
    {
        $payload = $request->getPayload(['username', 'password']);
        if (empty($payload['username']) || empty($payload['password'])) {
            $this->error = 'Fields username and password can not be empty';

            return false;
        }

        return true;
    }
}
