<?php

class zpush extends db
{
    function getDevice($userid, $devid)
    {
        $this->query("SELECT * FROM as_devices WHERE user_id=? AND device_id=?", array('i', 's'), array($userid, $devid));
        return $this->next_record();
    }

    function addDevice($userid, $devid)
    {
        return $this->query("INSERT INTO as_devices (user_id, device_id) VALUES (?,?)", array('i', 's'), array($userid, $devid));
    }

    function removeDevice($userid, $devid)
    {
        return $this->query("DELETE FROM as_devices WHERE user_id=? AND device_id=?", array('i', 's'), array($userid, $devid));
    }

    function getPolicyKey($userid, $devid)
    {
        $device = $this->getDevice($userid, $devid);
        if ($device)
            return $device['policy_key'];

        return null;
    }

    function setPolicyKey($userid, $devid, $policykey)
    {
        return $this->query("UPDATE as_devices SET policy_key=? WHERE user_id=? AND device_id=?", array('i', 'i', 's'), array($policykey, $userid, $devid));
    }

    function getStatus($userid, $devid)
    {
        $device = $this->getDevice($userid, $devid);
        if ($device)
            return $device['status'];

        return null;
    }

    function setStatus($userid, $devid, $status)
    {
        return $this->query("UPDATE as_devices SET status=? WHERE user_id=? AND device_id=?", array('i', 'i', 's'), array($status, $userid, $devid));
    }

    function setDefaultAddressBook($userid, $addressbookid)
    {
        return $this->query("INSERT INTO as_default_addressbook (user_id, addressbook_id) VALUES (?,?) ON DUPLICATE KEY UPDATE addressbook_id=?", array('i', 'i', 'i'), array($userid, $addressbookid, $addressbookid));
    }

    function getDefaultAddressBook($userid)
    {
        $this->query("SELECT addressbook_id FROM as_default_addressbook WHERE user_id=?", array('i'), array($userid));
        $result = $this->next_record();
        if (result == null)
            return null;
        return $result['addressbook_id'];
    }

    function getAddressBooks($userid)
    {
        $this->query("SELECT addressbook_id FROM as_addressbooks WHERE user_id=?", array('i'), array($userid));
        $result = Array();
        while ($record = $this->next_record())
        {
            $result [] = $record['addressbook_id'];
        }
        return result;
    }

    function addAddressBook($userid, $addressbookid)
    {
        return $this->query("INSERT INTO as_addressbooks (user_id, addressbook_id) VALUES (?,?)", array('i', 'i'), array($userid, $addressbookid));
    }

    function removeAddressBook($userid, $addressbookid)
    {
        return $this->query("DELETE FROM as_addressbooks WHERE user_id=? AND addressbook_id=?", array('i', 'i'), array($userid, $addressbookid));
    }
}

?>