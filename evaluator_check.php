<?php
include('special/config_final.php');
$getter = $connection->prepare('SELECT  evaluator.username, COUNT(version_evaluator.id_ver) AS ct
FROM evaluator
LEFT JOIN version_evaluator
ON evaluator.id = version_evaluator.id_ev
GROUP BY evaluator.id');
$getter->execute();
$res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
for($i = 0; $i< count($res_array); $i++){
    echo('<h3>'.$res_array[$i]['username'].': '.$res_array[$i]['ct'].' заявок </h3>');
}
?>