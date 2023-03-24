<?php
include('download_final.php');
class Author{
    private $con, $name;
    function __construct($connection,$name) {
        $this->con = $connection;
        $this->name = $name;
        
        
        
    }
    function get_author_by_name(){
        $getter_new1 = $this->con->prepare("SELECT id FROM author WHERE username=:name1");
        $getter_new1->bindParam("name1", $this->name, PDO::PARAM_STR);
        $result11 = $getter_new1->execute();
        $result11_ar = $getter_new1->fetchALL(PDO::FETCH_ASSOC);
        $author_id = $result11_ar[0]["id"];
        return $author_id;
    }
    function if_it_belongs($title){
        $author_id = $this->get_author_by_name();
        $checker = $this->con->prepare('SELECT version.id from version INNER JOIN send_recieve ON send_recieve.id_ver=version.id WHERE send_recieve.id_a =:t AND version.name =:f');
        $checker->bindValue('t',$author_id,PDO::PARAM_INT);
        $checker->bindValue('f',$title,PDO::PARAM_STR);
        $checker->execute();
        $res_array = $checker->fetchALL(PDO::FETCH_ASSOC);
        if(count($res_array)>0){
            return 1;
        }
        else{
            return 0;
        }


    }

    function if_name_is_uniq($title){
        $checker = $this->con->prepare('SELECT id from version where name =:t');
        $checker->bindValue('t',$title,PDO::PARAM_STR);
        $checker->execute();
        $res_array = $checker->fetchALL(PDO::FETCH_ASSOC);
        if(count($res_array)>0){
            return 1;
        }
        else{
            return 0;
        }
    }

    function get_max_id_by_name($name){
        $getter = $this->con->prepare('SELECT MAX(id) as id FROM version INNER JOIN send_recieve ON send_recieve.id_ver = version.id WHERE name=:t AND send_recieve.id_a =:f');
        $getter->bindValue('t',$name,PDO::PARAM_STR);
        $getter->bindValue('f',$this->get_author_by_name(),PDO::PARAM_STR);
        $result = $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        if(count($res_array)==1){
            return $res_array[0]['id'];
        }
        else{
            return -1;
        }
    }
    function stater($name){
        
        $finder = $this->con->prepare('SELECT max(stat) as st FROM version WHERE name=:t LIMIT 1');
        $finder->bindValue('t',$name,PDO::PARAM_STR);
        $finder->execute();
        $res_array = $finder->fetchALL(PDO::FETCH_ASSOC);
        return $res_array[0]['st'];
    }
    function upload_version($title){
        $getter = $this->con->prepare("SELECT id, version_number, stat FROM version WHERE name=:name1 ORDER BY version_number DESC LIMIT 1");
        $getter->bindParam("name1", $title, PDO::PARAM_STR);
        $result = $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        if(count($res_array) == 0){
            $version = 0;
            $tname1 = $_FILES["fileupload1"]["tmp_name"];
            $tname2 = $_FILES["fileupload2"]["tmp_name"];
            $pnam = $title.$version;
            move_uploaded_file($tname1, 'D:\Myssp\versions' . '/' .$pnam);
            move_uploaded_file($tname2, 'D:\Myssp\request' . '/' .$pnam);
            $inserter = $this->con->prepare("INSERT INTO version(name,stat,version_number) VALUES (:name1, :stat,:version1)");
            $inserter->bindParam(':name1', $title, PDO::PARAM_STR);
            $inserter->bindValue(':stat', 1, PDO::PARAM_INT);
            $inserter->bindValue(':version1', $version, PDO::PARAM_INT);
            $result = $inserter->execute();
            $getter_new = $this->con->prepare("SELECT id FROM version WHERE name=:name1");
            $getter_new->bindParam("name1", $title, PDO::PARAM_STR);
            $result1 = $getter_new->execute();
            $result1_ar = $getter_new->fetchALL(PDO::FETCH_ASSOC);
            $version_id = $result1_ar[0]["id"];
            $getter_new1 = $this->con->prepare("SELECT id FROM author WHERE username=:name1");
            $getter_new1->bindParam("name1", $this->name, PDO::PARAM_STR);
            $result11 = $getter_new1->execute();
            $result11_ar = $getter_new1->fetchALL(PDO::FETCH_ASSOC);
            $author_id = $result11_ar[0]["id"];
            $inserter1 = $this->con->prepare("INSERT INTO send_recieve(id_a, id_ver) VALUES (:author_id, :ver_id)");
            $inserter1->bindParam(':author_id', $author_id, PDO::PARAM_INT);
            $inserter1->bindParam(':ver_id', $version_id, PDO::PARAM_INT);
            $result = $inserter1->execute();
            if ($result) {
                echo('<h3> Заявка успешно загружена и отправлена на рассмотрение</h3>');
            } else {
                echo('<h3>Произошла ошибка при загрузке.</h3>');
            }
        }
        else{
            $stat = $res_array[0]["stat"];
            $version = $res_array[0]["version_number"];
            if($stat==1){
                echo("<h3>Вы не можете отправить загрузить новую версию заявки, пока предыдущая версия не была проверена.</h3>");
                echo("<h3>Дождитесь предложений по корректировке от сотрудников или одобрения или отказа выдачи авторского свидетельства</h3>");
            }
            else{
            $version = $version+1;
            $tname1 = $_FILES["fileupload1"]["tmp_name"];
            $tname2 = $_FILES["fileupload2"]["tmp_name"];
            $pnam = $title.$version;
            move_uploaded_file($tname1, 'D:\Myssp\versions' . '/' .$pnam);
            move_uploaded_file($tname2, 'D:\Myssp\request' . '/' .$pnam);
            $updater = $this->con->prepare("UPDATE version SET version_number = :ver_number WHERE name = :ver_name");
            $updater->bindParam(':ver_number', $version, PDO::PARAM_STR);
            $updater->bindValue(':ver_name', $title, PDO::PARAM_INT);
            $result = $updater->execute();
                if ($result) {
                    echo('<h3> Новая версия успешно загружена и отправлена на рассмотрение</h3>');
                } else {
                    echo('<h3>Произошла ошибка при загрузке.</h3>');
                }
            }
        }

    }
    function upload_to_server(){
        if (isset($_POST["submit"]) and isset($this->name)){
            //sleep(10);
            $approval = $this->con->prepare('SELECT approved FROM version WHERE name=:t');
            $approval->bindValue('t',$_POST["title1"],PDO::PARAM_STR);
            $approval->execute();
            $res_array = $approval->fetchALL(PDO::FETCH_ASSOC);
            if(count($res_array)>0 and $res_array[0]['approved'] != NULL){
                echo('<h3>Невозможно получить патент на уже одобренное изобретение<h3>');
                return 0;
            }


            $this->con->exec('Start transaction');
            $this->con->exec('LOCK TABLES version WRITE, author WRITE, send_recieve WRITE, version_evaluator WRITE');

            $title = $_POST["title1"];
            if($this->name == 'Alex'){sleep(10);}
            if($title == 'testoflocker1'){

                $this->con->exec('UNLOCK TABLES');
            }
            $getter = $this->con->prepare('SELECT * FROM version WHERE name=:name1 ORDER BY version_number DESC');
            $getter->bindValue('name1',$title,PDO::PARAM_STR);
            $result = $getter->execute();

            $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
            //print_r($res_array);

            if(count($res_array)>0){
                if($this->if_it_belongs($_POST["title1"])==0){
                    echo('<h3>Другой автор уже запросил авторское свидетельство на изобретение с таким названием</h3>');
                    return 0;
                }
                $this->upload_version($title);
            }
            else {
                $this->upload_version($title);
            }
            $this->con->exec('UNLOCK TABLES');
            $this->con->exec('commit');
        

        }
    }
    function show_last_version(){
        if (isset($this->name)){
            $getter = $this->con->prepare('SELECT name, approved, max(version_number) as version_number FROM version
            INNER JOIN send_recieve ON send_recieve.id_ver = version.id WHERE approved IS NULL AND send_recieve.id_a=:t GROUP BY name');
            $getter->bindValue('t', $this->get_author_by_name(), PDO::PARAM_INT);
            $result = $getter->execute();
            $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
            if(is_array($res_array)){
                for($i =0;$i<count($res_array);$i++){
                    $st = $this->stater($res_array[$i]['name']);
                    echo('<h3> ======================= </h3>');
                    echo('<h3>'.$res_array[$i]['name']." Версия номер ".$res_array[$i]['version_number'].'</h3>');
                    echo('<p><a href="download_final.php?path=versions/'.$res_array[$i]['name'].$res_array[$i]['version_number'].'">Описание</a></p>');
                    echo('<p><a href="download_final.php?path=request/'.$res_array[$i]['name'].$res_array[$i]['version_number'].'">Заявка</a></p>');
                    switch ($st){
                        case 1:
                            echo('<h3>Заявка на стадии распределения</h3>');
                            break;
                        case 2:
                            echo('<h3>Заявку проверяет оценщик</h3>');
                            break;
                        case 3:
                            echo('<h3>Заявку проверяет редактор</h3>');
                            break;

                    }

                }
            }
        }

    }

    function show_all_problems(){
        $this->con->exec('LOCK TABLES problem_list WRITE, version WRITE, author WRITE, send_recieve WRITE');
        $getter = $this->con->prepare('SELECT problem_list.txt, problem_list.sender, version.name FROM problem_list INNER JOIN version ON version.id =  problem_list.id_ver WHERE problem_list.id_ver IN (SELECT id_ver as ver_id FROM send_recieve WHERE id_a =:f)');
        $getter->bindValue('f',$this->get_author_by_name(),PDO::PARAM_INT);
        $result = $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        //echo('<table>');
        //echo('<tr><th>Название</th><th>Проблема</th></tr>');
        for($i =0;$i<count($res_array);$i++){
            if($res_array[$i]["sender"] == "ev") {
                echo("<h3> Оценщик " . $res_array[$i]["name"] . ": " . $res_array[$i]["txt"] . "</h3>");
            }
            else{
                echo("<h3> Редактор " . $res_array[$i]["name"] . ": " . $res_array[$i]["txt"] . "</h3>");
            }
        }
        $this->con->exec('UNLOCK TABLES');
    }
    function count_sended_version($name){
        $this->con->exec('LOCK TABLES version WRITE, send_recieve WRITE');
        $getter=$this->con->prepare('SELECT count(version.id) as num from version INNER JOIN send_recieve on send_recieve.id_ver=version.id WHERE version.name=:t GROUP BY version.name');
        $getter->bindValue('t', $name, PDO::PARAM_STR);
        $result = $getter->execute();
        $res_array =$getter->fetchALL(PDO::FETCH_ASSOC);
        $this->con->exec('UNLOCK TABLES');
        return $res_array[0]['num'];
    }
    
    function show_approved_versions(){
        $this->con->exec('LOCK TABLES send_recieve WRITE, version WRITE, author WRITE');
        $getter = $this->con->prepare('SELECT name FROM version WHERE approved = TRUE AND id IN (SELECT id_ver FROM send_recieve WHERE id_a = :f)');
        $getter->bindValue('f',$this->get_author_by_name(),PDO::PARAM_INT);
        $result = $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        //echo('<table>');
        //echo('<tr><th>Название</th><th>Проблема</th></tr>');
        for($i =0;$i<count($res_array);$i++){
            echo("<h3>".$res_array[$i]["name"]."</h3>");
            echo('<p><a href="download_final.php?path=approvals/'.$res_array[$i]['name'].'">Авторское свидетельство</a></p>');
        }
        $this->con->exec('UNLOCK TABLES');
    }
    function show_deleted_versions(){
        $this->con->exec('LOCK TABLES deletion WRITE, author WRITE');
        $getter = $this->con->prepare('SELECT invention_name,reason, deletion_date FROM deletion WHERE id_a =:f');
        $getter->bindValue('f',$this->get_author_by_name(),PDO::PARAM_INT);
        $result = $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        //echo('<table>');
        //echo('<tr><th>Название</th><th>Проблема</th></tr>');
        //echo('<tr><th>Название</th><th>Проблема</th></tr>');
        for($i =0;$i<count($res_array);$i++){
            if (date('y-m-d')<=date('y-m-d',(strtotime($res_array[$i]["deletion_date"].' + 7 days')))) {
                echo("<h3>" . $res_array[$i]["invention_name"] . "</h3>");
                echo("<h3>" . 'Причина: ' . $res_array[$i]['reason'] . "</h3>");
            }
            else {
                $deleter = $this->con->prepare('DELETE FROM deletion WHERE invention_name = :f');
                $deleter->bindValue('f',$res_array[$i]["invention_name"],PDO::PARAM_INT);
                $deleter->execute();
            }
        }
        $this->con->exec('UNLOCK TABLES');
    }
    function send_problems(){
        if (isset($_POST["submit1"]) and isset($_SESSION["session_username"])){
            //$this->con->beginTransaction();
            $name = $_POST["name"];
            $id = $this->get_max_id_by_name($name);
            //echo("<h3>".$id."</h3>");
            if($id == -1){
                echo("<h3>Изобретение не найдено или вам не пренадлежит</h3>");
                return 0;
            }
            $reciever = $_POST['reciever'];
            if ($reciever == "ev") {
                $inserter = $this->con->prepare('INSERT INTO problem_list_ev(txt,id_ver) VALUES (:t,:f)');
            }
            if ($reciever == "red"){
                $inserter = $this->con->prepare('INSERT INTO problem_list_red(txt,id_ver) VALUES (:t,:f)');
            }
            $inserter->bindValue(':t',$_POST["problem"],PDO::PARAM_STR);
            $inserter->bindValue(':f',$id,PDO::PARAM_INT);
            $result = $inserter->execute();
            if($result){
                echo("<h3>Обращение зарегистировано</h3>");
            }
            $this->con->exec('UNLOCK TABLES');
            //$this->con->commit();
        }        
    }
    function logout_from_session(){
        if(isset($_POST['submit_exit']) or !isset($_COOKIE['author'])){
            
            unset($_SESSION['session_username']);
            unset($_FILES["fileupload"]);
            unset($_COOKIE['author']);
            setcookie('author', $this->name, time() - 1);
            header('Location: ../startpage_final.php');

        }
    }
    function if_you_can_delete(){
        $getter = $this->con->prepare('SELECT MAX(stat) as st FROM version WHERE name=:t GROUP BY name');
        $getter->bindValue('t',$_POST["name"],PDO::PARAM_STR);
        $getter->execute();
        $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
        if(count($res_array)>0){
            if($res_array[0]['st']<10){
                return 1;
            }
        }
        return 0;
        
    }
    function delete_all_about_article(){
        if(isset($_POST["submit_delete"])){
            //echo($this->if_it_belongs($_POST["name"]));
            if(!preg_match("/^[a-zA-Z0-9\s]/", $_POST['name'])){
                //echo("Invalid username");
                die("Invalid title");
            }
            if($this->if_it_belongs($_POST["name"])){

                //$home = $_SERVER['DOCUMENT_ROOT']."/";
                //$unlink = unlink($home.'versions/'.$_POST["name"].'*');
                //if($unlink == true){ echo "получилось удалить";} else{ echo "не получилось удалить";}

                if($this->if_you_can_delete() == 0){
                    echo('<h3>Данную заявку невозможно удалить<h3>');
                    return 0;
                }
                $getter = $this->con->prepare('SELECT id from version where name =:t and stat<10');
                $getter->bindValue('t', $_POST["name"], PDO::PARAM_STR);
                $getter->execute();
                $res_array = $getter->fetchALL(PDO::FETCH_ASSOC);
                //echo(count($res_array));
                for($i=0;$i<count($res_array);$i++){
                    $deleter = $this->con->prepare('DELETE from problem_list WHERE id_ver=:t');
                    $deleter->bindValue('t',$res_array[$i]['id'],PDO::PARAM_INT);
                    $deleter->execute();
                    $deleter = $this->con->prepare('DELETE from problem_list_red WHERE id_ver=:t');
                    $deleter->bindValue('t',$res_array[$i]['id'],PDO::PARAM_INT);
                    $deleter->execute();
                    $deleter = $this->con->prepare('DELETE from problem_list_ev WHERE id_ver=:t');
                    $deleter->bindValue('t',$res_array[$i]['id'],PDO::PARAM_INT);
                    $deleter->execute();
                    $deleter = $this->con->prepare('DELETE from send_recieve WHERE id_ver=:t');
                    $deleter->bindValue('t',$res_array[$i]['id'],PDO::PARAM_INT);
                    $deleter->execute();
                    $deleter = $this->con->prepare('DELETE from version_redactor WHERE id_ver=:t');
                    $deleter->bindValue('t',$res_array[$i]['id'],PDO::PARAM_INT);
                    $deleter->execute();
                    $deleter = $this->con->prepare('DELETE from version_evaluator WHERE id_ver=:t');
                    $deleter->bindValue('t',$res_array[$i]['id'],PDO::PARAM_INT);
                    $deleter->execute();
                    $deleter = $this->con->prepare('DELETE from version WHERE id=:t');
                    $deleter->bindValue('t',$res_array[$i]['id'],PDO::PARAM_INT);
                    $deleter->execute();
                    
                }
                $this->con->exec('UNLOCK TABLES');
            }
        }
    }
}
?>