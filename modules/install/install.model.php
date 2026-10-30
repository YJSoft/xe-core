<?php
/* Copyright (C) XEHub <https://www.xehub.io> */
class installModel extends install
{
	var $pwd;

	function getMysqlConnectionDriver()
	{
		if(DBPdo_mysql::isSupported()) return 'pdo_mysql';
		if(function_exists('mysqli_connect')) return 'mysqli';
		if(function_exists('mysql_connect')) return 'mysql';
		return false;
	}

	/** Return installer choices, combining all MySQL drivers into one entry. */
	function getInstallDBList($enabled = true)
	{
		$mysql = new stdClass();
		$mysql->db_type = 'mysql';
		$mysql->enable = $this->getMysqlConnectionDriver() !== false;
		$list = array($mysql);
		foreach(DB::getSupportedList() as $db)
		{
			if(!DB::isMysql($db->db_type))
			{
				$list[] = $db;
			}
		}

		$result = array();
		foreach($list as $db)
		{
			if((bool)$db->enable === (bool)$enabled) $result[] = $db;
		}
		return $result;
	}

	/** Select an engine only after connecting to the installation database. */
	function getMysqlInstallDriver($db_type, $oDB)
	{
		$result = $oDB->_query('SHOW ENGINES');
		if(!$result || $oDB->isError()) return $oDB->getError();
		$rows = $oDB->_fetch($result);
		if(!is_array($rows)) $rows = array($rows);
		$engines = array();
		foreach($rows as $row)
		{
			if(is_object($row) && in_array(strtoupper($row->Support), array('YES', 'DEFAULT')))
			{
				$engines[strtolower($row->Engine)] = true;
			}
		}

		// SHOW ENGINES can report YES even when CREATE TABLE is disabled for an engine.
		$result = $oDB->_query("SHOW VARIABLES LIKE 'disabled_storage_engines'");
		if(!$result || $oDB->isError()) return $oDB->getError();
		$row = $oDB->_fetch($result);
		if(is_object($row))
		{
			foreach(explode(',', $row->Value) as $engine) unset($engines[strtolower(trim($engine))]);
		}

		if(isset($engines['innodb'])) $db_type .= '_innodb';
		else if(!isset($engines['myisam'])) return new BaseObject(-1, 'msg_no_mysql_storage_engine');

		$output = new BaseObject();
		$output->add('db_type', $db_type);
		return $output;
	}

	function getSFTPList()
	{
		$ftp_info =  Context::getRequestVars();
		if(!$ftp_info->ftp_host)
		{
			$ftp_info->ftp_host = "127.0.0.1";
		}

		$connection = ssh2_connect($ftp_info->ftp_host, $ftp_info->ftp_port);
		if(!ssh2_auth_password($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
		{
			return new BaseObject(-1,'msg_ftp_invalid_auth_info');
		}

		$sftp = ssh2_sftp($connection);
		$curpwd = "ssh2.sftp://$sftp".$this->pwd;
		$dh = @opendir($curpwd);
		if(!$dh) return new BaseObject(-1, 'msg_ftp_invalid_path');

		$list = array();
		while(($file = readdir($dh)) !== false)
		{
			if(!is_dir($curpwd.$file)) continue;
			$list[] = $file . "/";
		}
		closedir($dh);
		$this->add('list', $list);
	}

	function getInstallFTPList()
	{
		if(!($ftp_info = Context::getRequestVars()) || !$ftp_info->ftp_user || !$ftp_info->ftp_password) 
		{
			return new BaseObject(-1, 'msg_ftp_invalid_auth_info');
		}
		$this->pwd = $ftp_info->ftp_root_path;
		if(!$ftp_info->ftp_host)
		{
			$ftp_info->ftp_host = "127.0.0.1";
		}

		if($ftp_info->sftp == 'Y')
		{
			return $this->getSFTPList();
		}

		$_list = NULL;
		if(function_exists('ftp_connect'))
		{
			$connection = ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port);
			if(!$connection) return new BaseObject(-1, sprintf(Context::getLang('msg_ftp_not_connected'), 'host'));
			if(! @ftp_login($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
			{
				ftp_close($connection);	
				return new BaseObject(-1,'msg_ftp_invalid_auth_info');
			}

			if($ftp_info->ftp_pasv != "N") 
			{
				ftp_pasv($connection, true);
			}

			$_list = ftp_rawlist($connection, $this->pwd);
			ftp_close($connection);	
		}
		else
		{
			require_once(_XE_PATH_.'libs/ftp.class.php');
			$oFtp = new ftp();
			if($oFtp->ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port))
			{
				if($oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password))
				{
					$_list = $oFtp->ftp_rawlist($this->pwd);
					$oFtp->ftp_quit();
				}
				else
				{
					$oFtp->ftp_quit();
					return new BaseObject(-1,'msg_ftp_invalid_auth_info');
				}
			}
		}

		$list = array();
		if($_list)
		{
			foreach($_list as $k => $v)
			{
				$src = new stdClass(); 
				$src->data = $v;
				$res = Context::convertEncoding($src);
				$v = $res->data;
				if(strpos($v,'d') === 0 || strpos($v, '<DIR>')) $list[] = substr(strrchr($v,' '),1) . '/';
			}
		}
		$this->add('list', $list);
	}
}
/* End of file install.model.php */
/* Location: ./modules/install/install.model.php */
