<?php

namespace Modules\Core\Miscellaneous;

use Core\Request;
use Libs\ApiLib;
use Libs\ApiLog;

class AttachmentController
{
    public function getAttachment(): void
    {
        $request = Request::getBody();

        $domain = !empty($request['src']) ? $request['src'] : '';

        $file = !empty($domain) ? API_PUBLIC_PATH . "/uploads/$domain/{$request['attachment']}" : API_PUBLIC_PATH . "/uploads/{$request['attachment']}";
        $file_log = str_replace(API_PUBLIC_PATH, '', $file);
        ApiLog::sysLog($file_log);

        if (!file_exists($file)) {
            ApiLib::handleResponse('Attachment Not Found', [], 100, __METHOD__);
        }
        echo file_get_contents($file);
    }
}