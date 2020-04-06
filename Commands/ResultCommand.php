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
 * User "/result" command
 *
 * Simple command that returns info about the current user.
 */
class ResultCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'result';

    /**
     * @var string
     */
    protected $description = 'مـشاهدة أرباح/خـسائر هذه السنة حسب الأشهر';

    /**
     * @var string
     */
    protected $usage = '/result يمكنك الضغط عـلى الأمر هٌـنا مباشرة';

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
        $result = LibyanTrader::getResult($AuthorizedUser);
        if ($result === false) {
            $data['text'] = LibyanTrader::$ERROR_TEXT;
            $data['parse_mode'] = 'MARKDOWN';
            return Request::sendMessage($data);
        }

        $months = [
            '1' => 'كانون الثاني/يناير',
            '2' => 'شباط/فبراير',
            '3' => 'آذار/مارس',
            '4' => 'نيسان/أبريل',
            '5' => 'أيار/مايو',
            '6' => 'حزيران/يونيه',
            '7' => 'تموز/يوليه',
            '8' => 'آب/أغسطس',
            '9' => 'أيلول/سبتمبر',
            '10' => 'تشرين الأول/أكتوبر',
            '11' => 'تشرين الثاني/نوفمبر',
            '12' => 'كانون الأول/ديسمبر'
        ];

        $caption = '';
        $arguments = [];

        $caption .= '*نَـتائِـج إسـتثمـارك فـي شركة المتداول اللـيبي حـسب الأشـهر*'. PHP_EOL . PHP_EOL;

        foreach($result as $key => $value) {
            $caption .= '*' . $months[$key] .':* %s$' . PHP_EOL;
            array_push($arguments, number_format($value, 2, '.', ','));
        }

        $caption = sprintf($caption, ...$arguments);

        $data['text'] = $caption;
        $data['parse_mode'] = 'MARKDOWN';

        return Request::sendMessage($data);
    }
}
