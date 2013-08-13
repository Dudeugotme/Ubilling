<?php
//frontend for cron task
if (isset($_GET['cronping'])) {
    $hostid_q="SELECT * from `ubstats` WHERE `key`='ubid'";
    $hostid=simple_query($hostid_q);
    if (!empty($hostid)) {
        $ubserial=$hostid['value'];
        //check for ubserial validity
        if ($_GET['cronping']==$ubserial) {
              $currenttime=time();
              $deadSwitches=zb_SwitchesRepingAll();
              zb_StorageSet('SWPINGTIME', $currenttime);
              //store dead switches log data
              if (!empty($deadSwitches)) {
                  zb_SwitchesDeadLog($currenttime, $deadSwitches);
              }
              die('SWITCH REPING DONE '.date("Y-m-d H:i:s"));
        } else {
            die('WRONG SERIAL');
        }
    }
}


if(cfr('SWITCHES')) {


if (isset($_POST['newswitchmodel'])) {
    if (cfr('SWITCHESEDIT')) {
    $modelid=$_POST['newswitchmodel'];
    $ip=$_POST['newip'];
    $desc=$_POST['newdesc'];
    $location=$_POST['newlocation'];
    $snmp=$_POST['newsnmp'];
    $geo=$_POST['newgeo'];
    ub_SwitchAdd($modelid, $ip, $desc, $location, $snmp,$geo);
    rcms_redirect("?module=switches");
     } else {
         show_window(__('Error'),__('Access denied'));
     }
}

if (isset($_GET['switchdelete'])) {
	if (!empty($_GET['switchdelete'])) {
          if (cfr('SWITCHESEDIT')) {
	ub_SwitchDelete($_GET['switchdelete']);
        rcms_redirect("?module=switches");
        } else {
            show_window(__('Error'),__('Access denied'));
        }
	}
}


if (!isset($_GET['edit'])) {
$swlinks='';    
if (cfr('SWITCHESEDIT')) {
    $swlinks.=  wf_modal(__('Add switch'), __('Add switch'), web_SwitchFormAdd(), 'ubButton', '470', '280');
}

if (cfr('SWITCHM')) {
    $swlinks.=wf_Link('?module=switchmodels', 'Available switch models', false, 'ubButton');
}

$swlinks.=wf_Link('?module=switches&forcereping=true', 'Force ping', false, 'ubButton');
$swlinks.=wf_Link('?module=switches&timemachine=true', 'Time machine', false, 'ubButton');

$alter_conf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
if ($alter_conf['SWYMAP_ENABLED']) {
  $swlinks.=wf_Link('?module=switchmap', 'Switches map', false, 'ubButton');
}


show_window('',  $swlinks);

if (!isset($_GET['timemachine'])) {
show_window(__('Available switches'), web_SwitchesShow());
} else {
    //show dead switch time machine
    if (!isset($_GET['snapshot'])) {
        $deadTimeMachine=  ub_JGetSwitchDeadLog();
        $timeMachine= wf_FullCalendar($deadTimeMachine);
        show_window(__('Dead switches time machine'),$timeMachine);
    } else {
        //showing dead switches snapshot
        ub_SwitchesTimeMachineShowSnapshot($_GET['snapshot']);
    }
    
}


} else {
    //editing switch form
    $switchid=vf($_GET['edit'],3);
    $allswitchmodels=zb_SwitchModelsGetAllTag();
    $switchdata=zb_SwitchGetData($switchid);

    
    //if someone edit switch 
    if (wf_CheckPost(array('editmodel'))) {
         if (cfr('SWITCHESEDIT')) {
        simple_update_field('switches', 'modelid', $_POST['editmodel'], "WHERE `id`='".$switchid."'");
        simple_update_field('switches', 'ip', $_POST['editip'], "WHERE `id`='".$switchid."'");
        simple_update_field('switches', 'location', $_POST['editlocation'], "WHERE `id`='".$switchid."'");
        simple_update_field('switches', 'desc', $_POST['editdesc'], "WHERE `id`='".$switchid."'");
        simple_update_field('switches', 'snmp', $_POST['editsnmp'], "WHERE `id`='".$switchid."'");
        simple_update_field('switches', 'geo', $_POST['editgeo'], "WHERE `id`='".$switchid."'");
        log_register('SWITCH CHANGE ['.$switchid.']'.' IP '.$_POST['editip']." LOC ".$_POST['editlocation']);
        rcms_redirect("?module=switches");
         } else {
             show_window(__('Error'),__('Access denied')); 
         }
    }
     
    $editinputs=wf_Selector('editmodel', $allswitchmodels, 'Model', $switchdata['modelid'], true);
    $editinputs.=wf_TextInput('editip', 'IP', $switchdata['ip'], true, 20);
    $editinputs.=wf_TextInput('editlocation', 'Location', $switchdata['location'], true, 30);
    $editinputs.=wf_TextInput('editdesc', 'Description', $switchdata['desc'], true, 30);
    $editinputs.=wf_TextInput('editsnmp', 'SNMP community', $switchdata['snmp'], true, 20);
    $editinputs.=wf_TextInput('editgeo', 'Geo location', $switchdata['geo'], true, 20);
 
    $editinputs.=wf_Submit('Save');
    $editform=wf_Form('', 'POST', $editinputs, 'glamour');
    show_window(__('Edit switch'),$editform);
    show_window('',  wf_Link('?module=switches', 'Back',true, 'ubButton'));
    }

}
else {
	show_error(__('Access denied'));
}
?>