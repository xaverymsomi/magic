<?php

/**
 * Description of Bootstrap
 *
 * @author Developer
 */

namespace Libs;

use Exception;
use Modules\Dashboard\Dashboard;
use Modules\Error\Error;
use Modules\Login\Login;

class Mabrex
{
	private ?array $_url = [];
	private ?string $url = '';
	private mixed $_controller = null;

	private string $_controllerPath = 'controllers/';
	private string $_modulesPath = MX17_APP_ROOT . '/modules/';
	private string $_modelPath = 'models/';
	private string $_errorFile = 'Error.php';
	private string $_defaultModule = 'Dashboard';
	private string $_defaultFile = 'Dashboard.php';
	private array $extensions_no_log = ['.ico', '.png', '.jpg', '.css', '.js', '.map'];

	private static function malicious_inputs($data): void
	{
		// Define patterns to match against strings
		$malicious_patterns = [
			'/[\r\n]/', // Newlines
			'/[<>]/', // HTML tags or special characters
			'/\b(php|eval|javascript):/i', // Code execution attempts
			'/\b(base64_encode|base64_decode)/i',
			'/\b(gzinflate|gzuncompress)/i',
			'/\b(system|exec|shell_exec|passthru|proc_open|popen|pcntl_exec)/i',
			'/\b(eval|create_function)/i',
			'/\b(assert|preg_replace)/i',
			'/\b(iframe|<script|on\w+=)/i',
			'/\b(sql_(connect|query)|mysql_(connect|query)|mysqli_(connect|query)|pg_(connect|query)|sqlite_(open|query)|sqlite3_(open|query))/i',
			'/\b(unlink|fwrite|fopen|file_put_contents)/i',
			'/\b(mail|header)/i',
			'/\b(\$_(GET|POST|COOKIE|REQUEST|FILES|SERVER))/i',
			'/\b(\$_(SESSION|ENV|GLOBALS))/i',
		];

		// Recursive function to sanitize mixed-type input
		$checkValue = function ($value) use (&$checkValue, $malicious_patterns) {
			if (is_array($value)) {
				foreach ($value as $v) {
					$checkValue($v); // Recursive
				}
			} elseif (is_string($value)) {
				foreach ($malicious_patterns as $pattern) {
					if (preg_match($pattern, $value)) {
						Log::sysLog('Malicious match: [' . $pattern . ']');
						Log::sysLog('Malicious input: [' . $value . ']');
						kill();
					}
				}
			}
		};

		$checkValue($data);
	}


	public function init(): void
	{
		$this->processUrl();
		$this->_getUrl();

		if ($this->checkNoLogExtensions()) {
			exit;
		}

		$this->logRequestStart();
		Log::sysLog('REQUEST-STARTED: '. json_encode($this->_url[0]));

		if (!$this->isControllerInNonAuthenticatedList()) {
			(new LoginCheck())->protect(strtolower($this->_url[0] ?? ''));
			$this->loadControllerWithAuth();
		} else {
			(new LoginCheck())->destroy(strtolower($this->_url[0] ?? ''));
			$this->loadControllerWithoutAuth();
		}

		Log::sysLog('REQUEST-ENDED: '. json_encode($this->_url[0]));
	}

	private function logRequestStart(): void
	{
		Log::savePlainLog(str_repeat("*", 150));
		Log::sysLog('REQUEST-STARTED');
		Log::sysLog('CALLER: ' . user_log());
		Log::sysLog('REQUEST-URL: ' . $this->url);
		Log::sysLog('REQUEST-CONTENT-TYPE: ' . (array_key_exists('CONTENT_TYPE', $_SERVER) ? $_SERVER['CONTENT_TYPE'] : null));
		Log::sysLog('REQUEST-DATA: ' . json_encode($this->getRequestData()));
		Log::sysLog('REQUEST-URL-DATA: ' . json_encode($this->_url));
		Log::sysLog('AUTH-CHECK: ' . json_encode(Auth::isLogged()));
	}

	private function loadControllerWithAuth(): void
	{
		Log::sysLog('AUTH-USER: ' . json_encode(Auth::user()));
		if (empty($this->_url[0])) {
			Log::sysLog('LOADING DASHBOARD CONTROLLER');
			$this->_loadDefaultController();
		} else {
			Log::sysLog('LOADING CONTROLLER');
			if ($this->_loadExistingController()) {
				$this->handleDualControl();
			}
		}
	}

	private function loadControllerWithoutAuth(): void
	{
		if (empty($this->_url[0])) {
			Log::sysLog('LOADING LOGIN CONTROLLER');
			$this->_loadLoginController();
		} else {
			Log::sysLog('LOADING CONTROLLER');
			if ($this->_loadExistingController()) {
				$this->_callControllerMethod();
			}
		}
	}

	private function _loadLoginController(): void
	{
		try {
			$loginController = new Login();
			$loginController->index();
			Log::sysLog('LOGIN CONTROLLER LOADED SUCCESSFULLY');
		} catch (Exception $e) {
			Log::sysLog('[ERROR-500]: Failed to load login controller - ' . $e->getMessage());
			$this->_error();
		}
	}

	private function handleDualControl(): void
	{
		Log::sysLog('CHECKING DUAL CONTROL');
		$dual = new DualControl($this->_url[0], $this->_url[1] ?? '');

		Log::sysLog('DUAL-CONTROL: ' . json_encode($dual));
		$result = $dual->getResult();

		Log::sysLog('DUAL-CONTROL-RESULT: ' . json_encode($result));

		if (!$result) {
			Log::sysLog('LOADING CONTROLLER METHOD');
			$this->_callControllerMethod();
		}
	}

	private function processUrl(): void
	{
		$this->url = $_GET['url'] ?? $_SERVER['PATH_INFO'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
		if ($this->url === '/index.php') {
			$this->url = '';
		}
	}

	private function _getUrl(): void
	{
		$url = trim($this->url, '/');
		$url = filter_var($url, FILTER_SANITIZE_URL);
		$this->_url = explode('/', $url);
	}

	private function checkNoLogExtensions(): bool
	{
		foreach ($this->extensions_no_log as $ext) {
			if (str_contains($this->url, $ext)) {
				return true;
			}
		}
		return false;
	}

	private function isControllerInNonAuthenticatedList(): bool
	{
		return in_array(strtolower($this->_url[0] ?? ''), ['login', 'logout', 'autorun']);
	}

	private function _loadDefaultController(): void
	{
		$this->_controller = new Dashboard();
		$this->_controller->index();
	}

	private function _loadExistingController(): bool
	{
		$controllerFolder = ucwords(strtolower($this->_url[0] ?? ''));
		$controllerPath = $this->_modulesPath . $controllerFolder . '/' . $controllerFolder . '.php';

		if (!file_exists($controllerPath)) {
			Log::sysLog('[ERROR-404]: CONTROLLER ' . $controllerFolder . '.php NOT FOUND');
			$this->_error();
			return false;
		}

		$controllerClass = 'Modules\\' . $controllerFolder . '\\' . $controllerFolder;
		$this->_controller = new $controllerClass();
		return true;
	}

	private function _callControllerMethod(): void
	{
		$method = $this->_url[1] ?? 'index';
		$params = array_slice($this->_url, 2);

		if (!method_exists($this->_controller, $method)) {
			Log::sysLog('[ERROR-404]: METHOD ' . $method . ' NOT FOUND');
			$this->_error();
			return;
		}

		call_user_func_array([$this->_controller, $method], $params);
	}

	private function _error(): void
	{
		$this->_controller = new Error("Error 404", "Resource Not Found");
		$this->_controller->index();
		exit;
	}

	private function getRequestData(): array
	{
		$files = [];
		$data = [];

		if ($_FILES) {
			$files = $_FILES;
			$post = $_POST;
			$data = array_merge($post, $files); // full request data
		} else {
			$raw = file_get_contents('php://input');
			$data = $raw;
		}

		// Sanitize/scan only if data is a string or array of strings
		self::malicious_inputs($data);

		// Mask sensitive data if it's a string payload
		if (is_string($data) && str_contains($data, 'password') ) {
			$data = 'CONFIDENTIAL';
		}

		return [
			'data' => $data,
			'files' => $files
		];
	}
}