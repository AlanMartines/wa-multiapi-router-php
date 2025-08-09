<?php

class Router
{
	private $routes = [];

	public function add($method, $path, $callback)
	{
		// Converter rota com :param para regex
		$pattern = preg_replace('#:([\w]+)#', '(?P<\1>[^/]+)', $path);
		$pattern = "#^" . $pattern . "$#";

		$this->routes[] = [
			'method' => strtoupper($method),
			'pattern' => $pattern,
			'callback' => $callback
		];
	}

	public function get($path, $callback)
	{
		$this->add('GET', $path, $callback);
	}

	public function post($path, $callback)
	{
		$this->add('POST', $path, $callback);
	}

	public function options($path, $callback)
	{
		$this->add('OPTIONS', $path, $callback);
	}

	public function put($path, $callback)
	{
		$this->add('PUT', $path, $callback);
	}

	public function delete($path, $callback)
	{
		$this->add('DELETE', $path, $callback);
	}

	public function patch($path, $callback)
	{
		$this->add('PUT', $path, $callback);
	}

	public function run()
	{
		$method = $_SERVER['REQUEST_METHOD'];
		$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

		// Detectar automaticamente o caminho base
		$scriptName = dirname($_SERVER['SCRIPT_NAME']);
		if ($scriptName !== '/' && str_starts_with($uri, $scriptName)) {
			$uri = substr($uri, strlen($scriptName));
		}

		// Tratar corpo JSON
		$body = file_get_contents("php://input");
		$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
		$jsonData = null;

		if (stripos($contentType, 'application/json') !== false) {
			$jsonData = json_decode($body, false);
			if (json_last_error() !== JSON_ERROR_NONE) {
				http_response_code(400);
				echo json_encode([
					"Status" => [
						"error" => true,
						"statusCode" => 400,
						"mensagem" => "JSON inválido: " . json_last_error_msg()
					]
				], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
				exit;
			}
		} else {
			// Suporte a form-data e application/x-www-form-urlencoded
			$jsonData = (object) $_POST;
		}

		// Segurança extra para POSTs sem CONTENT_TYPE
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($jsonData)) {
			$jsonData = (object) $_POST;
		}

		foreach ($this->routes as $route) {
			if ($route['method'] !== $method) {
				continue;
			}

			if (preg_match($route['pattern'], $uri, $matches)) {
				$params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
				// Mescla query string ($_GET) com os parâmetros da rota
				$mergedParams = array_merge($params, $_GET);
				call_user_func($route['callback'], (object) $mergedParams, $jsonData ?? new stdClass());
				return;
			}
		}

		http_response_code(404);
		$object = [
			"Status" => [
				"error" => true,
				"statusCode" => http_response_code(),
				"mensagem" => "Não foi possivel executar a ação, verifique a url informada."
			]
		];
		header('Content-type: application/json');
		echo json_encode($object, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		exit;
	}
}
