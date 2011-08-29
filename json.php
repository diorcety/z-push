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

require_once ($GO_MODULES->modules['z-push']['class_path'] . 'zpush.class.inc.php');
$notes = new notes();


$task = isset($_REQUEST['task']) ? ($_REQUEST['task']) : '';

try {

    switch ($task)
    {
        /* {TASKSWITCH} */
    }
} catch (Exception $e)
{
    $response['feedback'] = $e->getMessage();
    $response['success'] = false;
}
echo json_encode($response);