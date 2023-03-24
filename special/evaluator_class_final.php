<?php
include('download_final.php');
function get_evaluator_id($connection){
    $connection->exec('LOCK TABLES evaluator WRITE');
    $getter = $connection->prepare('SELECT id, username FROM evaluator where username=:f');
    $getter->bindParam('f',$_SESSION["session_username"],PDO::PARAM_STR);
    $res = $getter->execute();
    $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
    $connection->exec('UNLOCK TABLES');
    if(count($res_array)==0){
        return -1;
    }
    return $res_array[0]['id'];
}

function check_if_it_belongs($connection, $id_ver, $id_ev){
    
    $connection->exec('LOCK TABLES version_evaluator WRITE');
    $checker = $connection->prepare('SELECT id_ver as id from version_evaluator where id_ev=:t and id_ver=:f');
    $checker->bindParam('t', $id_ev,PDO::PARAM_INT);
    $checker->bindParam('f', $id_ver,PDO::PARAM_INT);
    $checker->execute();
    $res_array = $checker->fetchALL(PDO::FETCH_ASSOC);
    $connection->exec('UNLOCK TABLES');

    if(count($res_array)>0){
        return 1;
    }
    else{
        return 0;
    }
}

function get_last_version_by_name($connection, $name){
    $connection->exec('LOCK TABLES version WRITE');
    $getter=$connection->prepare('SELECT max(id) as id from version where name=:f group by name');
    $getter->bindParam('f',$name,PDO::PARAM_STR);
    $result = $getter->execute();
    $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
    $connection->exec('UNLOCK TABLES');
    if(count($res_array)==0){
        return -1;
    }
    if(check_if_it_belongs($connection, $res_array[0]['id'], get_evaluator_id($connection))==0){
        return -1;
    }
    return $res_array[0]['id'];
}
function get_all_id_where_stat_on_delete($connection){
    $connection->exec('LOCK TABLES version WRITE');
    $getter = $connection->prepare('SELECT id FROM version WHERE stat<0');
    $getter->execute();
    $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
    $connection->exec('UNLOCK TABLES');
    return $res_array;
}

function get_redactor_by_name($connection,$name){
    $connection->exec('LOCK TABLES redactor WRITE');
    $getter=$connection->prepare('SELECT id from redactor where username=:f');
    $getter->bindParam('f',$name,PDO::PARAM_STR);
    $result = $getter->execute();
    $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
    $connection->exec('UNLOCK TABLES');
    if(count($res_array)==0){
        return -1;
    }
    return $res_array[0]['id'];
}

function article_evaluator_show($connection){
    //echo(get_evaluator_id($connection));
    $connection->exec('LOCK TABLES version WRITE, version_evaluator WRITE');
    $getter = $connection->prepare('SELECT version.name, approved, max(version.version_number) as last_version FROM version INNER JOIN version_evaluator on version.id=version_evaluator.id_ver WHERE version_evaluator.id_ev =:t AND approved IS NULL AND version.stat = 2 GROUP BY version.name; ');
    $getter->bindValue('t',get_evaluator_id($connection),PDO::PARAM_INT);
    $result = $getter->execute();
    $result_array = $getter->fetchALL(PDO::FETCH_ASSOC);
    
    $connection->exec('UNLOCK TABLES');
    for($i=0;$i<count($result_array);$i++){
        echo('<h3>'.$result_array[$i]['name'].'</h3>');
        echo('<p><a href="download_final.php?path=request/'.$result_array[$i]['name'].$result_array[$i]['last_version'].$result_array[$i]['last_version'].'">Заявка</a></p>');
        echo('<p><a href="download_final.php?path=versions/'.$result_array[$i]['name'].$result_array[$i]['last_version'].$result_array[$i]['last_version'].'">Описание</a></p>');
    }

};
function last_correction($connection){
    $getter = $connection->prepare('SELECT max(problem_list_ev.id) as id FROM problem_list_ev INNER JOIN version_evaluator ON version_evaluator.id_ver=problem_list_ev.id_ver INNER JOIN version ON version.id=version_evaluator.id_ver WHERE version_evaluator.id_ev=:t GROUP BY version.name');
    $getter->bindValue('t', get_evaluator_id($connection),PDO::PARAM_INT);
    $result = $getter->execute();
    $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
    return $res_array;
}
function show_problems($connection)
{
    $our_ids = last_correction($connection);
    for ($i = 0; $i < count($our_ids); $i++) {
        $printer = $connection->prepare('SELECT problem_list_ev.txt as txt, version.name as name from problem_list_ev INNER JOIN version on problem_list_ev.id_ver=version.id where problem_list_ev.id=:t');
        $printer->bindValue('t', $our_ids[$i]['id'], PDO::PARAM_INT);
        $printer->execute();
        $res_array = $printer->fetchALL(PDO::FETCH_ASSOC);
        echo('<h4>' . $res_array[$i]['name'] . ': ' . $res_array[$i]['txt'] . '</h4>');
    }
}
function send_problems_aut($connection){
    if (isset($_POST["submit"]) and isset($_SESSION["session_username"])){
        if(!preg_match("/[a-zA-Z0-9\s]+/", $_POST['title'])){
            //echo("Invalid username");
            die("Invalid title");
        }
        if(!preg_match("/[a-zA-Z0-9:\s]+/", $_POST['problem'])){
            //echo("Invalid username");
            die("Invalid title");
        }

        $id = get_last_version_by_name($connection,$_POST["title"]);
        if($id == -1){
            echo('<h3>Такого изобретения нет</h3>');
            return 0;
        }
        $connection->exec('LOCK TABLE problem_list WRITE');
        $inserter = $connection->prepare('INSERT INTO problem_list(txt, id_ver,sender) VALUES (:f,:t,"ev")');
        $inserter->bindParam('f',$_POST["problem"],PDO::PARAM_STR);
        $inserter->bindParam('t',$id,PDO::PARAM_INT);
        $result = $inserter->execute();
        $connection->exec('UNLOCK TABLE');
        if($result){
            echo('<h3>Проблема успешно зарегистрирована и отправлена автору</h3>');
        }
    }

};

function send_article_cor($connection){
    if (isset($_POST["submit3"]) and isset($_SESSION["session_username"])){
        if(!preg_match("/^[a-zA-Z0-9\s]/", $_POST['name'])){
            //echo("Invalid username");
            die("Invalid title");
        }
        if(!preg_match("/^[a-zA-Z0-9\s]/", $_POST['redactor'])){
            //echo("Invalid username");
            die("Invalid redactor");
        }
        $id_ver = get_last_version_by_name($connection,$_POST["name"]);
        $id_red = get_redactor_by_name($connection,$_POST["redactor"]);
        //echo("<h3>".$id_ver." ".$id_red."</h3>");
        if($id_ver!=-1 and $id_red!=-1){
            
            $connection->exec('LOCK TABLES version_redactor WRITE, version WRITE');
            $inserter = $connection->prepare('INSERT INTO version_redactor(id_ver, id_red) VALUES (:f,:t)');
            $inserter->bindParam('f',$id_ver,PDO::PARAM_INT);
            $inserter->bindParam('t',$id_red,PDO::PARAM_INT);
            $result = $inserter->execute();
            if($result){
                echo('<h3>Заявка успешно отправлена</h3>');
            }
            $updater = $connection->prepare('UPDATE version SET stat=3 WHERE id=:f');
            $updater->bindParam('f',$id_ver,PDO::PARAM_INT);
            $result = $updater->execute();
            
            $connection->exec('UNLOCK TABLES');
        }
        else{
            echo("<h3>Изобретения с таким названием или такого редактора нет</h3>");
        }
    }


};
function counter($connection, $id){
    $connection->exec('LOCK TABLES fills WRITE, plan_num WRITE');
    $counter = $connection->prepare('SELECT count(id_ver) as count from fills where id_plan =:t');
    $counter->bindValue('t',$id,PDO::PARAM_INT);
    $counter->execute();
    $res_array = $counter->fetchALL(PDO::FETCH_ASSOC);
    $getter = $connection->prepare('SELECT number_of_articles as n from plan_num where id = :t');
    $getter->bindValue('t',$id,PDO::PARAM_INT);
    $getter->execute();
    $res_array1 = $getter->fetchALL(PDO::FETCH_ASSOC);
    if($res_array1[0]['n']==$res_array[0]['count']){
        $updater = $connection->prepare('UPDATE plan_num SET fulled = 1 where id =:t');
        $updater->bindValue('t',$id,PDO::PARAM_INT);
        $updater->execute();
    };
    $connection->exec('UNLOCK TABLES');
}
function filler($conncetion){
    $conncetion->exec('LOCK TABLES plan_num WRITE, fills WRITE, version WRITE');
    $getter = $conncetion->prepare('SELECT max(id) as id FROM plan_num');
    $getter->execute();
    $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
    $getter2 = $conncetion->prepare('SELECT count(id_ver) as c FROM fills WHERE id_plan=:t');
    $getter2->bindValue('t', $res_array[0]['id'],PDO::PARAM_INT);
    $getter2->execute();
    $res_array2 = $getter2->fetchALL(PDO::FETCH_ASSOC);

    $getter3 = $conncetion->prepare('SELECT number_of_articles as num FROM plan_num WHERE id =:t');
    $getter3->bindValue('t', $res_array[0]['id'],PDO::PARAM_INT);
    $getter3->execute();
    $res_array3 = $getter3->fetchALL(PDO::FETCH_ASSOC);
    if(count($res_array2)==0){
        $how_may_can_insert = $res_array3[0]['num'] - $res_array2[0]['c'];
        $i = 0;
        while($i <=$how_may_can_insert){
            
            $selector = $conncetion->prepare('SELECT id from version where stat = 10 LIMIT 1');
            $selector->execute();
            $res_array4 = $selector->fetchALL(PDO::FETCH_ASSOC);
            if(count($res_array4)>0){
                //print_r($res_array4[0]['id']);
                $updater = $conncetion->prepare('UPDATE version SET stat = 11 WHERE id =:t');
                $updater->bindValue('t',$res_array4[0]['id'],PDO::PARAM_INT);
                $updater->execute();
                $inserter = $conncetion->prepare('INSERT INTO fills(id_ver, id_plan) VALUES (:t,:f)');
                $inserter->bindValue('t',$res_array4[0]['id'],PDO::PARAM_INT);
                $inserter->bindValue('f',$res_array[0]['id'],PDO::PARAM_INT);
                $inserter->execute();
            }
            $i++;
        }
    }
    if($res_array2[0]['c']<$res_array3[0]['num']){
        //print_r('imhere');
        $how_may_can_insert = $res_array3[0]['num'] - $res_array2[0]['c'];
        $i = 0;
        while($i <=$how_may_can_insert){
            
            $selector = $conncetion->prepare('SELECT id from version where stat = 10 LIMIT 1');
            $selector->execute();
            $res_array4 = $selector->fetchALL(PDO::FETCH_ASSOC);
            if(count($res_array4)>0){
                //print_r($res_array4[0]['id']);
                $updater = $conncetion->prepare('UPDATE version SET stat = 11 WHERE id =:t');
                $updater->bindValue('t',$res_array4[0]['id'],PDO::PARAM_INT);
                $updater->execute();
                $inserter = $conncetion->prepare('INSERT INTO fills(id_ver, id_plan) VALUES (:t,:f)');
                $inserter->bindValue('t',$res_array4[0]['id'],PDO::PARAM_INT);
                $inserter->bindValue('f',$res_array[0]['id'],PDO::PARAM_INT);
                $inserter->execute();
            }
            $i++;
        }
    }
    $conncetion->exec('UNLOCK TABLES');
    counter($conncetion, $res_array[0]['id']);
    remove($conncetion);
    
}


function if_you_can_delete($connection){
    $getter = $connection->prepare('SELECT MAX(stat) as st FROM version WHERE name=:t GROUP BY name');
    $getter->bindValue('t',$_POST["name"],PDO::PARAM_STR);
    $getter->execute();
    $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
    //echo($res_array[0]['st']);
    if(count($res_array)>0){
        if($res_array[0]['st']<10){
            return 1;
        }
    }
    return 0;
    

}

function logout($connection){
    if(isset($_POST['submit_exit']) or !isset($_COOKIE['evaluator'])){
        $name = $_SESSION['session_username'];
        setcookie('evaluator', $name, time() - 1);
        unset($_SESSION['session_username']);
        unset($_FILES["fileupload"]);
        unset($_COOKIE['evaluator']);
        //setcookie('evaluator', $this->name, time() - 1);
        header('Location: ../startpage_final.php');

    }
}
?>