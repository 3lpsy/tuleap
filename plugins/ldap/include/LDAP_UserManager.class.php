<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'LDAP_UserDao.class.php';
require_once 'LDAP.class.php';
require_once 'common/user/UserManager.class.php';

/**
 * Manage interaction between an LDAP group and Codendi user_group.
 */
class LDAP_UserManager {
    /**
     * @type LDAP
     */
    private $ldap;

    private $ldapResultCache;

    /**
     * Constructor
     *
     * @param LDAP $ldap Ldap access object
     */
    function __construct(LDAP $ldap) {
        $this->ldap = $ldap;
        $this->ldapResultCache = array();
    }

    /**
     * Get LDAPResult object corresponding to an LDAP ID
     *
     * @param  $ldapId    The LDAP identifier
     * @return LDAPResult
     */
    function getLdapFromLdapId($ldapId) {
        if (!isset($this->ldapResultCache[$ldapId])) {
            $lri = $this->getLdap()->searchEdUid($ldapId);
            if ($lri->count() == 1) {
                $this->ldapResultCache[$ldapId] = $lri->current();
            } else {
                $this->ldapResultCache[$ldapId] = false;
            }
        }
        return $this->ldapResultCache[$ldapId];
    }

    /**
     * Get LDAPResult object corresponding to a User object
     * 
     * @param  User $user
     * @return LDAPResult
     */
    function getLdapFromUser($user) {
        if ($user && !$user->isAnonymous()) {
            return $this->getLdapFromLdapId($user->getLdapId());
        } else {
            return false;
        }
    }

    /**
     * Get LDAPResult object corresponding to a user name
     *
     * @param  $userName  The user name
     * @return LDAPResult
     */
    function getLdapFromUserName($userName) {
        $user = $this->getUserManager()->getUserByUserName($userName);
        return $this->getLdapFromUser($user);
    }

    /**
     * Get LDAPResult object corresponding to a user id
     *
     * @param  $userId    The user id
     * @return LDAPResult
     */
    function getLdapFromUserId($userId) {
        $user = $this->getUserManager()->getUserById($userId);
        return $this->getLdapFromUser($user);
    }

    /**
     * Get a User object from an LDAP result
     *
     * @param LDAPResult $lr The LDAP result
     *
     * @return User
     */
    function getUserFromLdap(LDAPResult $lr) {
        $user = $this->getUserManager()->getUserByLdapId($lr->getEdUid());
        if(!$user) {
            $user = $this->createAccountFromLdap($lr);
        }
        return $user;
    }

    /**
     * Get the list of Codendi users corresponding to the given list of LDAP users.
     *
     * When a user doesn't exist, his account is created automaticaly.
     *
     * @param Array $ldapIds
     * @return Array
     */
    function getUserIdsForLdapUser($ldapIds) {
        $userIds = array();
        $dao = $this->getDao();
        foreach($ldapIds as $lr) {
            $user = $this->getUserManager()->getUserByLdapId($lr->getEdUid());
            if($user) {
                $userIds[$user->getId()] = $user->getId();
            } else {
                $user = $this->createAccountFromLdap($lr);
                if ($user) {
                    $userIds[$user->getId()] = $user->getId();
                }
            }
        }
        return $userIds;
    }

    /**
     * Return an array of user ids corresponding to the give list of user identifiers
     *
     * @param String $userList A comma separated list of user identifiers
     *
     * @return Array
     */
    function getUserIdsFromUserList($userList) {
        $userIds = array();
        $userList = array_map('trim', split('[,;]', $userList));
        foreach($userList as $u) {
            $user = $this->getUserManager()->findUser($u);
            if($user) {
                $userIds[] = $user->getId();
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_ldap', 'user_manager_user_not_found', $u));
            }
        }
        return $userIds;
    }

    /**
     * Return LDAP logins stored in DB corresponding to given userIds.
     * 
     * @param Array $userIds Array of user ids
     * @return Array ldap logins
     */
    function getLdapLoginFromUserIds(array $userIds) {
        $dao = $this->getDao();
        return $dao->searchLdapLoginFromUserIds($userIds);
    }

    /**
     * Generate a valid, not used Codendi login from a string.
     *
     * @param String $uid User identifier
     * @return String
     */
    function generateLogin($uid) {
        $account_name = $this->getLoginFromString($uid);
        $uid = $account_name;
        $i=2;
        while($this->userNameIsAvailable($uid) !== true) {
            $uid = $account_name.$i;
            $i++;
        }
        return $uid;
    }

    /**
     * Check if a given name is not already a user name or a project name
     *
     * This should be in UserManager
     *
     * @param String $name Name to test
     * @return Boolean
     */
    function userNameIsAvailable($name) {
        $dao = $this->getDao();
        return $dao->userNameIsAvailable($name);
    }

    /**
     * Return a valid Codendi user_name from a given string
     *
     * @param String $uid Identifier to convert
     * @return String
     */
    function getLoginFromString($uid) {
        $name = utf8_decode($uid);
        $name = strtr($name, utf8_decode(' .:;,?%^*(){}[]<>+=$àâéèêùûç'), '____________________aaeeeuuc');
        $name = str_replace("'", "", $name);
        $name = str_replace('"', "", $name);
        $name = str_replace('/', "", $name);
        $name = str_replace('\\', "", $name);
        return strtolower($name);
    }

    /**
     * Create user account based on LDAPResult info.
     *
     * @param  LDAPResult $lr
     * @return User
     */
    function createAccountFromLdap(LDAPResult $lr) {
    	return $this->createAccount($lr->getEdUid(), $lr->getLogin(), $lr->getCommonName(), $lr->getEmail());
    }

    /**
     * Create user account based on LDAP info.
     *
     * @param  String $eduid
     * @param  String $uid
     * @param  String $cn
     * @param  String $email
     * @return User
     */
    function createAccount($eduid, $uid, $cn, $email) {
        include_once 'account.php';

        if(trim($uid) == '' || trim($eduid) == '') {
            return false;
        }

        // Generate a Codendi login
        $form_loginname = $this->generateLogin($uid);
        // Generates a pseudo-random password. Its not full secure but its
        // better than nothing.
        $password = md5((string)mt_rand(10000, 999999).time());
        $status = $this->getLdap()->getLDAPParam('default_user_status');
        $register_purpose= 'LDAP';
        $unixStatus = 'S';
        $mailSite = 0;
        $mailVa = 0;
        $confirm_hash='';
        $timezone='GMT';

        // Create Codendi account
        if($new_userid = account_create($form_loginname,
                                        $password,
                                        $eduid,
                                        $cn,
                                        $register_purpose,
                                        $email,
                                        $status,
                                        $confirm_hash,
                                        $mailSite,
                                        $mailVa,
                                        $timezone,
                                        $GLOBALS['Language']->getText('conf','language_id'),
                                        $unixStatus)) {
            // Create an entry in the ldap user db
            $ldapUserDao = $this->getDao();
            $ldapUserDao->createLdapUser($new_userid, 0, $uid);
            return $this->getUserManager()->getUserById($new_userid);
        }
        return false;
    }

    /**
     * Synchronize user account with LDAP informations
     *
     * @param  User       $user
     * @param  LDAPResult $lr
     * @param  String     $password
     * @return Boolean
     */
    function synchronizeUser(User $user, LDAPResult $lr, $password) {
        // Generic operations on synchro: update password, name and email
        $user->setPassword($password);
        $user->setRealName($lr->getCommonName());
        $user->setEmail($lr->getEmail());

        // Allow sites to configure whatever they want
        include($GLOBALS['Language']->getContent('synchronize_user','en_US', 'ldap', '.php'));

        // Perform DB update
        $userUpdated = $this->getUserManager()->updateDb($user);

        $ldapUpdated = $this->updateLdapUid($user->getId(), $lr->getLogin());
        return ($userUpdated || $ldapUpdated);
    }

    /**
     * Store new LDAP login in database
     * 
     * @param Integer $userId  User id
     * @param String  $ldapUid User LDAP login
     * 
     * @return Boolean
     */
    function updateLdapUid($userId, $ldapUid) {
        return $this->getDao()->updateLdapUid($userId, $ldapUid);
    }

    /**
     * Wrapper for DAO
     *
     * @return LDAP_UserDao
     */
    function getDao()
    {
        return new LDAP_UserDao(CodendiDataAccess::instance());
    }

    /**
     * Wrapper for LDAP object
     *
     * @return LDAP
     */
    protected function getLdap()
    {
        return $this->ldap;
    }

    /**
     * Wrapper for UserManager object
     *
     * @return UserManager
     */
    protected function getUserManager()
    {
        return UserManager::instance();
    }
}

?>