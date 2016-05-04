<?php
namespace Craft;

class AmTools_ErrorsService extends BaseApplicationComponent
{
	public function initErrorHandler()
	{
		\Yii::app()->onException = function($exceptionEvent)
		{
			$msg = array(
				'code' => $exceptionEvent->exception->getCode(),
				'message' => $exceptionEvent->exception->getMessage(),
				'location' => $exceptionEvent->exception->getFile() . ' on line ' . $exceptionEvent->exception->getLine()
			);

			if ($msg['message'] != '')
			{
				AmTools_ErrorsService::sendErrorMail($msg);
			}
		};
		\Yii::app()->onError = function($errorEvent)
		{
			$msg = array(
				'code' => $errorEvent->code,
				'message' => $errorEvent->message,
				'file' => $errorEvent->file,
				'line' => $errorEvent->line
			);

			AmTools_ErrorsService::sendErrorMail($msg);
		};
	}

	public function sendErrorMail($error)
	{
		$error = array_merge($error, array(
			'server' => $_SERVER,
			'session' => $_SESSION,
			'request' => $_REQUEST
		));
		mail(craft()->config->get('testToEmailAddress') != '' ? craft()->config->get('testToEmailAddress') : 'onderhoud@am-impact.nl', "Fout in Craft opgetreden op " . $_SERVER["SERVER_NAME"], print_r($error, 1));
	}

	public function send404Header()
    {
        header("HTTP/1.0 404 Not Found");
    }
}
