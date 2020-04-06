<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Written by Marco Boretto <marco.bore@gmail.com>
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Huemix\LibyanTrader;

/**
 * User "/summary" command
 *
 * Simple command that returns info about the current user.
 */
class SummaryCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'summary';

    /**
     * @var string
     */
    protected $description = 'إظـهار بيـانات الحِـساب ومعلـومات الرصـيد';

    /**
     * @var string
     */
    protected $usage = '/summary يمكنك الضغط عـلى الأمر هٌـنا مباشرة';

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

        $AuthorizedUser = LibyanTrader::AuthorizedData($this);

        // if not logged in SHOW HELP
        if ($AuthorizedUser === false) return $this->getTelegram()->executeCommand('help');

        //Send chat action
        Request::sendChatAction([
            'chat_id' => $chat_id,
            'action'  => 'typing',
        ]);

        // FAKE DATABASE QUERY

        $summary = LibyanTrader::getSummary($AuthorizedUser);

        if ($summary === false) {
            $data['text'] = LibyanTrader::$ERROR_TEXT;
            $data['parse_mode'] = 'MARKDOWN';
            return Request::sendMessage($data);
        }

        $caption = sprintf(
            '*بَـيـانات المُـستثمـر*' . PHP_EOL . PHP_EOL.
            '*رقـم الحِـساب:* %d' . PHP_EOL .
            '*إسـم صـاحب الحِـساب:* %s' . PHP_EOL .
            '*الرصـيد:* %s$' . PHP_EOL .
            '*المبـلغ المُـستحق:* %s$'. PHP_EOL. PHP_EOL.
            '*شُــكرا لِـكونك أحد المُـستثمرين فـي شركة المُـتداول الـليبي*', 
            $summary->account,
            $summary->name,
            number_format($summary->balance, 2, '.', ','),
            number_format($summary->b_balance, 2, '.', ',')
        );

        $data['text'] = $caption;
        $data['parse_mode'] = 'MARKDOWN';

        return Request::sendMessage($data);
    }
}
