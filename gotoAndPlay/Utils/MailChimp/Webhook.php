<?php
namespace gotoAndPlay\Utils\MailChimp;

class Webhook {

    private static $eventSubscriptions = [];
    private static $receivedWebhook    = null;

    public static function subscribe($event, callable $callback) {
        if (!isset(self::$eventSubscriptions[$event])) {
            self::$eventSubscriptions[$event] = [];
        }

        self::$eventSubscriptions[$event][] = $callback;

        self::receive();
    }

    public static function receive($input = null) {
        if (is_null($input)) {
            if (self::$receivedWebhook !== null) {
                $input = self::$receivedWebhook;
            } else {
                $input = file_get_contents("php://input");
            }
        }

        if (!is_null($input) && $input != '') {
            return self::processWebhook($input);
        }

        return false;
    }

    private static function processWebhook($input) {
        self::$receivedWebhook = $input;
        parse_str($input, $result);
        if ($result && isset($result['type'])) {
            self::dispatchWebhookEvent($result['type'], $result['data']);

            return $result;
        }

        return false;
    }

    private static function dispatchWebhookEvent($event, $data) {
        if (isset(self::$eventSubscriptions[$event])) {
            foreach (self::$eventSubscriptions[$event] as $callback) {
                $callback($data);
            }

            self::$eventSubscriptions[$event] = [];
        }
    }

}
