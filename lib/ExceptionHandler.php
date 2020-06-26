<?php
namespace TAO;

use \Bitrix\Main\Diag\ExceptionHandlerLog;

class ExceptionHandler extends \Bitrix\Main\Diag\ExceptionHandlerLog {
	protected static $instance;
	protected $config;

	public function initialize(array $options){
		self::init($options);
	}

	public function init(array $options){
		self::instance();
	}

	public static function instance()
	{
		if (!static::$instance) {
			static::$instance = new self();
		}
		return static::$instance;
	}

	protected function messageContent($error)
	{
		$message = date('d.m.Y - G:i:s');
		$message .= "\n" . $error->getMessage();
		$message .= "\n" . $error->getFile()."(".$error->getLine().")\n";
		$message .= "\n" . 'StackTrace:'. "\n" . $error->getTraceAsString() . "\n";
		$message .= "\n".'$_SERVER:'."\n" . print_r($_SERVER, true)
			. "\n".'$_COOKIE:'."\n" . print_r($_COOKIE, true)
			. "\n".'$_SESSION:'."\n" . print_r($_SESSION, true)
			. "\n".'$_GET:'."\n" . print_r($_GET, true)
			. "\n".'$_POST:'."\n" . print_r($_POST, true);
		return $message;
	}

	protected function additionalHeaders() {
		$from = $this->config['from'];
		if (is_null($from)) {
			$from = 'error@' . $_SERVER['HTTP_HOST'];
		}
		return "From: ".$from."\r\n"."X-Mailer: PHP/" . phpversion();
	}

	public function write($exception, $logType)
	{
		$this->config = \TAO::getOption('errors_notifier');
		$recipients = $this->config['recipients'];
		if (!is_null($recipients)) {
			$title = $this->config['subject'];
			if (is_null($title)) {
				$title = 'Ошибка на сайте ' . $_SERVER['HTTP_HOST'];
			}
			$message = $this->messageContent($exception);
			$additionalHeaders = $this->additionalHeaders();
			if (is_array($recipients)) {
				foreach ($recipients as $recipient) {
					mail($recipient, $title, $message, $additionalHeaders);
				}
			} else {
				mail($recipients, $title, $message, $additionalHeaders);
			}
		}
	}
}