<?php
/**
 * Copyright Intermesh
 *
 * This file is part of Group-Office. You should have received a copy of the
 * Group-Office license along with Group-Office. See the file /LICENSE.TXT
 *
 * If you have questions write an e-mail to info@intermesh.nl
 *
 * @version $Id: json.php 7083 2011-03-28 13:40:21Z mschering $
 * @copyright Copyright Intermesh
 * @author Merijn Schering <mschering@intermesh.nl>
 */

require('../../Group-Office.php');

$GO_SECURITY->json_authenticate('z-push');

require_once($GO_MODULES->modules['z-push']['class_path'] . 'zpush.class.inc.php');
require_once($GO_MODULES->modules['addressbook']['class_path'] . 'addressbook.class.inc.php');
require_once($GO_MODULES->modules['calendar']['class_path'] . 'calendar.class.inc.php');

$GO_AS = new zpush();
$GO_ADDRESSBOOK = new addressbook();
$GO_CALENDAR = new calendar();

$task = isset($_REQUEST['task']) ? ($_REQUEST['task']) : '';

try {

    switch ($task)
    {
        case 'devices':
            $response['results'] = array();
            foreach ($GO_AS->getDevices($GO_SECURITY->user_id) as $deviceid)
            {
                $result = $GO_AS->getDevice($GO_SECURITY->user_id, $deviceid);

                $device = array();
                $device['id'] = $result['device_id'];
                $device['device'] = $result['device'];
                $device['agent'] = $result['agent'];
                $device['first_sync'] = $result['first_sync'];
                $device['last_sync'] = $result['last_sync'];
                $device['status'] = $result['status'];
                $response['results'][] = $device;
            }
            $response['total'] = sizeof($response['results']);
            $response['success'] = true;
            break;

        case 'addressbooks':
            // Commit
            if (isset($_REQUEST['results'])) {
                $results = json_decode($_REQUEST['results'], true);
                foreach ($results as $addressbook)
                {
                    if ($addressbook['default']) {
                        $GO_AS->setDefaultAddressBook($GO_SECURITY->user_id, $addressbook['id']);
                        $GO_AS->removeAddressBook($GO_SECURITY->user_id, $addressbook['id']);
                    } else {
                        if ($addressbook['synchronize']) {
                            $GO_AS->addAddressBook($GO_SECURITY->user_id, $addressbook['id']);
                        } else {
                            $GO_AS->removeAddressBook($GO_SECURITY->user_id, $addressbook['id']);
                        }
                    }
                }
                $response['success'] = true;
            } else {
                $response['results'] = array();
                $synchronized = $GO_AS->getAddressBooks($GO_SECURITY->user_id);
                $default = $GO_AS->getDefaultAddressBook($GO_SECURITY->user_id);

                $GO_ADDRESSBOOK->get_user_addressbooks($GO_SECURITY->user_id);
                while ($GO_ADDRESSBOOK->next_record())
                {
                    $result = $GO_ADDRESSBOOK->record;

                    $addressbook = array();
                    $addressbook['id'] = $result['id'];
                    $addressbook['name'] = $result['name'];
                    $addressbook['synchronize'] = in_array($result['id'], $synchronized);
                    $addressbook['default'] = ($default == $result['id']) ? true : false;

                    $response['results'][] = $addressbook;
                }
                $response['total'] = sizeof($response['results']);
                $response['success'] = true;
            }
            break;

        case 'calendars':
            // Commit
            if (isset($_REQUEST['results'])) {
                $results = json_decode($_REQUEST['results'], true);
                foreach ($results as $calendar)
                {
                    if ($calendar['default']) {
                        $GO_AS->setDefaultCalendar($GO_SECURITY->user_id, $calendar['id']);
                        $GO_AS->removeCalendar($GO_SECURITY->user_id, $calendar['id']);
                    }
                    else {
                        if ($calendar['synchronize']) {
                            $GO_AS->addCalendar($GO_SECURITY->user_id, $calendar['id']);
                        } else {
                            $GO_AS->removeCalendar($GO_SECURITY->user_id, $calendar['id']);
                        }
                    }
                }
                $response['success'] = true;
            } else {
                $response['results'] = array();
                $synchronized = $GO_AS->getCalendars($GO_SECURITY->user_id);
                $default = $GO_AS->getDefaultCalendar($GO_SECURITY->user_id);

                $GO_CALENDAR->get_user_calendars($GO_SECURITY->user_id);
                while ($GO_CALENDAR->next_record())
                {
                    $result = $GO_CALENDAR->record;

                    $calendar = array();
                    $calendar['id'] = $result['id'];
                    $calendar['name'] = $result['name'];
                    $calendar['synchronize'] = in_array($result['id'], $synchronized);
                    $calendar['default'] = ($default == $result['id']) ? true : false;

                    $response['results'][] = $calendar;
                }
                $response['total'] = sizeof($response['results']);
                $response['success'] = true;
            }
            break;
        /* {TASKSWITCH} */
    }
} catch (Exception $e)
{
    $response['feedback'] = $e->getMessage();
    $response['success'] = false;
}
echo json_encode($response);