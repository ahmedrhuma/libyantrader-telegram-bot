<?php
namespace Huemix;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Request;
use PDO;

class LibyanTrader {

    static $ERROR_TEXT = '*حَـدث خطـأ أثـناء إنشـاء الطلب*' . PHP_EOL
    . 'الرَجـاء المحاولة مرة أخـرى لاحقا، أو الإتصال بنـا عـن طَـريق /contact للتبليغ عن المشكلة.';

    static $CLOSED = '*المَـوقع مغلق للصـيانة*' . PHP_EOL . PHP_EOL
    . 'يـتم إجراء تـحديثات عـلى النِـظام، ولا يمكنك إجراء أي عملية في الوقت الحالي، سيـتم إعلامكم فـور الإنتهاء من عملية التـحديث.' . PHP_EOL . PHP_EOL .
    '*شُـكرا لـكونك أحد مُـستثمري شركة المُـتداول الـليبي*';

    /**
     * DO Login
     * @param user ['email', 'account', 'password']
     * @param user_id telegram user ID
     * @param chat_id telegram chat id
     * @param info telegram user info
     * @return true on success
     * @return false if any error occured
     */
    static function login($user, $user_id, $chat_id, $info) {
        // use The following information
        $email = $user['email'];
        $accountNumber = $user['account'];
        $password = $user['password'];

        $pdo = DB::getPdo();
        $stmt = $pdo->prepare('
            INSERT INTO `loggedin` VALUES(:user_id, :chat_id, :name)
        ');
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $info->first_name . ' ' . $info->last_name);
        $stmt->execute();
        $DB_RESPONSE = (object)[
            'name' => $info->first_name . ' ' . $info->last_name,
            'account' => $accountNumber
        ];

        return $DB_RESPONSE;
    }

    /**
     * Apply withdraw money query to Database
     * @param $handler telegram handler
     * @return true on success
     * @return false if any error occured
     */

    static function Logout($handler) {
        $message     = $handler->getMessage();
        $chat_id     = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();

    
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare('
            DELETE FROM loggedin WHERE user_id = :user_id AND chat_id = :chat_id
        ');
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':chat_id', $chat_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * @return userData on success
     * @return false on error
     * @return -1 if closed
     */
    static function AuthorizedData($handler) {
        $message     = $handler->getMessage();
        $chat_id     = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
    
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare('
            SELECT * FROM loggedin WHERE user_id = :user_id AND chat_id = :chat_id
        ');
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchObject();
    }

    /**
     * Apply transfer to Account query to Database
     * @param user ['user_id', 'chat_id']
     * @param to ['account', 'value', 'notes']
     * @return true on complete
     * @return false on error
     */
    static function transferToAccount($user, $to) {
        // do Database Request here
        return true;
    }

    /**
     * Apply withdraw money query to Database
     * @param user ['user_id', 'chat_id']
     * @param data ['type', 'value', 'notes']
     * @return true on complete
     * @return false on error
     */
    static function withdrawalRequest($user, $data) {
        $TYPES_MAP = [
            'حـوللي' => 'havelle',
            'حـوللي تركيا' => 'havelle_turkey',
            'بـنك إلكتروني' => 'online_bank',
            'حـوالة بنكية' => 'bank'
        ];
        // do database request here
        return true;
    }

    /**
     * Apply withdraw money query to Database
     * @param user ['user_id', 'chat_id']
     * @return object { account: Number, name: String, balance: Number, b_balance: Number}
     * @return false if any error occured
     */
    static function getSummary($user) {   
        return (object)[
            'account' => $user->user_id,
            'name' => $user->name,
            'balance' => rand(50000, 500000),
            'b_balance' => rand(0, 50000)
        ];
    }

    /**
     * Apply profits query displays each month profit on object
     * the key represents the month and the value represents the money
     * @param user ['user_id', 'chat_id']
     * @return object represents month as key number and the value is the profit { '1' => Number, '2' => Number}
     * @return false if any error occured
     */
    static function getResult($user) {
        // FAKE RESPONSE
        $month = date('m');
        $result = [];
        for ($i = 1;$i<$month;$i++) $result[$i] = rand();

        return (object)$result;
    }

    /**
     * Apply withdraw money query to Database
     * @param user ['user_id', 'chat_id']
     * @param params ['how', 'about', 'message']
     * @return true on success
     * @return false if any error occured
     */
    static function contact($user, $params) {
        return true;
    }

    /**
     * Send Message to specific user using $chat_id
     * @param chat_id user chat_id
     * @param text picture caption
     * @param photo_url internal picture path
     */
    static function sendMessageWithPhoto($chat_id, $text, $photo_path) {
        $data = ['chat_id' => $chat_id];
        $data['caption'] = $text;
        $data['photo']   = Request::encodeFile($photo_path);
        self::initialize();
        return Request::sendPhoto($data);
    }

    /**
     * Send Message to specific user using $chat_id
     * @param chat_id user chat_id
     * @param text picture caption
     */
    static function sendMessage($chat_id, $text) {
        $data = ['chat_id' => $chat_id];
        $data['text'] = $text;
        self::initialize();
        return Request::sendMessage($data);
    }

    /**
     * Send Message to specific user using $chat_id
     * API does not suuport sending more than 30 request per second
     * @param chat_id Array [user chat_id]
     * @param text picture caption
     * * @param photo_url internal picture path
     */
    static function sendMessageWithPictureToMany($chat_id, $text, $photo_path) {
        self::initialize();
        foreach($chat_id as $k => $v) {
            $data = ['chat_id' => $v];
            $data['caption'] = $text;
            $data['photo']   = Request::encodeFile($photo_path);
            Request::sendPhoto($data);
            // wait half a second before the next request
            if (count($chat_id) > 100) sleep(0.5);
        }
    }

    /**
     * Send Message to specific user using $chat_id
     * API does not suuport sending more than 30 request per second
     * @param chat_id Array [user chat_id]
     * @param text picture caption
     */
    static function sendMessageToMany($chat_id, $text) {
        self::initialize();
        foreach($chat_id as $k => $v) {
            $data = ['chat_id' => $v];
            $data['text'] = $text;
            Request::sendMessage($data);
            // wait half a second before the next request
            if (count($chat_id) > 100) sleep(0.5);
        }
    }

    private static function initialize() {
        require_once 'config.php';
        Request::initialize(new \Longman\TelegramBot\Telegram($bot_api_key, $bot_username));
    }
}
