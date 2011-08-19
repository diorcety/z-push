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
        $device = Array();
        $device['user_id'] = $userid;
        $device['device_id'] = $devid;
        $device['policy_key'] = $policykey;

        return $this->insert_row('sync_devices', $device);
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
        return $this->query("UPDATE as_devices SET policy_key=? WHERE user_id=? AND device_id=?", array('i', 'i', 's'), array($policykey ,$userid, $devid));
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
        return $this->query("UPDATE as_devices SET status=? WHERE user_id=? AND device_id=?", array('i', 'i', 's'), array($status ,$userid, $devid));
    }
}

?>