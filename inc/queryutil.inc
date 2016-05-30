<?php

$select = "SELECT `patient`.`patientid` ";
$from = " FROM `procedure` AS `lymphnodeexamination`, `procedure` AS `colectomy`, `diagnosis`, `patient` ";
$fromnumerator = " FROM `procedure` AS `lymphnodeexamination`, `procedure` AS `colectomy`, `diagnosis`, `lymphnodes`, `patient` ";

$where = " WHERE
				`lymphnodeexamination`.`type` = '284427004' 
				AND `colectomy`.`type` = '23968004'
				AND `diagnosis`.type = '93761005'
				
				AND `patient`.`patientid` = `colectomy`.`patientid`
				AND `patient`.`patientid` = `diagnosis`.`patientid`
				AND `patient`.`patientid` = `lymphnodeexamination`.`patientid`
				
				AND `lymphnodeexamination`.`date` >= '2010-01-01' 
				AND `lymphnodeexamination`.`date` <= '2010-12-31' 
				AND `lymphnodeexamination`.`date` > `colectomy`.`date` "; 

$numeratoronly = "AND `lymphnodes`.`numberexamined` >= 10
AND `patient`.`patientid` = `lymphnodes`.`patientid` 
AND `lymphnodes`.`procedureid` = `lymphnodeexamination`.`procedureid`";


$exclusion = " AND `patient`.`patientid` NOT IN (SELECT `patient`.`patientid` FROM `procedure` AS `radiotherapy`
WHERE `radiotherapy`.`type` = '108290001' 
AND `patient`.`patientid` = `radiotherapy`.`patientid` 
AND `radiotherapy`.`date` < `lymphnodeexamination`.`date`)"; 

$denominatorsql= $select . $from . $where . $exclusion;
$numeratorsql= $select . $fromnumerator . $where . $numeratoronly . $exclusion;
?>