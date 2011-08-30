<?php
/**
 * Copyright Intermesh
 *
 * This file is part of Group-Office. You should have received a copy of the
 * Group-Office license along with Group-Office. See the file /LICENSE.TXT
 *
 * If you have questions write an e-mail to info@intermesh.nl
 *
 * @version $Id: action.php 5868 2010-10-16 14:41:18Z mschering $
 * @copyright Copyright Intermesh
 * @author Merijn Schering <mschering@intermesh.nl>
 */


require_once("../../Group-Office.php");
$GO_SECURITY->json_authenticate('z-push');

require_once($GO_MODULES->modules['z-push']['class_path'] . 'zpush.class.inc.php');
require_once($GO_MODULES->modules['addressbook']['class_path'] . 'addressbook.class.inc.php');

//require_once ($GO_LANGUAGE->get_language_file('z-push'));
$GO_AS = new zpush();

try {

    switch ($_REQUEST['task'])
    {
        case 'delete_device':
            $id = isset($_REQUEST['id']) ? ($_REQUEST['id']) : null;
            if ($id !== null) {
                $result = $GO_AS->removeDevice($GO_SECURITY->user_id, $id);
                $response['success'] = $result;
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