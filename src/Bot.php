<?php

namespace KojimifyBot;

use Kojimify\Kojimify;

class Bot
{
    const PARSE_MODE = 'Markdown';
    const RESPONSE_INLINE = 'answerInlineQuery';
    const RESPONSE_SIMPLE = 'sendMessage';

    /**
     * @var string
     */
    protected $token;

    /**
     * @var \Kojimify\Kojimify
     */
    protected $textProcessor;

    /**
     * @var string
     */
    protected $helpText = "Напиши пару слов — и смотри, как они становятся гениальнее в руках истинного Гения.\nДобавь в конце восклицательный знак, чтобы достичь гениальности самого Кодзимы.";

    public function __construct(string $token, string $helpText = null, Kojimify $textProcessor = null)
    {
        if ($textProcessor === null) {
            $textProcessor = new Kojimify();
        }

        $this->token = $token;
        $this->textProcessor = $textProcessor;
        if ($helpText !== null) {
            $this->helpText = $helpText;
        }
    }

    protected function isInline(Payload $payload): bool
    {
        return isset($payload->inline_query);
    }

    protected function getSourceText(Payload $payload): string
    {
        if ($this->isInline($payload)) {
            $sourceText = $payload->inline_query->query;
        } else {
            $sourceText = $payload->message->text;
        }

        $sourceText = mb_strtoupper(trim($sourceText));

        return $sourceText;
    }

    protected function getResponseMethod(Payload $payload): string
    {
        if ($this->isInline($payload)) {
            return self::RESPONSE_INLINE;
        }

        return self::RESPONSE_SIMPLE;
    }

    protected function isHelp(string $sourceText): bool
    {
        return $sourceText === '/HELP' || $sourceText === '/START';
    }

    protected function getResponseText(Payload $payload): string
    {
        $sourceText = $this->getSourceText($payload);

        if ($this->isHelp($sourceText)) {
            $responseText = $this->helpText;
        } else {
            $responseText = sprintf("```\n%s\n```", $this->textProcessor->processText($sourceText));
        }

        return $responseText;
    }

    protected function getResponse(Payload $payload): array
    {
        if ($this->isInline($payload)) {
            return [
                'inline_query_id' => $payload->inline_query->id,
                'results'         => [
                    [
                        'type'                  => 'article',
                        'title'                 => $this->getSourceText($payload),
                        'id'                    => uniqid(),
                        'input_message_content' => [
                            'message_text' => $this->getResponseText($payload),
                            'parse_mode'   => self::PARSE_MODE,
                        ],
                    ],
                ],
            ];
        }

        return [
            'chat_id'    => $payload->message->chat->id,
            'parse_mode' => self::PARSE_MODE,
            'text'       => $this->getResponseText($payload),
        ];
    }

    public function run(Payload $payload): string
    {
        $responseMethod = $this->getResponseMethod($payload);

        $ch = curl_init("https://api.telegram.org/bot{$this->token}/{$responseMethod}");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->getResponse($payload)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $apiCallResult = curl_exec($ch);
        curl_close($ch);

        return $apiCallResult;
    }
}
