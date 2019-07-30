<?php

use KojimifyBot\Bot;
use KojimifyBot\Payload;

require_once __DIR__ . '/vendor/autoload.php';

mb_internal_encoding('UTF-8');

$payloadDecoded = json_decode(file_get_contents('php://input'));
$payloadDecoded or die('Fail');

$token = $_REQUEST['token'] ?? '';

$bot = new Bot($token);

$apiCallResult = $bot->run(new Payload($payloadDecoded));
$apiCallResultDecoded = json_decode($apiCallResult);
if ($apiCallResultDecoded->ok !== true) {
    error_log("{$apiCallResult}\n\n", 3, __DIR__ . '/log.json');
}

exit;
