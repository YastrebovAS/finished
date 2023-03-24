<?php
session_start();
include('special/config_final.php');
include('special/admin_class_final.php');
$dis = new Admin($connection,'admin');
?>
<script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<body>
<h3>Если хотите выйти, воспользуйтесь кнопкой:</h3>
<form method = "post" enctype="multipart/form-data">
    <input type = "submit" name = "submit_exit" value = "выйти">
</form>
<?php
$dis->logout_from_session();
?>
<h3>Поступившие изобретения</h3>
<?php
$lifetime =120;
setcookie('admin', 'admin', time()+$lifetime,'/');
$dis->show_all_zero_version_of_article();
?>
<table>
    <tr><th><h3>Отправьте нулевую версию оценщику</h3></th></tr>
    <tr>
        <td>
            <form method = "post" enctype="multipart/form-data">
                <lable>Название изобретения</lable>
                <input type = "text" name = "name" pattern="[a-zA-Z0-9\s]+" required>
                <lable>Логин оценщика</lable>
                <input type = "text" name = "evaluator" pattern="[a-zA-Z0-9\s]+" required>
                <input type = "submit" name = "submit1">
            </form>
            <?php
            //print_r($_POST);
            if(isset($_POST['submit1'])){
                if(!preg_match("/^[a-zA-Z0-9\s]/", $_POST["name"])){
                    //echo("Invalid name");
                    //$ercounter++;
                    die("Invalid name");
                }
                if(!preg_match("/^[a-zA-Z0-9\s]/", $_POST["evaluator"])){
                    //echo("Invalid name");
                    //$ercounter++;
                    die("Invalid evaluator name");
                }
            }
            $lifetime =120;
            setcookie('admin', 'admin', time()+$lifetime,'/');
            $dis->send_zero_version_to_evaluator();
            ?>
        </td>
    </tr>

</table>

<h3>Информация по сайту</h3>
<?php
$dis->show_meta();
?>

<h3>Занятость оценщиков</h3>
<form id="see_ev_workload" method="post">
    <button type="submit" name="submit_new" value ="проверить">Проверить</button>
</form>
<div id="result"></div>

<script type ="text/javascript">
    $(document).ready(function() {
        //alert('I am here');
        $('#see_ev_workload').on('submit',function(e)
        {
            //alert('new');
            e.preventDefault();
            $.ajax({

                    type: "POST",
                    url: "evaluator_check.php",
                    success: function(data){
                        $('#result').append(data);
                    }

                }

            )
        })

    })
</script>
</body>