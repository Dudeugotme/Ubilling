<?php
if (cfr('MTSIGMON')) {
    
    function zb_MsmGetMonitoredDevices() {
        $query="SELECT * from `switches` WHERE `desc` LIKE '%MTSIGMON%'";
        $alldevices=  simple_queryall($query);
        $result=array();
        
        if (!empty($alldevices)) {
            foreach ($alldevices as $io=>$eachdevice) {
                $result[$eachdevice['id']]['ip']=$eachdevice['ip'];
                $result[$eachdevice['id']]['location']=$eachdevice['location'];
                $result[$eachdevice['id']]['community']=$eachdevice['snmp'];
            }
        }
        
        return ($result);
    }
    
    function zb_MsmDeviceQuery($ip,$community) {
        $oid='.1.3.6.1.4.1.14988.1.1.1.2.1.3';
        $mask_mac=false;
        $result=array();
        
        @$rawsnmp=snmpwalkoid($ip, $community, $oid);
                
        if (!empty($rawsnmp)) {
            if (is_array($rawsnmp)) {
                  foreach ($rawsnmp as $indexOID => $rssi) {
                        $oidarray=explode(".", $indexOID);
                        $end_num=sizeof($oidarray);
                        $mac='';
                        
                        for ($counter=2;$counter<8;$counter++) {
                                $temp=sprintf('%02x', $oidarray[$end_num-$counter]);
                                                
                                if (($counter <5) && $mask_mac)
                                        $mac=":xx$mac";
                                else if ($counter==7)
                                        $mac="$temp$mac";
                                else 
                                        $mac=":$temp.$mac";
                        }
                        
                        
                        $mac=str_replace('.','',$mac);
                        $mac=trim($mac);
                        $rssi=str_replace('INTEGER:','',$rssi);
                        $rssi=trim($rssi);
                        $result[$mac]=$rssi;
                        

                    }

            } 
        }
        
        return ($result);
        
    }
    
// Main code part
    
 $alter_config=rcms_parse_ini_file(CONFIG_PATH.'/alter.ini');
 if ($alter_config['MTSIGMON_ENABLED']) {
     
  $allMonitoredDevices=  zb_MsmGetMonitoredDevices();
  $allusermacs=zb_UserGetAllMACs();
  $alladdress= zb_AddressGetFullCityaddresslist();
  $alltariffs=  zb_TariffsGetAllUsers();
  $allrealnames= zb_UserGetAllRealnames();
  $alluserips=  zb_UserGetAllIPs();
  
   $result='';
   $hlightmac='';
   
   
   //hlight user mac sub
   if (isset($_GET['username'])) {
       $login=  mysql_real_escape_string($_GET['username']);
       $userip=zb_UserGetIP($login);
       $usermac=  zb_MultinetGetMAC($userip);
       $hlightmac=$usermac;
   }
   

   
   
   if (!empty($allMonitoredDevices)) {
       foreach ($allMonitoredDevices as $io=>$eachdevice) {
           $userCounter=0;
           $hostdata=zb_MsmDeviceQuery($eachdevice['ip'], $eachdevice['community']);
           $result.=wf_tag('h2',false).$eachdevice['location'].' - '.$eachdevice['ip'].  wf_tag('h2',true);   
           $tablecells=  wf_TableCell(__('Full address'));
           $tablecells.=  wf_TableCell(__('Real Name'));
           $tablecells.=  wf_TableCell(__('Tariff'));
           $tablecells.=  wf_TableCell(__('IP'));
           $tablecells.=  wf_TableCell(__('MAC'));
           $tablecells.=  wf_TableCell(__('Signal').' dBm');
           $tablerows=  wf_TableRow($tablecells, 'row1');
           
           if (!empty($hostdata)) {
               foreach ($hostdata as $eachmac=>$eachsig) {
                //signal coloring   
                if ($eachsig<-79) {
                    $displaysig=  wf_tag('font', false, '', 'color="#900000"').$eachsig.wf_tag('font', true);
                } else {
                    $displaysig=  wf_tag('font', false, '', 'color="#006600"').$eachsig.wf_tag('font', true);
                }
                
                //user counter increment
                $userCounter++;
                                
                //hightlighting user
                if (!empty($hlightmac)) {
                    if ($hlightmac==$eachmac) {
                        $rowclass='siglight';
                    } else {
                        $rowclass='row3';
                    }
                } else {
                    $rowclass='row3';
                }
                
                //extracting user profile link
                if (array_search($eachmac, $allusermacs)) {
                   $backmaclogin=  array_search($eachmac, $allusermacs);
                   @$backaddress=$alladdress[$backmaclogin];
                   $profilelink=  wf_Link("?module=userprofile&username=".$backmaclogin, web_profile_icon().' '.$backaddress, false, '');
                   $realname=@$allrealnames[$backmaclogin];
                   $usertariff=@$alltariffs[$backmaclogin];
                   $userip=@$alluserips[$backmaclogin];
                } else {
                    $profilelink='';
                    $realname='';
                    $usertariff='';
                    $userip='';
                }
                
                $tablecells= wf_TableCell($profilelink);
                $tablecells.=  wf_TableCell($realname);
                $tablecells.=  wf_TableCell($usertariff);
                $tablecells.=  wf_TableCell($userip);
                $tablecells.=  wf_TableCell($eachmac);
                $tablecells.=  wf_TableCell($displaysig);
                $tablerows.=  wf_TableRow($tablecells, $rowclass);
               }
               
            $result.=wf_TableBody($tablerows, '100%', '0', 'sortable');
           } else {
               $result.=__('Empty reply received');
           }
           $result.=wf_tag('div', false, 'glamour').__('Total').': '.$userCounter.  wf_tag('div',true).  wf_delimiter();
       }
       
   
   } else {
     $result=__('No devices for signal monitoring found');
   }
   
   //if called as an user profile plugin
   if (isset($_GET['username'])) {
       $result.=wf_Link('?module=userprofile&username='.$_GET['username'], __('Back'), true, 'ubButton');
   }
   
   //show final result
   show_window(__('Mikrotik signal monitor'),$result);    
  } else {
      show_window(__('Error'),__('This module disabled'));
  } 

  
 } else {
      show_error(__('You cant control this module'));
}

?>