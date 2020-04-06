<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;

/**
 * Start command
 *
 * Gets executed when a user first starts using the bot.
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Start command';

    /**
     * @var string
     */
    protected $usage = '/start';

    /**
     * @var string
     */
    protected $version = '1.1.0';

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

        $chat_id = $message->getChat()->getId();
        $text    = '*مَـرحبا بكـ فـي المساعد الشخصي لشركة المٌـتداول الـليبي*' . PHP_EOL . 'يُـمكنني مُـساعدتك فـي معرفة رصيد حسابك، إجراء المعاملات مثل طلب السحب، التحقق من آخر عمليات الإيداع والسحب، التحقق من الأرباح، والمزيد من الإجراءات السريعة يمكنك طلبها مباشرة مـن هُـنا.'. PHP_EOL. 'يُـرجى كـتابة الأمر /help لإظهـار جـميع الأوامر المستخدمة.';

        $data = [
            'chat_id' => $chat_id,
            'parse_mode' => 'markdown',
            'text'    => $text,
        ];

        return Request::sendMessage($data);
    }
}
