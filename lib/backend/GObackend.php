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
    var $GO_AS;
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
        require_once($GO_MODULES->modules['z-push']['class_path'] . 'zpush.class.inc.php');
        require_once($GO_MODULES->modules['addressbook']['class_path'] . 'addressbook.class.inc.php');
        $this->GO_AS = new zpush();
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
            if (strcasecmp('validate', $this->_devid) != 0) {
                $this->GO_AS->updateLastSync($this->_userid, $this->_devid);
            }
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
        $this->log("Get Folder List");

        $folders = array();

        //Add Calendars
        $folders[] = $this->StatFolder(self::FOLDER_CALANDAR);

        //Add AddressBooks
        $folders[] = $this->StatFolder(self::FOLDER_CONTACTS);
        foreach ($this->GO_AS->getAddressBooks($this->_userid) as $addressBookId)
        {
            $folders[] = $this->StatFolder(self::FOLDER_CONTACTS . '/' . $addressBookId);
        }

        //Add Tasks
        $folders[] = $this->StatFolder(self::FOLDER_TASKS);

        $this->log('==== FOLDER LIST ====');
        $this->log(print_r($folders, true));
        $this->log('=====================');
        return $folders;
    }

    function isContacts($uri)
    {
        return substr($uri, 0, strlen(self::FOLDER_CONTACTS)) == self::FOLDER_CONTACTS;
    }

    function isCalendars($uri)
    {
        return substr($uri, 0, strlen(self::FOLDER_CALANDAR)) == self::FOLDER_CALANDAR;
    }

    function isTasks($uri)
    {
        return substr($uri, 0, strlen(self::FOLDER_TASKS)) == self::FOLDER_TASKS;
    }

    function getAddressBookId($uri)
    {
        if ($uri == self::FOLDER_CONTACTS) {
            $addressBookId = $this->GO_AS->getDefaultAddressBook($this->_userid);
            if ($addressBookId == null) {
                $this->log('Warning! No default AddressBook!');
                return null;
            }
        } else {
            $addressBookId = $this->getFolderId($uri);
        }
        return $addressBookId;
    }

    /**
     * Retrieve folder
     *
     * @param string $uri  The folder id
     *
     * @return SyncFolder
     */
    function GetFolder($uri)
    {
        $this->log("Get Folder $uri");

        $folder = new SyncFolder();
        $folder->serverid = $uri;
        $folder->parentid = $this->getFolderParent($uri);

        if ($this->isCalendars($uri)) {
            // CALENDARS
            if ($uri == self::FOLDER_CALANDAR) {
                $folder->displayname = $this->getFolderId($uri);
                $folder->type = SYNC_FOLDER_TYPE_APPOINTMENT;
            } else {
                $folder->displayname = $this->getFolderId($uri);
                $folder->type = SYNC_FOLDER_TYPE_USER_APPOINTMENT;
            }
        } else if ($this->isContacts($uri)) {
            // CONTACTS
            if ($uri == self::FOLDER_CONTACTS)
                $folder->type = SYNC_FOLDER_TYPE_CONTACT;
            else
                $folder->type = SYNC_FOLDER_TYPE_USER_CONTACT;

            $addressBookId = $this->getAddressBookId($uri);
            $addressBook = $this->GO_ADDRESSBOOK->get_addressbook($addressBookId);
            if ($addressBook == null) {
                $this->log('Error! Invalid AddressBook: ' . $addressBookId);
                return null;
            }
            $folder->displayname = $addressBook['name'];
        } else if ($this->isTasks($uri)) {
            // TASKS
            if ($uri == self::FOLDER_TASKS) {
                $folder->displayname = $this->getFolderId($uri);
                $folder->type = SYNC_FOLDER_TYPE_TASK;
            } else {
                $folder->displayname = $this->getFolderId($uri);
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
        $this->log("Get Waste Basket");

        return false;
    }

    function GetMessageList($uri, $cutoffdate)
    {
        $this->log("Get Message List \"$uri\" ($cutoffdate)");

        $messages = array();
        if ($this->isCalendars($uri)) {
            if ($uri == self::FOLDER_CALANDAR) {
            } else {
            }
        } else if ($this->isContacts($uri)) {
            $addressBookId = $this->getAddressBookId($uri);
            $this->GO_ADDRESSBOOK->get_contacts($addressBookId);
            while ($record = $this->GO_ADDRESSBOOK->next_record())
            {
                $message = array();
                $message['mod'] = $record['mtime'];
                $message['id'] = $record['id'];
                $message['flags'] = 0;

                $messages[] = $message;
            }
        } else if ($this->isTasks($uri)) {
            if ($uri == self::FOLDER_TASKS) {
            } else {
            }
        }
        return $messages;
    }

    function StatMessage($uri, $id)
    {
        $message = array();
        if ($this->isCalendars($uri)) {
        } else if ($this->isContacts($uri)) {
            $record = $this->GO_ADDRESSBOOK->get_contact($id);
            $message['mod'] = $record['mtime'];
            $message['id'] = $record['id'];
            $message['flags'] = 0;
        } else if ($this->isTasks($uri)) {
        }
        return $message;
    }

    function CreateSyncContact($vars)
    {
        $contact = new SyncContact();

        $contact->firstname = $vars['first_name'];
        $contact->middlename = $vars['middle_name'];
        $contact->lastname = $vars['last_name'];
        $contact->title = $vars['title'];
        $contact->birthday = $vars['birthday'];
        $contact->email1address = $vars['email'];
        $contact->email2address = $vars['email2'];
        $contact->email3address = $vars['email3'];
        $contact->department = $vars['department'];
        $contact->jobtitle = $vars['function'];
        $contact->homephonenumber = $vars['home_phone'];
        $contact->businessphonenumber = $vars['work_phone'];
        $contact->homefaxnumber = $vars['fax'];
        $contact->businessfaxnumber = $vars['work_fax'];
        $contact->mobilephonenumber = $vars['cellular'];
        $contact->homecountry = $vars['country'];
        $contact->homestate = $vars['state'];
        $contact->homecity = $vars['city'];
        $contact->homepostalcode = $vars['zip'];
        $contact->homestreet = $vars['address'];

        return $contact;
    }

    function CreateGOContact($contact, $addressbookId, $id = false)
    {
        $vars = Array();

        if ($id != false)
            $vars['id'] = $id;
        $vars['addressbook_id'] = $addressbookId;
        $vars['user_id'] = $this->_userid;
        $vars['first_name'] = $contact->firstname;
        $vars['middle_name'] = $contact->middlename;
        $vars['last_name'] = $contact->lastname;
        $vars['title'] = $contact->title;
        $vars['birthday'] = $contact->birthday;
        $vars['email'] = $contact->email1address;
        $vars['email2'] = $contact->email2address;
        $vars['email3'] = $contact->email3address;
        $vars['department'] = $contact->department;
        $vars['function'] = $contact->jobtitle;
        $vars['home_phone'] = $contact->homephonenumber;
        $vars['work_phone'] = $contact->businessphonenumber;
        $vars['fax'] = $contact->homefaxnumber;
        $vars['work_fax'] = $contact->businessfaxnumber;
        $vars['cellular'] = $contact->mobilephonenumber;
        $vars['country'] = $contact->homecountry;
        $vars['state'] = $contact->homestate;
        $vars['city'] = $contact->homecity;
        $vars['zip'] = $contact->homepostalcode;
        $vars['address'] = $contact->homestreet;

        return $vars;
    }

    function GetMessage($uri, $id, $truncsize, $mimesupport = 0)
    {
        $this->log("Get Message $id from $uri");

        if ($this->isCalendars($uri)) {
        } else if ($this->isContacts($uri)) {
            $record = $this->GO_ADDRESSBOOK->get_contact($id);
            if ($record != null)
                return $this->CreateSyncContact($record);
        } else if ($this->isTasks($uri)) {
        }
        return null;
    }

    function DeleteMessage($uri, $id)
    {
        $this->log("Delete Message $id from $uri");

        if ($this->isCalendars($uri)) {
        } else if ($this->isContacts($uri)) {
            return $this->GO_ADDRESSBOOK->delete_contact($id) == 1 ? true : false;
        } else if ($this->isTasks($uri)) {
        }
        return false;
    }

    function SetReadFlag($folderid, $id, $flags)
    {
        return false;
    }

    function ChangeMessage($uri, $id, $message)
    {
        $this->log("Change Message $id from $uri");

        if ($this->isCalendars($uri)) {
        } else if ($this->isContacts($uri)) {
            $addressBookId = $this->getAddressBookId($uri);
            $contact = $this->CreateGOContact($message, $addressBookId, $id);
            if ($id == false) {
                $id = $this->GO_ADDRESSBOOK->add_contact($contact);
            } else {
                $this->GO_ADDRESSBOOK->update_contact($contact);
            }
            return $this->StatMessage($uri, $id);
        } else if ($this->isTasks($uri)) {
        }
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

        $key = $this->GO_AS->getPolicyKey($this->_userid, $devid);

        // Generate new key
        if ($key === null) {
            $key = $this->generatePolicyKey();

            //android sends 'validate' as deviceid, it does not need to be added to the device list
            if (strcasecmp('validate', $devid) != 0) {
                $this->log("Add device $devid to $this->_username@$this->_domain");

                global $devtype, $useragent;
                // Create a device registration if doesn't exists
                $this->GO_AS->addDevice($this->_userid, $devid, $devtype, $useragent);

                // Set default policy key
                $this->setPolicyKey($key, $devid);
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
        return $this->GO_AS->setPolicyKey($this->_userid, $devid, $policykey);
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

        $status = $this->GO_AS->getStatus($this->_userid, $devid);
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
        return $this->GO_AS->setStatus($this->_userid, $devid, $status);
    }

    function AlterPing()
    {
        return false;
    }

    function AlterPingChanges($folderid, &$syncstate)
    {
        return array();
    }

    private
    function log($message)
    {
        if (GO_LOGFILE != '') {
            @$fp = fopen(GO_LOGFILE, 'a+');
            @$date = strftime('%x %X');
            @fwrite($fp, "$date [". getmypid() ."] : $message\n");
            @fclose($fp);
        }
    }
}

?>
