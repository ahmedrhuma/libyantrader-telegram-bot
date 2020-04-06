<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Huemix\LibyanTrader;

/**
 * User "/logout" command
 *
 * Simple command that returns info about the current user.
 */
class LogoutCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'logout';

    /**
     * @var string
     */
    protected $description = 'تسـجيل خروجك من حساب المستثمر';

    /**
     * @var string
     */
    protected $usage = '/logout يمكنك الضغط عـلى الأمر هٌـنا مباشرة';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        $from       = $message->getFrom();
        $user_id    = $from->getId();
        $chat_id    = $message->getChat()->getId();
        $message_id = $message->getMessageId();

        $data = [
            'chat_id'             => $chat_id,
            'reply_to_message_id' => $message_id,
        ];

        if (LibyanTrader::AuthorizedData($this) === -1) {
            $data['text'] = LibyanTrader::$CLOSED;
            $data['parse_mode'] = 'MARKDOWN';
            return Request::sendMessage($data);
        }

        $AuthorizedUser = LibyanTrader::Logout($this);

        $data['text'] = '*شُـكرا لـكونك أحد عُـملاء شـركة المتداول الـليبي*';
        $data['parse_mode'] = 'MARKDOWN';

        return Request::sendMessage($data);
    }
}
