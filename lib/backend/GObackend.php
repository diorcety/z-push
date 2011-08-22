<?php
/***********************************************
 * File      :   GObackend.php
 * Project   :   Z-Push
 * Descr     :   This backend for Group-Office
 *
 * Created   :   19.08.2011
 *
 * Consult LICENSE file for details
 ************************************************/

include_once('diffbackend.php');

// The is an improved version of mimeDecode from PEAR that correctly
// handles charsets and charset conversion
include_once('mimeDecode.php');
require_once('z_RFC822.php');

class GOBackend extends BackendDiff
{
    /** Constants **/
    const FOLDER_ROOT = '0';
    const FOLDER_CONTACTS = 'Contacts';
    const FOLDER_TASKS = 'Tasks';
    const FOLDER_CALANDAR = 'Calandar';

    /** Variables **/
    var $_user = null;
    var $_userid = null;
    var $_username = null;
    var $_domain = null;
    var $_devid = null;
    var $_protocolversion = null;
    var $GO_SYNC;
    var $GO_ADDRESSBOOK;

    function GOBackend()
    {
        // Get module state in GO
        require_once(GO_PATH . 'Group-Office.php');

        global $GO_CONFIG;
        global $GO_MODULES;

        if (!isset($GO_MODULES->modules['z-push']))
            die('Z-Push module is not installed. Install it at Start menu -> Modules');

        // Create Connectors
        require_once($GO_CONFIG->module_path . 'z-push/classes/zpush.class.inc.php');
        require_once($GO_CONFIG->module_path . 'addressbook/classes/addressbook.class.inc.php');
        $this->GO_SYNC = new zpush();
        $this->GO_ADDRESSBOOK = new addressbook();
    }

    function Logon($username, $domain, $password)
    {
        global $GO_CONFIG;

        require_once($GO_CONFIG->class_path . 'base/users.class.inc.php');
        require_once($GO_CONFIG->class_path . 'base/auth.class.inc.php');
        $GO_AUTH = new GO_AUTH();
        $GO_USERS = new GO_USERS();

        $user_data = $GO_USERS->get_user_by_email($username . '@' . $domain);
        if (!$user_data)
            return false;

        if ($GO_AUTH->authenticate($user_data['username'], $password)) {
            $this->_userid = $user_data['id'];
            $this->_username = $user_data['username'];
            $this->_domain = $domain;
            $this->log("Logon $this->_username@$this->_domain: $this->_userid");
            return true;
        }

        return false;
    }

    function Logoff()
    {
        if ($this->_userid !== null) {
            $this->log("Logoff $this->_username@$this->_domain: $this->_userid");
            $this->_userid = null;
            $this->_username = null;
            $this->_domain = null;
            return true;
        }
        return false;
    }

    function Setup($user, $devid, $protocolversion)
    {
        $this->_user = $user;
        $this->_devid = $devid;
        $this->_protocolversion = $protocolversion;
        $this->log("Setup $this->_user $this->_devid $this->_protocolversion");
        return true;
    }

    function GetHierarchyImporter()
    {
        return new ImportHierarchyChangesDiff($this);
    }

    function GetContentsImporter($folderid)
    {
        return new ImportContentsChangesDiff($this, $folderid);
    }

    function GetExporter($folderid = false)
    {
        return new ExportChangesDiff($this, $folderid);
    }

    /**
     * Return a list of available folders
     *
     * @return array  An array of folder stats
     */
    function GetFolderList()
    {
        $folders = array();

        //Add Calendars
        $folders[] = $this->StatFolder(self::FOLDER_CALANDAR);
        $this->GO_ADDRESSBOOK->get_user_addressbooks($this->_userid);

        //Add AddressBooks
        $folders[] = $this->StatFolder(self::FOLDER_CONTACTS);
        while ($record = $this->GO_ADDRESSBOOK->next_record())
        {
            $folders[] = $this->StatFolder(self::FOLDER_CONTACTS . '/' . $record['id']);
        }

        //Add Tasks
        $folders[] = $this->StatFolder(self::FOLDER_TASKS);

        $this->log("==== FOLDER LIST ====");
        $this->log(print_r($folders, true));
        $this->log("=====================");
        return $folders;
    }

    /**
     * Retrieve folder
     *
     * @param string $id  The folder id
     *
     * @return SyncFolder
     */
    function GetFolder($id)
    {
        $folder = new SyncFolder();
        $folder->serverid = $id;
        $folder->parentid = $this->getFolderParent($id);

        if (substr($id, 0, strlen(self::FOLDER_CALANDAR)) == self::FOLDER_CALANDAR) {
            // CALENDARS
            if ($id == self::FOLDER_CALANDAR) {
                $folder->displayname = $this->getFolderId($id);
                $folder->type = SYNC_FOLDER_TYPE_APPOINTMENT;
            } else {
                $folder->displayname = $this->getFolderId($id);
                $folder->type = SYNC_FOLDER_TYPE_USER_APPOINTMENT;
            }

        } else if (substr($id, 0, strlen(self::FOLDER_CONTACTS)) == self::FOLDER_CONTACTS) {
            // CONTACTS
            if ($id == self::FOLDER_CONTACTS) {
                $folder->displayname = $this->getFolderId($id);
                $folder->type = SYNC_FOLDER_TYPE_CONTACT;
            } else {
                $addressBook = $this->GO_ADDRESSBOOK->get_addressbook($this->getFolderId($id));
                $folder->displayname = $addressBook['name'];
                $folder->type = SYNC_FOLDER_TYPE_USER_CONTACT;
            }

        } else if (substr($id, 0, strlen(self::FOLDER_TASKS)) == self::FOLDER_TASKS) {
            // TASKS
            if ($id == self::FOLDER_TASKS) {
                $folder->displayname = $this->getFolderId($id);
                $folder->type = SYNC_FOLDER_TYPE_TASK;
            } else {
                $folder->displayname = $this->getFolderId($id);
                $folder->type = SYNC_FOLDER_TYPE_USER_TASK;
            }
        }

        return $folder;
    }


    function getFolderParent($id)
    {
        $parts = explode('/', $id);
        if (sizeof($parts) < 2) {
            return self::FOLDER_ROOT;
        }
        else {
            $mod = $parts[sizeof($parts) - 1];
            unset($parts[sizeof($parts) - 1]);
            return $parent = implode('/', $parts);
        }
    }

    function getFolderId($id)
    {
        $parts = explode('/', $id);
        if (sizeof($parts) < 2) {
            return $id;
        }
        else {
            return $parts[sizeof($parts) - 1];
        }
    }

    /**
     * Stat folder. Note that since the only thing that can ever change for a
     * folder is the name, we use that as the 'mod' value.
     *
     * @param $id
     *
     * @return a stat hash
     */
    function StatFolder($id)
    {
        $stat = array();
        $stat['id'] = $id;
        $stat['mod'] = $this->getFolderId($id);
        $stat['parent'] = $this->getFolderParent($id);

        return $stat;
    }

    function GetAttachmentData($attname)
    {
        return false;
    }

    function SendMail($rfc822, $forward = false, $reply = false, $parent = false)
    {
        return true;
    }

    function GetWasteBasket()
    {
        return false;
    }

    function GetMessageList($folderid, $cutoffdate)
    {
        $messages = array();
        if (substr($folderid, 0, strlen(self::FOLDER_CALANDAR)) == self::FOLDER_CALANDAR) {
            if ($folderid == self::FOLDER_CALANDAR) {
            } else {
            }
        } else if (substr($folderid, 0, strlen(self::FOLDER_CONTACTS)) == self::FOLDER_CONTACTS) {
            if ($folderid == self::FOLDER_CONTACTS) {
            } else {
            }
        } else if (substr($folderid, 0, strlen(self::FOLDER_TASKS)) == self::FOLDER_TASKS) {
            if ($folderid == self::FOLDER_TASKS) {
            } else {
            }
        }
        return $messages;
    }

    function StatMessage($folderid, $id)
    {
        return false;
    }

    function GetMessage($folderid, $id, $truncsize, $mimesupport = 0)
    {
        return false;
    }

    function DeleteMessage($folderid, $id)
    {
        return false;
    }

    function SetReadFlag($folderid, $id, $flags)
    {
        return false;
    }

    function ChangeMessage($folderid, $id, $message)
    {
        return false;
    }

    function MoveMessage($folderid, $id, $newfolderid)
    {
        return false;
    }

    function MeetingResponse($requestid, $folderid, $error, &$calendarid)
    {
        return false;
    }

    /**
     * Returns array of items which contain contact information
     *
     * @param string $searchquery
     *
     * @return array
     */
    function getSearchResults($searchquery)
    {
        return false;
    }

    /**
     * Return a policy key for given user with a given device id.
     * If there is no combination user-deviceid available, a new key
     * should be generated.
     *
     * @param string $user
     * @param string $pass
     * @param string $devid
     *
     * @return unknown
     */
    function getPolicyKey($user, $pass, $devid)
    {
        if ($this->_userid === null) {
            debugLog("logon failed for user $user");
            return false;
        }

        $key = $this->GO_SYNC->getPolicyKey($this->_userid, $devid);

        // Generate new key
        if ($key === null) {
            $key = $this->generatePolicyKey();

            //android sends 'validate' as deviceid, it does not need to be added to the device list
            if (strcasecmp('validate', $devid) != 0) {
                $this->log("Add device $devid to $this->_username@$this->_domain");
                // Create a device registration if doesn't exists
                $this->GO_SYNC->addDevice($this->_userid, $devid);

                // Set default policy key
                $this->setPolicyKey($devid, $key);
            }
        }

        return $key;
    }

    /**
     * Set a new policy key for the given device id.
     *
     * @param string $policykey
     * @param string $devid
     * @return unknown
     */
    function setPolicyKey($policykey, $devid)
    {
        $this->log("Set $this->_username@$this->_domain device $devid police key: $policykey");
        return $this->GO_SYNC->setPolicyKey($this->_userid, $devid, $policykey);
    }

    /**
     * Return a device wipe status
     *
     * @param string $user
     * @param string $pass
     * @param string $devid
     * @return int
     */
    function getDeviceRWStatus($user, $pass, $devid)
    {
        if ($this->_userid === null) {
            debugLog("logon failed for user $user");
            return false;
        }

        $status = $this->GO_SYNC->getStatus($this->_userid, $devid);
        if ($status !== null)
            return $status;

        return false;
    }

    /**
     * Set a new rw status for the device
     *
     * @param string $user
     * @param string $pass
     * @param string $devid
     * @param string $status
     *
     * @return boolean
     */
    function setDeviceRWStatus($user, $pass, $devid, $status)
    {
        if ($this->_userid === null) {
            debugLog("Set rw status: logon failed for user $user");
            return false;
        }

        $this->log("Set $this->_username@$this->_domain device $devid status: $status");
        return $this->GO_SYNC->setStatus($this->_userid, $devid, $status);
    }

    function AlterPing()
    {
        return false;
    }

    function AlterPingChanges($folderid, &$syncstate)
    {
        return array();
    }

    private function log($message)
    {
        if (GO_LOGFILE != '') {
            @$fp = fopen(GO_LOGFILE, 'a+');
            @$date = strftime('%x %X');
            @fwrite($fp, "$date ['. getmypid() .'] : $message\n");
            @fclose($fp);
        }
    }
}

?>