<?php
	/***************************************************************************\
	* eGroupWare - SambaAdmin                                                   *
	* http://www.egroupware.org                                                 *
	* http://www.linux-at-work.de                                               *
	* Written by : Lars Kneschke [lkneschke@linux-at-work.de]                   *
	* -------------------------------------------------                         *
	* This program is free software; you can redistribute it and/or modify it   *
	* under the terms of the GNU General Public License as published by the     *
	* Free Software Foundation; either version 2 of the License, or (at your    *
	* option) any later version.                                                *
	\***************************************************************************/
	/* $Id$ */

	class bosambaadmin
	{
		var $sessionData;
		var $LDAPData;
		/**
		 * @var sosambaadmin
		 */
		var $sosambaadmin;

		function __construct()
		{
			$this->sosambaadmin = CreateObject('sambaadmin.sosambaadmin');

			$this->restoreSessionData();
		}

		function checkLDAPSetup()
		{
			return $this->sosambaadmin->checkLDAPSetup();
		}

		function changePassword($_data)
		{
			// are we using LDAP for accounts or authentication (fallback = LDAP with fallback to SQL)
			if (empty($GLOBALS['egw_info']['server']['ldap_host']) ||
				!($GLOBALS['egw_info']['server']['account_repository'] == 'ldap' ||
					in_array($GLOBALS['egw_info']['server']['auth_type'], array('ldap', 'fallback'))))
			{
				return false;
			}
			return $this->sosambaadmin->changePassword($_data['account_id'], $_data['new_passwd']);
		}

		function deleteWorkstation($_workstations)
		{
			return $this->sosambaadmin->deleteWorkstation($_workstations);
		}

		function expirePassword($_accountID)
		{
			return $this->sosambaadmin->expirePassword($_accountID);
		}

		function getUserData($_accountID, $_usecache)
		{
			if ($_usecache)
			{
				$userData = $this->userSessionData[$_accountID];
			}
			else
			{
				$userData = $this->sosambaadmin->getUserData($_accountID);
				$this->userSessionData[$_accountID] = $userData;
				$this->saveSessionData();
			}
			return $userData;
		}

		function getWorkstationData($_uidnumber)
		{
			return $this->sosambaadmin->getWorkstationData($_uidnumber);
		}

		function getWorkstationList($_start, $_sort, $_order, $_searchString)
		{
			return $this->sosambaadmin->getWorkstationList($_start, $_sort, $_order, $_searchString);
		}

		function restoreSessionData()
		{
			$this->sessionData = $GLOBALS['egw']->session->appsession('session_data');
			$this->userSessionData = $GLOBALS['egw']->session->appsession('user_session_data');
		}

		function saveSessionData()
		{
			$GLOBALS['egw']->session->appsession('session_data','',$this->sessionData);
			$GLOBALS['egw']->session->appsession('user_session_data','',$this->userSessionData);
		}

		function saveUserData($_accountID, $_formData)
		{
			return $this->sosambaadmin->saveUserData($_accountID, $_formData);
		}

		function updateAccount($accountData)
		{
			// are we using LDAP for accounts
			if (empty($GLOBALS['egw_info']['server']['ldap_host']) ||
				$GLOBALS['egw_info']['server']['account_repository'] != 'ldap')
			{
				return false;
			}
			if(($accountID = (int)$accountData['account_id']))
			{
				$config = config::read('sambaadmin');

				$oldAccountData = $this->getUserData($accountID,false);

				// account_status
				if(!$oldAccountData['sambahomedrive'] && $config['samba_homedrive'])
					$accountData['sambahomedrive']		= $config['samba_homedrive'];
				if(!$oldAccountData['sambahomepath'] && $config['samba_homepath'])
					$accountData['sambahomepath']		= $config['samba_homepath'].$accountData['account_lid'];
				if(!$oldAccountData['sambalogonscript'] && $config['samba_logonscript'])
					$accountData['sambalogonscript']	= $config['samba_logonscript'];
				if(!$oldAccountData['sambaprofilepath'] && $config['samba_profilepath'])
					$accountData['sambaprofilepath']	= $config['samba_profilepath'].$accountData['account_lid'];
			}
			$accountData['status']				= $accountData['account_status'] == 'A' ? 'activated' : 'deactivated';

			return $this->sosambaadmin->saveUserData($accountID, $accountData);
		}

		function updateGroup($data)
		{
			// are we using LDAP for accounts
			if (empty($GLOBALS['egw_info']['server']['ldap_host']) ||
				$GLOBALS['egw_info']['server']['account_repository'] != 'ldap')
			{
				return false;
			}
			if(($accountID = (int)$data['account_id']))
			{
				return $this->sosambaadmin->updateGroup($accountID);
			}
			return false;
		}

		function updateWorkstation($_newData)
		{
			if(!$this->verifyData($_newData))
				return false;

			return $this->sosambaadmin->updateWorkstation($_newData);
		}

		function verifyData($_newData)
		{
			unset($_newData);

			return true;
		}

		function admin()
		{
			// are we using LDAP for accounts
			if (!empty($GLOBALS['egw_info']['server']['ldap_host']) &&
				$GLOBALS['egw_info']['server']['account_repository'] == 'ldap')
			{
				$appname = 'sambaadmin';
				$file = array(
					'Site Configuration'	=> egw::link('/index.php','menuaction=admin.uiconfig.index&appname='.$appname),
					//'check ldap setup (experimental!!!)'	=> egw::link('/index.php','menuaction=sambaadmin.uisambaadmin.checkLDAPSetup'),
				);
				display_section($appname,$appname,$file);
			}
		}

		function edit_user()
		{
			global $menuData;

			// are we using LDAP for accounts
			if (!empty($GLOBALS['egw_info']['server']['ldap_host']) &&
				$GLOBALS['egw_info']['server']['account_repository'] == 'ldap')
			{
				$menuData[] = Array
				(
					'description'	=> 'samba settings',
					'url'		=> '/index.php',
					'extradata'	=> 'menuaction=sambaadmin.uiuserdata.editUserData',
					'popup'     => '640x200',
				);
			}
		}
	}
