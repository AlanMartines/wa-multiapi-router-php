<?php
/* Informa o nível dos erros que serão exibidos */
//
require_once('../config.php');
require_once(DBAPI);
//
class adv
{
	private $pdo = null;
	private static $crudAdv = null;

	private function __construct($conexao)
	{
		$this->pdo = $conexao;
	}

	public static function conectar($conexao)
	{
		if (!isset(self::$crudAdv)) {
			self::$crudAdv = new adv($conexao);
		}
		return self::$crudAdv;
	}

	public function validateToken($token = null)
	{
		try {
			$sql = "SELECT * FROM tokens WHERE token = ?";
			$stm = $this->pdo->prepare($sql);
			$stm->bindValue(1, $token);
			$stm->execute();
			$linha = $stm->fetch(PDO::FETCH_OBJ);
			Conexao::desconectar();
			return $linha;
		} catch (PDOException $erro) {
			Conexao::desconectar();
			error_log("Erro na linha: {$erro->getLine()}" . " / Erro SQL: " . $erro->getMessage());
			return false;
		}
	}

	public function sendMassaToken($email = null)
	{
		try {
			$sql = "SELECT * FROM token_as_name WHERE email = ? AND `state` = 'CONNECTED' ORDER BY RAND() LIMIT 1";
			$stm = $this->pdo->prepare($sql);
			$stm->bindValue(1, $email);
			$stm->execute();
			$linha = $stm->fetch(PDO::FETCH_OBJ);
			Conexao::desconectar();
			return $linha;
		} catch (PDOException $erro) {
			Conexao::desconectar();
			error_log("Erro na linha: {$erro->getLine()}" . " / Erro SQL: " . $erro->getMessage());
			return false;
		}
	}

	public function updateStatisticsSuccess($token = null)
	{
		try {
			$sql = "
				INSERT INTO statistics (token, date, success, error, created, modified)
				VALUES (?, CURDATE(), 1, 0, NOW(), NOW())
				ON DUPLICATE KEY UPDATE
					success = success + 1,
					modified = NOW()
			";
			$stm = $this->pdo->prepare($sql);
			$stm->bindValue(1, $token);
			$stm->execute();
			Conexao::desconectar();
			return true;
		} catch (PDOException $erro) {
			Conexao::desconectar();
			error_log("Erro na linha: {$erro->getLine()} / Erro SQL: " . $erro->getMessage());
			return false;
		}
	}	

	public function updateStatisticsError($token = null)
	{
		try {
			$sql = "
				INSERT INTO statistics (token, date, success, error, created, modified)
				VALUES (?, CURDATE(), 0, 1, NOW(), NOW())
				ON DUPLICATE KEY UPDATE
					error = error + 1,
					modified = NOW()
			";
			$stm = $this->pdo->prepare($sql);
			$stm->bindValue(1, $token);
			$stm->execute();
			Conexao::desconectar();
			return true;
		} catch (PDOException $erro) {
			Conexao::desconectar();
			error_log("Erro na linha: {$erro->getLine()} / Erro SQL: " . $erro->getMessage());
			return false;
		}
	}

}
