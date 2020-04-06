# Libyan Trader Telegram Bot
Follow the instuctions on how to install and integrate the backend bot to your current system.

Let's get started then! :smiley:

## 0. Cloning this repository

To start off, you can clone this repository using git:

```bash
$ git clone https://github.com/ahmedrhuma/libyantrader-telegram-bot.git
```

## 1. Download dependencies

We use `composer`, you can download the dependencies using it.

`composer.phar install`

---

**Structure**
This is the files structure, and what each file contains.

- `composer.json` (Describes the project and it's dependencies)
- `set.php` (Used to set the webhook)
- `unset.php` (Used to unset the webhook)
- `hook.php` (Used for the webhook method)
- `config.php` (used to held the config params)
- `test.php` (Showcase on how to use the API out of commands context)
- `Commands` (used to held all the commands under the hood work)

## 2. MySQL storage

create a new database (utf8mb4_unicode_520_ci), import structure.sql.

**Edit the database connection on `config.php` file**

```php
$mysql_credentials = [
   'host'     => 'localhost',
   'user'     => 'root',
   'password' => '',
   'database' => 'telegram',
];
```

## 3. Bot Authentication

Change the authentication token and bot name on `config.php` file.

```php
$bot_api_key  = 'TOKEN_HERE';
$bot_username = 'BOT_NAME';
```

## 4. Setting admin user

After getting the telegram user ID, you can set some users as admins, so they can run admin commands, set the user admins ID on `config.php`

```php
$admin_users = [
    ID_HERE,
    ID_TWO
    ...etc
]
```


## 5. Set hook URL

Set the URl for the hook on `config.php` file.

```php
$hook_url = 'https://your_path/hook.php';
```

## 6. Modifying DB Queries

All Business logic related queries can be found on `Commands/LibyanTrader.php` class.

it's has the inline comments describes the inputs and the outputs needed.

**DO NOT CHANGE OTHER FILES UNDRE `Commands` FOLDER**

## 7. Queries

* login
* Logout
* AuthorizedData
* transferToAccount
* withdrawalRequest
* getSummary
* getResult
* contact
* sendMessageWithPhoto
* sendMessageWithPictureToMany
* sendMessage
* sendMessageToMany

#### Login

Performs login request, the unique user ID's are `$user_id` and `$telegram_id`

#### Logout
performs logout request, must clear the data

#### AuthorizedData
Checks if the user is already authenticated or not, by checking the telegram `$user_id` and `$chat_id` if they exists.

#### TransferToAccount
Performs internal transfer request

#### WithdrawalRequest
Performs withdrawal request

#### getSummary
Get account summary, current balance and holder name.

#### getResult
Get the last performance result.

#### contact
Contact us form result

#### sendMessageWithPhoto
Send Message with picture to specific user 

#### sendMessage
Send message to specific user

#### sendMessageWithPictureToMany
send message to multiple users

#### sendMessageToMany
send message to multiple users

## 8. Sending Messages

You can check the file `test.php` to see how to send message to particular user using `$chat_id`.
