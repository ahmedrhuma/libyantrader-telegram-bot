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
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;
use Huemix\LibyanTrader;

/**
 * User "/transfer" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class TransferCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'transfer';

    /**
     * @var string
     */
    protected $description = 'طلب حوالة داخلية إلـى حساب مستثمر آخر';

    /**
     * @var string
     */
    protected $usage = '/transfer يمكنك الضغط عـلى الأمر هٌـنا مباشرة';

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

        // if not authorized
        if ($AuthorizedUser === false) {
            return $this->getTelegram()->executeCommand('help');
        }

        // $data = ['chat_id' => '968814487'];
        // $data['caption'] = 'ممكن تنزل الارباح اوتوماتيك في كل شهر هكي، هذه الرسالة جاية لغادة اوتوماتيك من البوت، تقدر تبعت لكل البشر عادي ';
        // $data['photo']   = Request::encodeFile('announcement.jpg');
        // return Request::sendPhoto($data);

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
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 0;
                    $this->conversation->update();
                    $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
                    $data['text'] = 'رقَـم حِساب العَـميل المُـستقبل';
                    if ($text !== '') {
                        $data['text'] = 'رقم الحساب يجب أن يكون رقم';
                    }

                    $data['parse_mode'] = 'MARKDOWN';
                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['account'] = $text;
                $text             = '';
            // no break
            case 1:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 1;
                    $this->conversation->update();
                    $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
                    $data['text'] = 'القـيمة المُـراد إرسـالها';

                    if ($text !== '') {
                        $data['text'] = 'قـيمة المُـرسلة يجب أن تكون رقم';
                    }

                    $data['parse_mode'] = 'MARKDOWN';
                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['value'] = $text;
                $text             = '';

                // no break
            case 2:
                if ($text === '' && $notes['already_called'] !== true) {
                    $notes['already_called'] = true;
                    $notes['state'] = 2;
                    $this->conversation->update();
                    $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
                    $data['parse_mode'] = 'MARKDOWN';
                    $data['text'] = '*ملاحظات عملية التحويل*' . PHP_EOL;
                    $data['text'] .= PHP_EOL. 'أي ملاحظات على طلب التحويل؟ أو أدخل الرقم 0 للإستمرار';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['notes'] = (string)$text === '0' ? '' : $text;
                $text         = '';

            // no break
            case 3:
                 //Send chat action
                $this->conversation->update();
                Request::sendChatAction([
                    'chat_id' => $chat_id,
                    'action'  => 'typing',
                ]);
                if (LibyanTrader::transferToAccount($AuthorizedUser, $notes) === true) {
                    $out_text = 'تم إنشـاء طلب الإرسـال بنجاح، لا داعي لتكرار الطلب، بيانات تحـويل الأموال:' . PHP_EOL;
                    unset($notes['state']);
    
                    $out_text .= PHP_EOL . '*نـوع الطلب*: حـوالة داخـلية';
                    $out_text .= PHP_EOL . '*حِـساب المُـستفيد*: ' . $notes['account'];
                    $out_text .= PHP_EOL . '*المَـبلغ*: ' . $notes['value'];
                    if ($notes['notes']) $out_text .= PHP_EOL .'*مُـلاحظات*: ' . $notes['notes'];
    
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);
                    $data['text']      = $out_text;
                    $data['parse_mode'] = 'MARKDOWN';
                    $this->conversation->stop();
    
                    $result = Request::sendMessage($data);
                    break;
                }
                else {
                    $data['text'] = LibyanTrader::$ERROR_TEXT;
                    $data['parse_mode'] = 'MARKDOWN';
                    $this->conversation->stop();
                    $result = Request::sendMessage($data);
                    break;
                }
        }

        return $result;
    }
}
