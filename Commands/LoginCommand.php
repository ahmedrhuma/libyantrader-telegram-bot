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
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
use Huemix\LibyanTrader;
use PDO;

/**
 * User "/login" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class LoginCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'login';

    /**
     * @var string
     */
    protected $description = 'تسجيل الدخول لحساب المستثمر الخاص بك في شركة المتداول الليبي، لتتمكن من إجراء كافة المعاملات يجب تسجيل دخولك أولا.';

    /**
     * @var string
     */
    protected $usage = '/login يمكنك الضغط عـلى الأمر هٌـنا مباشرة';

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

        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
        ];

        $AuthorizedData = LibyanTrader::AuthorizedData($this);

        if ($AuthorizedData === -1) {
            $data['text'] = LibyanTrader::$CLOSED;
            return Request::sendMessage($data);
        }

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
                $email = $this->test_input($text);
                $VALID_EMAIL = \filter_var($email, FILTER_VALIDATE_EMAIL);
                if ($text === '' || !$VALID_EMAIL) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text']         = 'البريد الإلكتروني:';
                    $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
                    if ($text !== '') {
                        $data['text'] = 'الرجـاء إدخال بريد إلكتروني صـالح';
                    }
                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['email'] = $text;
                $text          = '';

            // no break
            case 1:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = 'رقم الحساب:';
                    $data['reply_markup'] =  Keyboard::forceReply(['selective' => true]);

                    if ($text !== '') {
                        $data['text'] = 'رقم الحساب يجب أن يكون رقم';
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['account'] = $text;
                $text             = '';

            // no break
            case 2:
                if ($text === '') {
                    $notes['state'] = 2;
                    $this->conversation->update();
                    $data['text'] = 'كلمة المرور:';
                    $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['password'] = $text;
                $text         = '';

            // no break
            case 3:
                $this->conversation->update();
                // delete password field from chat
                Request::deleteMessage([
                    'chat_id'    => $chat_id,
                    'message_id' => $message->getMessageId(),
                ]);

                Request::sendChatAction([
                    'chat_id' => $chat_id,
                    'action'  => 'typing',
                ]);

                $loggedin = LibyanTrader::login($notes, $user_id, $chat_id, $user);

                if ($loggedin === false) {
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $data['parse_mode'] = 'markdown';
                    $data['text'] = '*حَـدث خطـأ أثـناء تسـجيل دُخُـولك*' . PHP_EOL
                    . 'يبدو أنك أدخلت بـيانات خـاطئة، الرجـاء المُـحاولة مرة أخرى.';
                    $this->conversation->stop();
                    $result = Request::sendMessage($data);
                    break;
                }

                $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                $data['parse_mode'] = 'markdown';
                $data['text']      = 'مَـرحـبا بـك *'.  $loggedin->name . '* فـي حساب المستثمر رقـم *'.  $loggedin->account .'*' . PHP_EOL
                    . '* يُـمكنك إجراء العديد من العَـمليات بإستخـدام الأوامر هُـنا، إما بكتابتها أو بالضغط عليـها *' . PHP_EOL
                    . 'يُـمكنك أولا البدء بإستخدام أمر المُـساعدة /help لعرض كـافة الخدمـات التي يمكنك القـيام بهـا بشكل سـريع هُـنا.';

                $this->conversation->stop();
                $result = Request::sendMessage($data);
                break;
        }

        return $result;
    }
}
