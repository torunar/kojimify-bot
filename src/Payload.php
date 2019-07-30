<?php

namespace KojimifyBot;

/**
 * @property \KojimifyBot\InlineQueryInterface $inline_query
 * @property \KojimifyBot\MessageInterface $message
 */
class Payload
{
    public function __construct($payloadDecoded)
    {
        foreach (get_object_vars($payloadDecoded) as $property => $value) {
            $this->$property = $value;
        }
    }
}
