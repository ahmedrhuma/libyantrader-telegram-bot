<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
use Huemix\LibyanTrader;
use PDO;


/**
 * User "/help" command
 *
 * Command that lists all available commands and displays them in User and Admin sections.
 */
class HelpCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'help';

    /**
     * @var string
     */
    protected $description = 'مُـشاهدة قَـائِـمة المُـساعدة، حيث يمكنك معرفة الأوامر المُـستخدمة وطريقة إستخدامها';

    /**
     * @var string
     */
    protected $usage = '/help أو /help <command>';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $message     = $this->getMessage();
        $chat_id     = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $command_str = trim($message->getText(true));

        // Admin commands shouldn't be shown in group chats
        $safe_to_show = $message->getChat()->isPrivateChat();

        $data = [
            'chat_id'    => $chat_id,
            'parse_mode' => 'markdown',
        ];

        /**
         * FAKE DATABASE CHECK
         */
        
        // IF NOT LOGGED IN
        $AuthorizedData = LibyanTrader::AuthorizedData($this);
        if ($AuthorizedData === false) {
            // CHECK IF USER IS NOT LOGGED IN AND SHOW MINIMAL HELP
            $data['text'] = 'يَـجب عَـليك أولا تسجيل الدخول إلى حِـسابك بإستخدام الأمر /login' . PHP_EOL .
                'ثـم إتباع الخطوات لربط حساب المستثمر الخاص بك بالتيليقرام، ثم يمكنك مشاهدة كـافة الأوامر المستخدمة.';
            return Request::sendMessage($data);
        }
        else if ($AuthorizedData === -1) {
            $data['text'] = LibyanTrader::$CLOSED;
            $data['parse_mode'] = 'MARKDOWN';
            return Request::sendMessage($data);
        }

        list($all_commands, $user_commands, $admin_commands) = $this->getUserAdminCommands();

        // If no command parameter is passed, show the list.
        if ($command_str === '') {
            $data['text'] = '';
            $data['text'] .= '*قَـائِـمة الأوامِـر التـي يمكنك إستخدامها فـي النظام التِـلقـائي لشركة المُتداول اللـيبي*:' . PHP_EOL . PHP_EOL;
            $data['text'] .= '*طَـريقة الإستخدام*' .PHP_EOL . PHP_EOL;
            $data['text'] .= '[] - قُـم بكـتـابة الشرطة المائلة إلى الخلف `\` لتظهر لك قـائمة بكافة الأوامر.' .PHP_EOL;
            $data['text'] .= '[] - يُـمكنك كتابة الأمر كـاملا، أو الضغط على الأمر مـباشرة مِـن القـائمة.' . PHP_EOL;
            $data['text'] .= '[] - يُـمكنك الضـغط عـلى الأمر مبـاشرة من الرسـائل المرسلة مِـني، ستجد الأوامر مظللة بلـون مختلف عن بقية النص، يمكنك الضغط عليها مباشرة للإستمرار.'. PHP_EOL .PHP_EOL;
            $data['text'] .= '*الأوامِـر*'.PHP_EOL;
            foreach ($user_commands as $user_command) {
                if ($user_command->getName() !== 'login')
                    $data['text'] .= '/' . $user_command->getName() . ' - ' . $user_command->getDescription() . PHP_EOL;
            }

            $data['text'] .= PHP_EOL . 'لمشاهدة طَـريقة إستخدام أحد الأوامر يُـرجى كِـتابة /help <الأمر>'. PHP_EOL . 'مِـثال:' . PHP_EOL . '/help summary';

            return Request::sendMessage($data);
        }

        $command_str = str_replace('/', '', $command_str);
        if (isset($all_commands[$command_str]) && ($safe_to_show || !$all_commands[$command_str]->isAdminCommand())) {
            $command      = $all_commands[$command_str];
            $data['text'] = sprintf(
                'الأمر: %s' . PHP_EOL .
                'الوصـف: %s' . PHP_EOL .
                'الإستخدام: %s',
                $command->getName(),
                $command->getDescription(),
                $command->getUsage()
            );

            return Request::sendMessage($data);
        }

        $data['text'] = 'لا يوجد مساعدة متوفرة: الأمر /'. $command_str. ' غير متوفر';

        return Request::sendMessage($data);
    }

    /**
     * Get all available User and Admin commands to display in the help list.
     *
     * @return Command[][]
     */
    protected function getUserAdminCommands()
    {
        // Only get enabled Admin and User commands that are allowed to be shown.
        /** @var Command[] $commands */
        $commands = array_filter($this->telegram->getCommandsList(), function ($command) {
            /** @var Command $command */
            return !$command->isSystemCommand() && $command->showInHelp() && $command->isEnabled();
        });

        $user_commands = array_filter($commands, function ($command) {
            /** @var Command $command */
            return $command->isUserCommand();
        });

        $admin_commands = array_filter($commands, function ($command) {
            /** @var Command $command */
            return $command->isAdminCommand();
        });

        ksort($commands);
        ksort($user_commands);
        ksort($admin_commands);

        return [$commands, $user_commands, $admin_commands];
    }
}
