<?php
namespace Craft;

class AmTools_ErrorsService extends BaseApplicationComponent
{
	public function setErrorHandler()
    {
    	// Convert PHP errors to exceptions
        set_error_handler(function ($number, $message, $file, $line) {
            if (0 === error_reporting()) {
                return;
            }

            throw new \ErrorException($message, $number, new \ErrorException($message, 0, $number, $file, $line));
        }, E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);

        // Handle exceptions which aren't caught in a try{} catch{} block
        set_exception_handler(array($this, 'setExceptionHandler'));
    }

    public function setExceptionHandler($exception)
    {
    	header('HTTP/1.0 500 Internal Server Error');
    	header('Content-Type: text/plain');

    	$this->_reportException($exception);
    }

    private function _reportException($exception)
    {
    	// Catch and ignore error exceptions to the next 2 calls so we don't
    	// end up in a loop when they land in uncaught_exception_handler which
    	// in turn calls report() again.
    	try {
    	    error_log($exception, 0);
    	}
    	catch (ErrorException $e) {
    	}

    	try {
    	    $server_address = $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    	    $message        = "Er is een error opgetreden op het volgende adres: \r\n" . $server_address . "\r\n\r\n De melding is als volgt:\r\n\r\n" . $exception;
    	    mail('h.prein@am-impact.nl', "Fout in Craft opgetreden op " . $_SERVER["SERVER_NAME"], $message);
    	}
    	catch (ErrorException $e) {
    	}
    }
}