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

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Huemix\LibyanTrader;
use Longman\TelegramBot\DB;
use PDO;

/**
 * User "/contact" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class ContactCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'contact';

    /**
     * @var string
     */
    protected $description = 'التـواصل معنا للإستفسار أو التبليغ عن مشكلة';

    /**
     * @var string
     */
    protected $usage = '/contact يمكنك الضغط عـلى الأمر هٌـنا مباشرة';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Conversation Object
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;

    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {

        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        $AuthorizedUser = LibyanTrader::AuthorizedData($this);

        // if not logged in SHOW HELP
        // if ($AuthorizedUser === false) return $this->getTelegram()->executeCommand('help');

        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $result = Request::emptyResponse();

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === '') {
                    $notes['state'] = 0;
                    // $kb = new Keyboard([
                    //     ['عبـر الموقع الرسمي'],
                    //     ['عبـر التيليقرام'],
                    // ]);
                    $this->conversation->update();

                    $data['text']         = 'كيف تفـضل التَـواصل مَـعنا؟';
                    $data['reply_markup'] = (new Keyboard([
                        'عبـر الموقع الرسمي',
                        'عبـر التيليقرام'
                    ]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);
                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['how'] = $text;
                $text          = '';

            // no break
            case 1:
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();
                    if ($notes['how'] === 'عبـر الموقع الرسمي') {
                        $data['text'] = 'إضـغط هُـنا لزيارة صفحة الإتصال بنـا';
                        $data['reply_markup'] = (new InlineKeyboard([
                            ['text' => 'شَـركة المُـتداول الليبي', 'url' => 'https://libyantrader.net'],
                        ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true);
                        $this->conversation->stop();
                        $result = Request::sendMessage($data);
                        break;
                    }

                    $data['text'] = 'عن ماذا تريد التبليغ؟';
                    $data['reply_markup'] =  (new Keyboard([
                        'مُـشكلة',
                        'إسـتفسار',
                        'شَـكوى أو إقتراح'
                    ]))->setResizeKeyboard(true)->setOneTimeKeyboard(true);
                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['about'] = $text;
                $text             = '';

            // no break
            case 2:
                if ($text === '') {
                    $notes['state'] = 2;
                    $this->conversation->update();
                    $data['text'] = 'أكتب رسـالتك للإدارة';
                    $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['message'] = $text;
                $text         = '';

            // no break
            case 3:
                $this->conversation->update();
                Request::sendChatAction([
                    'chat_id' => $chat_id,
                    'action'  => 'typing',
                ]);
                // DO DATABASE LOGIN HERE
                $result = LibyanTrader::contact(LibyanTrader::AuthorizedData($this), $notes);

                $data['parse_mode'] = 'MARKDOWN';
                if ($summary === false) {
                    $data['text'] = LibyanTrader::$ERROR_TEXT;
                    $result = Request::sendMessage($data);
                }
                else {
                    $data['text'] = '*شُـكرا لـتواصلـك مَـعنا*'. PHP_EOL . PHP_EOL . 'تـم إرسـال رسـالتك بنـجاح.' . PHP_EOL
                    . '*تفـاصيل الرسالة:*' . PHP_EOL . PHP_EOL
                    . '*المَـوضُـوع*: ' . $notes['about'] . PHP_EOL
                    . '*الرسـالة*: ' . $notes['message'];
                }
                $this->conversation->stop();
                $result = Request::sendMessage($data);
                break;
        }

        return $result;
    }
}
