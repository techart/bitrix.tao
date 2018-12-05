<?php

namespace TAO;

use Bitrix\Main\Web\HttpClient;

class Auth
{
	protected $officeAuthUrl;

	public static function init()
	{
		$auth = new Auth();
		$auth->register();
	}

	public function register()
	{
		\AddEventHandler("main", "OnUserLoginExternal", array($this, "onUserLoginExternal"), 100);
		\AddEventHandler("main", "OnExternalAuthList", array($this, "onExternalAuthList"), 100);
	}

	public function onUserLoginExternal(&$arParams)
	{
		if (!$this->useOfficeAuth()) {
			return null;
		}
		$login = new UserLogin(($arParams['LOGIN']));
		$password = $arParams['PASSWORD'];

		if ($this->isAuthorized($login->getOfficeLogin(), $password, $this->officeAuthUrl())) {
			$fields = array(
				"LOGIN" => $login->getBitrixLogin(),
				"NAME" => $login->getOfficeLogin(),
				"PASSWORD" => $password,
				"EMAIL" => $login->getEmail(),
				"ACTIVE" => "Y",
				"EXTERNAL_AUTH_ID" => "Office",
				"LID" => SITE_ID,
			);
			$user = new \CUser();
			$existedUser = \CUser::GetList($by = "timestamp_x", $order = "desc", array(
				"LOGIN_EQUAL_EXACT" => $login->getBitrixLogin(),
				"EXTERNAL_AUTH_ID" => "Office",
			))->Fetch();
			if (!$existedUser) {
				$shouldAdd = true;
				\TAO\Events::emit('auth.add_office_user', $fields, $shouldAdd);
				if ($shouldAdd) {
					$id = $user->Add($fields);
				}
			} else {
				$id = $existedUser["ID"];
				$shouldUpdate = true;
				\TAO\Events::emit('auth.update_office_user', $fields, $shouldUpdate);
				if ($shouldUpdate) {
					$user->Update($id, $fields);
				}
			}
			if ($id > 0) {
				$groups = \CUser::GetUserGroup($id);
				$shouldSetGroups = true;
				if (!in_array(1, $groups)) {
					$groups[] = 1;
				}
				\TAO\Events::emit('auth.set_user_groups', $login, $password, $id, $groups, $shouldSetGroups);

				if ($shouldSetGroups) {
					\CUser::SetUserGroup($id, $groups);
				}
				$arParams["store_password"] = "N";

				return $id;
			}
		}
		return null;
	}

	public function onExternalAuthList()
	{
		return $this->useOfficeAuth() ? array(array("ID" => "Office", "NAME" => "Офис Текарт")) : array();
	}

	protected function useOfficeAuth()
	{
		return (bool)$this->officeAuthUrl();
	}

	protected function officeAuthUrl()
	{
		return $this->officeAuthUrl ?: ($this->officeAuthUrl = \TAO::getOption('auth'));
	}

	protected function isAuthorized($strOfficeLogin, $strPassword, $url)
	{
		try {
			$http = new HttpClient();
			$http->setAuthorization($strOfficeLogin, $strPassword);
			$auth_status = trim($http->get($url));
			if ($http->getStatus() != '200') {
				return false;
			}
			return ($auth_status == 'ok' || $auth_status == 'ok:' || preg_match('{^ok:(.+)$}', $auth_status));
		} catch (\Exception $e) {
			error_log('Office external auth error: ' . $e->getMessage());
		}
		return false;
	}
}

class UserLogin
{
	protected $login = '';
	protected $email = '';

	public function __construct($login)
	{
		if (preg_match('~^(.+)\@techart\.ru$~', $login, $m)) {
			$this->login = $m[1];
			$this->email = $login;
		} else {
			$this->login = $login;
			$this->email = $login . '@techart.ru';
		}
	}

	public function getOfficeLogin()
	{
		return $this->login;
	}

	public function getBitrixLogin()
	{
		return $this->getEmail();
	}

	public function getEmail()
	{
		return $this->email;
	}
}
