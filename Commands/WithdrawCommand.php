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
use Huemix\LibyanTrader;

/**
 * User "/withdraw" command
 *
 * Command that demonstrated the Conversation funtionality in form of a simple survey.
 */
class WithdrawCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'withdraw';

    /**
     * @var string
     */
    protected $description = 'طَـلب سـحب جديد';

    /**
     * @var string
     */
    protected $usage = '/withdraw يمكنك الضغط عـلى الأمر هٌـنا مباشرة';

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
        if ($AuthorizedUser === false) return $this->getTelegram()->executeCommand('help');
        else if ($AuthorizedUser === -1) {
            $data['text'] = LibyanTrader::$CLOSED;
            return Request::sendMessage($data);
        }

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
                    $this->conversation->update();

                    $data['text']         = 'إخـتر نـوع السـحب';
                    $data['reply_markup'] = (new Keyboard([
                        'حـوللي',
                        'حـوللي تركيا',
                        'بـنك إلكتروني',
                        'حـوالة بنكية'
                    ]))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['type'] = $text;
                $text          = '';

            // no break
            case 1:
                if ($text === '' || !is_numeric($text)) {
                    $notes['state'] = 1;
                    $this->conversation->update();
                    $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
                    $data['text'] = 'القـيمة المُـراد سـحبها' . PHP_EOL . 
                    '*ملاحـظة*: أي قـيمة أكبـر مِـن أربـاحك لهذا الشهر ('. number_format(rand(500, 5000), 2, '.', ',') .') قد تتأخر بعض الوقـت ليتم تنفيذها.';

                    if ($text !== '') {
                        $data['text'] = 'قـيمة السحب يجب أن تكون رقم';
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
                    $data['text'] = '*ملاحظات عملية السحب*' . PHP_EOL;
                    if ($notes['type'] !== 'حـوللي' && $notes['حـوللي تركيا']) {
                        $data['text'] .= PHP_EOL. 'أي ملاحظات على طلب السحب؟ أو أدخل الرقم 0 للإستمرار';
                    }
                    else {
                        if ($notes['type'] === 'بـنك إلكتروني') {
                            $data['text'] = PHP_EOL . 'يُـرجى إدخال بيانات حِـسابك في البنك الإلكتروني: مثال إسم المنصة، رقم حسابك أو بريدك الإلكتروني.';
                        }
                        else {
                            $data['text'] = PHP_EOL . 'يُـرجى إدخال بيانات حِـسابك: إسم صاحب الحساب، رقم الأيبان، كود السويفت.';
                        }
                    }

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['notes'] = (string)$text === '0' ? '' : $text;
                $text         = '';

            // no break
            case 3:
                $this->conversation->update();
                Request::sendChatAction([
                    'chat_id' => $chat_id,
                    'action'  => 'typing',
                ]);
                if (LibyanTrader::withdrawalRequest($AuthorizedUser, $notes) === true) {
                    $out_text = 'تم إنشـاء طلب السحب بنجاح، لا داعي لتكرار الطلب، بيانات طلب السحب:' . PHP_EOL;
                    unset($notes['state']);
    
                    $out_text .= PHP_EOL . '*نـوع الطلب*: ' . $notes['type'];
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
                    $this->conversation->stop();
                    $data['text'] = LibyanTrader::$ERROR_TEXT;
                    $data['parse_mode'] = 'MARKDOWN';
                    $result = Request::sendMessage($data);
                    break;
                }
        }

        return $result;
    }
}
