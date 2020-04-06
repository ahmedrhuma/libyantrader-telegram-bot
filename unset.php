<?php
/**
 * README
 * This file is intended to unset the webhook.
 */

// Load composer
require_once __DIR__ . '/vendor/autoload.php';
require_once 'config.php';

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Delete webhook
    $result = $telegram->deleteWebhook();

    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}
