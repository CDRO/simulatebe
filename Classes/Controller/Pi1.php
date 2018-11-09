<?php
namespace Cabag\Simulatebe\Controller;
/***************************************************************
*  Copyright notice
*
*  (c) 2000-2004 Mads Brunn (brunn@mail.dk)
*  (c) 2010-2011 Sonja Scholz (ss@cabag.ch)
*  (c) 2018 Tizian Schmidlin <st@cabag.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Plugin 'Simulate BE-session' for the extension 'simulatebe'.
 *
 * @author	Mads Brunn <brunn@mail.dk>
 * @author	Sonja Scholz <ss@cabag.ch>
 * @author	Jonas Dübi <jd@cabag.ch>
 */
class Pi1 extends AbstractUserAuthentication {
	/**
	 * Same as class name
	 * @var string
	 * @todo make this private by version 4.0.0
	 */
	public $prefixId = "tx_simulatebe_pi1";

	/**
	 * Path to this script relative to the extension dir.
	 * @var string
	 * @todo make this private by version 4.0.0 or completely remove it
	 */
	public $scriptRelPath = "pi1/class.tx_simulatebe_pi1.php";

	/**
	 * @var string
	 * @todo make this private by version 4.0.0
	 */
	public $extKey = "simulatebe";

	/**
	 * @var string
	 */
	private $beUserTable = 'be_users';

	/**
	 * @var string
	 */
	private $beUserSessionTable = 'be_sessions';

	/**
	 * @var string
	 */
	private $feUserTable = 'fe_users';


	/**
	 * Main function of the plugin.
	 *
	 * @param $content
	 * @param $conf array Configuration of the Plugin
	 * @return void
	 */
	public function main($content, $conf) {

		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['simulatebe']);

		if($extConf['simulatebeOnLinkParameter'] && empty($_GET[$extConf['simulatebeLinkParameter']])) {
			return;
		} elseif($extConf['simulatebeOnLinkParameter'] && !empty($_GET[$extConf['simulatebeLinkParameter']])) {
			$GLOBALS['TSFE']->loginUser = 1;
		}

		if (empty($conf['cookieName'])) {
			$conf['cookieName'] = 'simulatebe';
		}
		$beCookieName = BackendUserAuthentication::getCookieName();

		if(!$_COOKIE[$conf['cookieName']]) {
			// check if be user is logged in
			// $GLOBALS['TSFE']->beUserLogin is 0 if user has no access to current page but is logged in to the BE, this leads to a endless loop
			$BE_USER='';
			if ($_COOKIE[$beCookieName]) {         // If the backend cookie is set, we proceed and checks if a backend user is logged in.
				$BE_USER = GeneralUtility::makeInstance(FrontendBackendUserAuthentication::class);     // New backend user object
				$BE_USER->OS = TYPO3_OS;
				$BE_USER->lockIP = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'];
				$BE_USER->start();                      // Object is initialized
				$BE_USER->unpack_uc('');
				if ($BE_USER->user['uid'])      {
						$backendUserLoggedIn = 1;
				}
			}

			// CAB
			// Original code:
			// if ((!isset($_COOKIE['simulatebe'])) && $conf['allow'] && $GLOBALS['TSFE']->loginUser
			// && intval($GLOBALS['TSFE']->fe_user->user['tx_simulatebe_beuser']) && (GeneralUtility::_GP('logintype')=='login')) {
			if (!$backendUserLoggedIn && $conf['allow'] && $GLOBALS['TSFE']->loginUser) {
				$be_user_obj = GeneralUtility::makeInstance(BackendUserAuthentication::class);
				$be_user_obj->lockIP = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'];

					// CAB - SS: 28.04.10 - also look for a be_user where the feusername field contains the current username
				if (intval($GLOBALS['TSFE']->fe_user->user['tx_simulatebe_beuser'])) {
						// Let's get the record for the backend user we want to simulate in the frontend.
					$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'*',
								$this->beUserTable,
								($be_user_obj->checkPid ?
									'pid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($be_user_obj->checkPid_value) . ') AND ' :
									''
								) . ' uid=' . $GLOBALS['TSFE']->fe_user->user['tx_simulatebe_beuser'] . ' ' .
								$be_user_obj->user_where_clause()
							);
				} else {
						// Let's get the record for the backend user we want to simulate in the frontend.
					$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
								'*',
								$this->beUserTable,
								($be_user_obj->checkPid ?
									'pid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($be_user_obj->checkPid_value) . ') AND ' :
									''
								) . ' tx_simulatebe_feuserusername=\'' . $GLOBALS['TSFE']->fe_user->user['username'] . '\' ' .
								$be_user_obj->user_where_clause()
							);
				}

					// If no be_user is found, return.
				if ($tempuser = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
					$this->beuser = $tempuser;
				} else {
					return;
				}

					// Faking a be-session for our frontend user.
					// The be-session gets the same id and hashlock as the current fe-session. Don't know if this is usefull but..
				$insertFields = array(
						'ses_id' => $GLOBALS['TSFE']->fe_user->user['ses_id'],
						'ses_name' => $be_user_obj->name,
						'ses_iplock' => $this->beuser['disableIPlock'] ?
							'[DISABLED]' : $be_user_obj->ipLockClause_remoteIPNumber($be_user_obj->lockIP),
						'ses_hashlock' => $GLOBALS['TSFE']->fe_user->user['ses_hashlock'],
						'ses_userid' => $this->beuser[$be_user_obj->userid_column],
						'ses_tstamp' => $GLOBALS['EXEC_TIME'] + max(intval($extConf['simulatebeFakeTimeout']), 0)
				);
					// CAB - check if there already is an entry first
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'ses_id',
						$this->beUserSessionTable,
						'ses_id = \'' . $GLOBALS['TSFE']->fe_user->user['ses_id'] . '\''
				);
				if (($sessionRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) == FALSE) {
					$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->beUserSessionTable, $insertFields);
					$sessionRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
						'ses_id',
						$this->beUserSessionTable,
						'ses_id = \'' . $GLOBALS['TSFE']->fe_user->user['ses_id'] . '\''
          );
				}

					// Setting the cookies
					// Also check if the session array isset
				if (intval($GLOBALS['TYPO3_DB']->sql_affected_rows()) || is_array($sessionRow)) {
					$this->setCookie($be_user_obj->name, $GLOBALS['TSFE']->fe_user->user['ses_id'], 0);
					$this->setCookie($conf['cookieName'], $GLOBALS['TSFE']->fe_user->user['ses_id'], 0);

						// Reload
					if($extConf['simulatebeOnLinkParameter'] && !empty($_GET[$extConf['simulatebeLinkParameter']])) {
						header('Location: /typo3');
					} else {
					//reloads
						header("Location: " . GeneralUtility::getIndpEnv("TYPO3_REQUEST_URL"));
					}
				}
			}
		}

			// Logout
		if ((isset($_COOKIE[$beCookieName]) && ($_COOKIE[$conf['cookieName']] == $_COOKIE[$beCookieName]))
			&& $conf['allow']
			&& (!$GLOBALS['TSFE']->loginUser)
			&& (GeneralUtility::_GP('logintype')=='logout')) {
			$this->logout();
		}

		if($_COOKIE[$conf['cookieName']] && $_COOKIE[$conf['cookieName']] != $_COOKIE[$beCookieName]) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('ses_id', 'be_sessions', 'ses_id = \'' . $_COOKIE[$beCookieName] . '\' ');
			if(empty($res)) {
				$this->setCookie(
					$beCookieName,
					'',
					0
				);
				$this->setCookie(
					$conf['cookieName'],
					'',
					0
				);
				header("Location: " . GeneralUtility::getIndpEnv("TYPO3_REQUEST_URL"));
				die;
			}
		}
	}

	/**
	 * Sets the session cookie for the current disposal.
	 *
	 * @return	void
	 */
	protected function setSessionCookie() {
		$isSetSessionCookie = $this->isSetSessionCookie();
		$isRefreshTimeBasedCookie = $this->isRefreshTimeBasedCookie();

		// CAB ST: set it to 25 mimnutes
		/** CAB FIX on 2011-03-11 */
		$this->lifetime = 1500;

		$isSetSessionCookie = $this->isSetSessionCookie();
		$isRefreshTimeBasedCookie = true;
		/** CAB FIX end */

		if ($isSetSessionCookie || $isRefreshTimeBasedCookie) {
				// Get the domain to be used for the cookie (if any):
			$cookieDomain = $this->getCookieDomain();
			$cookieExpire = ($isRefreshTimeBasedCookie ? $GLOBALS['EXEC_TIME'] + $this->lifetime : 0);

			$this->setCookie($this->name, $this->id, $cookieExpire);

			if ($this->writeDevLog) {
				$devLogMessage = ($isRefreshTimeBasedCookie ? 'Updated Cookie: ' : 'Set Cookie: ') . $this->id;
				GeneralUtility::devLog($devLogMessage . ($cookieDomain ? ', ' . $cookieDomain : ''), 'AbstractUserAuthentication');
			}
		}
	}

	/**
	 * Log backend user off if the frontend user gets logged out.
	 *
	 * @return void
	 */
	public function logout()
	{
		$be_user_obj = GeneralUtility::makeInstance(BackendUserAuthentication::class);

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			$this->beUserSessionTable,
			'ses_id = \'' . $GLOBALS['TYPO3_DB']->quoteStr($_COOKIE[$beCookieName], $this->beUserSessionTable) . '\''
			. ' AND ses_name = \'' . $GLOBALS['TYPO3_DB']->quoteStr($be_user_obj->name, $this->beUserSessionTable) . '\''
		);

		if (empty($conf['cookieName'])) {
			$conf['cookieName'] = 'simulatebe';
		}

		$this->setCookie($be_user_obj->name, '', 0);
		$this->setCookie($conf['cookieName'], '', 0);
	}

	/**
	 * Sends a cookie to the client.
	 *
	 * This method will respect the TYPO3 configuration for secure cookies.
	 *
	 * An exception is thrown if the cookie needs to be send as a "secure" cookie
	 * but the connection was not made using HTTPS.
	 *
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 *
	 * @throws \TYPO3\CMS\Core\Exception
	 */
	protected function setCookie($name, $value, $expire = 0)
	{
		$domain = $this->getCookieDomain();
		$path = ($domain ? '/' : GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
		$secure = (bool)$GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieSecure'] && GeneralUtility::getIndpEnv('TYPO3_SSL');
		$httponly = (bool)$GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieHttpOnly'];

		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieSecure'] && !GeneralUtility::getIndpEnv('TYPO3_SSL')) {
			throw new \TYPO3\CMS\Core\Exception(
				'Cookie was not set since HTTPS was forced in $TYPO3_CONF_VARS[SYS][cookieSecure].',
				1254325546
		);
	}

		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}
}
