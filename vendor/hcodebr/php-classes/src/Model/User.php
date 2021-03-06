<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model {
    
    const SESSION = "User";
	const SECRET = "HcodePhp7_Secret";
	const SECRET_IV = "HcodePhp7_Secret_IV";
	const ERROR = "UserError";
	const ERROR_REGISTER = "UserErrorRegister";
	const SUCCESS = "UserSucesss";

    public static function getFromSession()
    {
        $user = new User();

        if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0){

            $user->setData($_SESSION[User::SESSION]);

        }

        return $user;

    }

    public static function checkLoginExist($desemail)
    {

        $sql = new Sql();

        $results =  $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
            ':deslogin'=>$desemail
        ]);

        if (count($results) == 0){

            return false;

        }else{
            return true;
        }
    }

    public static function checkLogin($inadmin = true)
    {

       if (
        !isset($_SESSION[User::SESSION])
        ||
        !$_SESSION[User::SESSION]
        ||
        !(int)$_SESSION[User::SESSION]["iduser"] > 0
       ) {
           //Não esta logado
           return false;
       }else{

            if($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true){

                return true;

            }else if($inadmin === false){

                return true;

            }else{

                return false;

            }

       }

    }


    public static function login($login, $password){

        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            "LOGIN"=>$login
        ));

        if (count($results) === 0)
        {
            throw new \Exception("Usuario o senha invalido.");
        }

        $data = $results[0];

        if(password_verify($password, $data["despassword"]) === true)
        {
            $user = new User();
            
            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();
            
            return $user;

        }else{
            throw new \Exception("Usuario o senha invalido.");
        }

    }

    public static function verifyLogin($inadmin = true)
    {
        if(User::checkLogin($inadmin)){

        header("Location: /admin/login");
        exit;
        }
    }

    public static function logout()
    {

        $_SESSION[User::SESSION] = NULL;

    }

    public static function listAll(){

        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

    }

    public function save()
    {

        $sql = new Sql();

        $results =  $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :indamin)", array(
            ":desperson"=> utf8_decode($this->getdesperson()),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":indamin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);

    }

    public function get($iduser)
    {
        $sql = new Sql();

        $results =  $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser"=>$iduser
        ));

        $this->setData($results[0]);

    }

    public function update()
    {

        $sql = new Sql();

        $results =  $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :indamin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":indamin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);
    }

    public function delete()
    {

        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$this->getiduser()
        ));

    }

    public static function getForgot($email, $inadmin = true)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT *
			FROM tb_persons a
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;
		", array(
			":email"=>$email
		));

		if (count($results) === 0)
		{

			throw new \Exception("Não foi possível recuperar a senha.");

		}
		else
		{

			$data = $results[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser"=>$data['iduser'],
				":desip"=>$_SERVER['REMOTE_ADDR']
			));

			if (count($results2) === 0)
			{

				throw new \Exception("Não foi possível recuperar a senha.");

			}
			else
			{

				$dataRecovery = $results2[0];

				$code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

				$code = base64_encode($code);

				if ($inadmin === true) {

					$link = "http://www.thigu.com.br/admin/forgot/reset?code=$code";

				} else {

					$link = "http://www.thigu.com.br/forgot/reset?code=$code";
					
				}
                			

				$mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha da Hcode Store", "forgot", array(
					"name"=>$data['desperson'],
					"link"=>$link
				));				

				$mailer->send();

				return $data;


            }

        }
    }

    public static function setErrorRegister($msg)
    {

        $_SESSION[User::ERROR_REGISTER] = $msg;

    }

    public static function getErrorRegister()
    {

        $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';
        user::clearErrorRegister();

        return $msg;

    }

    public static function clearErrorRegister()
    {

        $_SESSION[User::ERROR_REGISTER] = NULL;

    }

    public static function getPage($page = 1, $itemsPerPage = 3)
    {
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();

        $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_users a
            INNER JOIN tb_persons b USING(idperson)
            ORDER BY b.desperson
            LIMIT $start, $itemsPerPage;");

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            'data'=>$results,
            'total'=>(int)$resultTotal[0]["nrtotal"],
            'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
        ];

    }

    public static function getPageSearch($search, $page = 1, $itemsPerPage = 3)
    {
        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();

        $results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_users a
            INNER JOIN tb_persons b USING(idperson)
            WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
            ORDER BY b.desperson
            LIMIT $start, $itemsPerPage;", [
                ':search'=>"%$search%"

            ]);

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

        return [
            'data'=>$results,
            'total'=>(int)$resultTotal[0]["nrtotal"],
            'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage)
        ];

    }

       
}