<?php
session_start();
include('special/config_final.php');
include('special/evaluator_class_final.php');
echo("<h1>Добрый день</h1>");
echo("<h3>Вы зашли как пользователь - оценщик ".$_SESSION["session_username"]."</h3>");

$lifetime = 120;
setcookie('evaluator', $_SESSION["session_username"], time()+$lifetime,'/');
$username = $_SESSION["session_username"];
?>
<h3>Если хотите выйти, воспользуйтесь кнопкой:</h3>
    <form method = "post" enctype="multipart/form-data">
        <input type = "submit" name = "submit_exit" value = "Выйти">
    </form>
    <?php
    setcookie('evaluator', $username, time()+$lifetime,'/');
    logout($connection);
    ?>
<script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<?php
echo("<h3>Направленные вам заявки на регистрацию изобретений:</h3>");
article_evaluator_show($connection);

?>
<html lang="ru">
 <head>
  <meta charset="utf-8">
  <script>
   function VAKappear() {
    var str = document.getElementById("email").value;
    var status = document.getElementById("status");
    var re = /^[^\s()<>@,;:\/]+@\w[\w\.-]+\.[a-z]{2,}$/i;
    if (re.test(str)) status.innerHTML = "Адрес правильный";
      else status.innerHTML = "Адрес неверный";
    if(isEmpty(str)) status.innerHTML = "Поле пустое";
   }
   function isEmpty(str){
    return (str == null) || (str.length == 0);
   }
  </script>
 </head> 
<body>
    <h3>В замечаниях используйте только заглавные и прописные Английские и Русские буквы и пробел</h3>
    <table>
        <tr>
            <th><h3>Замечание автору</h3></th>
            <th><h3>Перенаправьте заявку редактору</h3></th>
            
        </tr>
        <tr>
            <td>
    <form method = "post" enctype="multipart/form-data">
        <lable>Title</lable>
        <input type = "text" name = "title" pattern="[a-zA-Z0-9\s]+"  required>
        <lable>Problem</lable>
        <input type = "text" name = "problem" pattern="[a-zA-Z0-9\s]+" lang="ru"required>
        <input type = "submit" name = "submit">
    </form>
    <?php
    setcookie('evaluator', $username, time()+$lifetime,'/');
    send_problems_aut($connection)
    ?>
    </td>
    <td>
    <form method = "post" enctype="multipart/form-data">
        <lable>Имя пользователя редактора</lable>
        <input type = "text" id ="redactor" name = "redactor" required>
        <lable>Название изобретения</lable>
        <input type = "text" name = "name" pattern="[a-zA-Z0-9\s]+" required>
        <input type = "submit" name = "submit3">
    </form>
    <?php
    setcookie('evaluator', $username, time()+$lifetime,'/');
    send_article_cor($connection);
    ?>
    </td>
    
    
        </tr>
    </table>
<table>
    <tr><th><h3>Посмотрите, какие ошибки авторы нашли у вас:</h3></th><th><h3>Занятость редакторов</h3></th> </tr>
    <tr>
    <td>
    <?php
    show_problems($connection);
    ?>
    </td>
    <td>
    <form id="see_workload" method="post">
        <button type="submit" name="submit_new" value ="проверить">Проверить</button>
    </form>
    <div id="res"></div>
    
    <script type ="text/javascript">
        $(document).ready(function() {
            //alert('I am here');
            $('#see_workload').on('submit',function(e)
            {
                //alert('new');
                e.preventDefault();
                $.ajax({
                    
                    type: "POST",
                    url: "redactor_check.php",
                    success: function(data){
                        $('#res').append(data);
                    }

                }

                )
            })
            
        })
    </script>
    </td>
    </tr>
</table>
</body>