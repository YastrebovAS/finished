<?php
include('download_final.php');
class Correction_distributor{
    private $con, $name;
    function __construct($connection,$name) {
        $this->con = $connection;
        $this->name = $name;
        echo("<h2>Здравствуйте</h2>");
        echo("<h3>Вы зашли как редактор ".$this->name."</h3>");
       
    }
    
    function check_if_it_belongs($connection, $id_ver, $id_red){
    
    $connection->exec('LOCK TABLES version_redactor WRITE');
    $checker = $connection->prepare('SELECT id_ver as id from version_redactor where id_red=:t and id_ver=:f');
    $checker->bindParam('t', $id_red,PDO::PARAM_INT);
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
    
    function get_redactor_by_name(){
        $this->con->exec('LOCK TABLES redactor WRITE');
        $getter=$this->con->prepare('SELECT id from redactor where username =:f');
        $getter->bindParam('f',$this->name,PDO::PARAM_STR);
        $result = $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        $this->con->exec('UNLOCK TABLES');
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
    
    function get_redactor_id($connection){
    $connection->exec('LOCK TABLES redactor WRITE');
    $getter = $connection->prepare('SELECT id, username FROM redactor where username=:f');
    $getter->bindParam('f',$_SESSION["session_username"],PDO::PARAM_STR);
    $res = $getter->execute();
    $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
    $connection->exec('UNLOCK TABLES');
    if(count($res_array)==0){
        return -1;
    }
    return $res_array[0]['id'];
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
    if($this->check_if_it_belongs($connection, $res_array[0]['id'], $this->get_redactor_id($connection))==0){
        return -1;
    }
    return $res_array[0]['id'];
    }
    
    function get_articles(){
        $this->con->exec('LOCK TABLES version WRITE, version_redactor WRITE');
        $getter = $this->con->prepare('SELECT name, approved, version_number FROM version INNER JOIN version_redactor ON version_redactor.id_ver=version.id where version_redactor.id_red =:f and approved is NULL and stat = 3 GROUP BY name');
        $getter->bindValue('f',$this->get_redactor_by_name(),PDO::PARAM_INT);
        $result = $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        
        if(is_array($res_array)){
            for($i=0;$i<count($res_array);$i++){
                echo('<h3>'.$res_array[$i]['name'].'</h3>');
                echo('<p><a href="download_final.php?path=versions/'.$res_array[$i]['name'].$res_array[$i]['version_number'].'">Описание</a></p>');
                echo('</br>');
                
            };
        }
        $this->con->exec('UNLOCK TABLES');
    }
    
    function last_correction(){
        $getter = $this->con->prepare('SELECT max(problem_list_red.id) as id FROM problem_list_red INNER JOIN version_redactor ON version_redactor.id_ver=problem_list_red.id_ver INNER JOIN version ON version.id=version_redactor.id_ver WHERE version_redactor.id_red=:t GROUP BY version.name');
        $getter->bindValue('t', $this->get_redactor_by_name(),PDO::PARAM_INT);
        $result = $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        return $res_array;
    }
    
    function logout_from_session(){
        if(isset($_POST['submit_exit']) or !isset($_COOKIE['redactor'])){
            unset($_SESSION['session_username']);
            unset($_FILES["fileupload"]);
            unset($_COOKIE['redactor']);
            setcookie('redactor', $this->name, time() - 1);
            header('Location: ../startpage_final.php');

        }
    }

    function get_author_by_version_name($connection){
        $connection->exec('LOCK TABLES send_recieve WRITE,version WRITE');
        $getter = $connection->prepare('SELECT id_a FROM send_recieve
        INNER JOIN version on  send_recieve.id_ver = version.id
        WHERE version.name = :t');
        $getter->bindParam('t',$_POST['name'],PDO::PARAM_STR);
        $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        $connection->exec('UNLOCK TABLES');
        return $res_array;
    }

    function remove($connection){
    if(isset($_POST["submit_bad"])){
        if(!preg_match("/^[a-zA-Z0-9\s]/", $_POST['name'])){
            //echo("Invalid username");
            die("Invalid title");
        }
        $name = $_POST['name'];
        $today = date('y-m-d');
        $denial = $_POST['denial'];
        $author = $this->get_author_by_version_name($connection)[0]['id_a'];
        $warning = $connection->prepare("INSERT INTO deletion VALUES(:t,:j,:k,:a)");
        $warning->bindParam('t',$name,PDO::PARAM_STR);
        $warning->bindParam('j',$today,PDO::PARAM_STR);
        if ($denial == "significance"){$warning->bindValue('k',"Недостаточная значимость",PDO::PARAM_STR); }
        if ($denial == "plagiarism" and isset($_POST['vak'])){$warning->bindValue('k',"Плагиат. Информация передана в ВАК",PDO::PARAM_STR);}
        elseif ($denial == "plagiarism" and !isset($_POST['vak'])){$warning->bindValue('k',"Плагиат",PDO::PARAM_STR);}
        $warning->bindParam('a',$author,PDO::PARAM_INT);
        $warning -> execute();
        $updater = $connection->prepare('UPDATE version SET stat = -2 WHERE name =:t');
        $updater->bindValue('t',$name,PDO::PARAM_STR);
        $updater->execute();
        $result = $this->get_all_id_where_stat_on_delete($connection);
        for($i=0; $i<count($result);$i++){
            $connection->exec('LOCK TABLES version WRITE, problem_list WRITE, problem_list_red WRITE,problem_list_ev WRITE, send_recieve WRITE, version_redactor WRITE, version_evaluator WRITE');
            $deleter = $connection->prepare('DELETE FROM version WHERE id=:t');
            $deleter->bindValue('t',$result[$i]['id'],PDO::PARAM_INT);
            $deleter->execute();
            $deleter = $connection->prepare('DELETE FROM  problem_list WHERE id_ver=:t');
            $deleter->bindValue('t',$result[$i]['id'],PDO::PARAM_INT);
            $deleter->execute();
            $deleter = $connection->prepare('DELETE FROM  problem_list_ev WHERE id_ver=:t');
            $deleter->bindValue('t',$result[$i]['id'],PDO::PARAM_INT);
            $deleter->execute();
            $deleter = $connection->prepare('DELETE FROM  problem_list_red WHERE id_ver=:t');
            $deleter->bindValue('t',$result[$i]['id'],PDO::PARAM_INT);
            $deleter->execute();
            $deleter = $connection->prepare('DELETE FROM  send_recieve WHERE id_ver=:t');
            $deleter->bindValue('t',$result[$i]['id'],PDO::PARAM_INT);
            $deleter->execute();
            $deleter = $connection->prepare('DELETE FROM  version_redactor WHERE id_ver=:t');
            $deleter->bindValue('t',$result[$i]['id'],PDO::PARAM_INT);
            $deleter->execute();
            $deleter = $connection->prepare('DELETE FROM  version_evaluator WHERE id_ver=:t');
            $deleter->bindValue('t',$result[$i]['id'],PDO::PARAM_INT);
            $deleter->execute();
            
            $connection->exec('UNLOCK TABLES');
        }
        echo("<h3>Заявка успешно удалена, автор уведомлен</h3>");
    }
    }
    
    function send_problems_aut($connection){
    if (isset($_POST["submit1"]) and isset($_SESSION["session_username"])){
        if(!preg_match("/^[a-zA-Z0-9\s]/", $_POST['title1'])){
            //echo("Invalid username");
            die("Invalid title");
        }
        if(!preg_match("/^[a-zA-Z0-9\s]/", $_POST['problem'])){
            //echo("Invalid username");
            die("Invalid title");
        }
        $id = $this->get_last_version_by_name($connection,$_POST["title1"]);
        if($id == -1){
            echo('<h3>Такого изобретения нет</h3>');
            return 0;
        }
        $connection->exec('LOCK TABLE problem_list WRITE');
        $inserter = $connection->prepare('INSERT INTO problem_list(txt, id_ver,sender) VALUES (:f,:t, "red")');
        $inserter->bindParam('f',$_POST["problem"],PDO::PARAM_STR);
        $inserter->bindParam('t',$id,PDO::PARAM_INT);
        $result = $inserter->execute();
        $connection->exec('UNLOCK TABLE');
        if($result){
            echo('<h3>Проблема успешно зарегистрирована и отправлена автору</h3>');
        }
    }
    }

    function show_problems(){
        $our_ids = $this->last_correction();
        for($i=0;$i<count($our_ids);$i++){
            $printer = $this->con->prepare('SELECT problem_list_red.txt as txt, version.name as name from problem_list_red INNER JOIN version on problem_list_red.id_ver=version.id where problem_list_red.id=:t');
            $printer->bindValue('t',$our_ids[$i]['id'],PDO::PARAM_INT);
            $printer->execute();
            $res_array = $printer->fetchALL(PDO::FETCH_ASSOC);
            echo('<h4>'.$res_array[$i]['name'].': '.$res_array[$i]['txt'].'</h4>');
        }
        
    }
    
    function send_approval_aut($connection){
    if (isset($_POST["submit2"]) and isset($_SESSION["session_username"])){
        if(!preg_match("/^[a-zA-Z0-9\s]/", $_POST['title2'])){
            //echo("Invalid username");
            die("Invalid title");
        }
        $id = $this->get_last_version_by_name($connection,$_POST["title2"]);
        if($id == -1){
        echo($_POST['title2']);
            echo('<h3>Такого изобретения нет</h3>');
            return 0;
        }
        $tname1 = $_FILES["patent"]["tmp_name"];
        $title = $_POST["title2"];
        move_uploaded_file($tname1,'D:\Myssp\approvals'.'/'.$title);
        $connection->exec('LOCK TABLE version WRITE');
        $inserter = $connection->prepare('UPDATE version SET approved = TRUE WHERE id =:t');
        $inserter->bindParam('t',$id,PDO::PARAM_INT);
        $result = $inserter->execute();
        $connection->exec('UNLOCK TABLE');
        if($result){
            echo('<h3>Авторское свидетельство успешно зарегистрировано, автор уведомлен.</h3>');
        }
    }
    }

    
    
}
?>