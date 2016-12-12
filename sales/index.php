<?php

	if (!isset($_SESSION)) session_start();
	require($_SERVER["DOCUMENT_ROOT"]."/include/config.php");
	require($_SERVER["DOCUMENT_ROOT"]."/include/help.php");
	require($_SERVER["DOCUMENT_ROOT"]."/Helpers/BitrixHelperClass.php");




	$auth_id = isset($_REQUEST["AUTH_ID"]) ? $_REQUEST["AUTH_ID"] : null;
	$auth_id = isset($_REQUEST["auth_id"]) ? $_REQUEST["auth_id"] : $auth_id;

	if (is_null($auth_id) || $auth_id == ""){
		redirect("../sales/index.php?error=Нет авторизации через CRM Битрикс24!");
	}

	$curr_user = BitrixHelper::getCurrentUser($auth_id);
	$_SESSION["user_name"] =  $curr_user["EMAIL"];
	$_SESSION["user_id"] =  $curr_user["ID"];

	require_once($_SERVER["DOCUMENT_ROOT"] . "/sales/shared/header.php");
?>


	<div class="container">

        <!--a href="#" id="print" type="button" class="btn btn-default">Печать</a-->
		<table id="tableToPrint" class="table table-striped">
			<tr>
				<td><b>Продажа буса</b></td>
				<td>Продажа буса в одном из центров NEXT</td>
				<td><a class="btn btn-default" href="/sales/contact.php?action=booth&auth_id=<?= $auth_id?>" role="button">Открыть &raquo;</a></td>
			</tr>

			<tr>
				<td><b>Предзаказник аренды</b></td>
				<td>Создать предзаказник аренды, сделки с существующим контактом</td>
				<td><a class="btn btn-default" href="/sales/contact.php?action=preorder&auth_id=<?= $auth_id?>" role="button">Открыть &raquo;</a></td>
			</tr>

			<?php if ($_SESSION["user_id"] == "30" || $_SESSION["user_id"] == "1" || $_SESSION["user_id"] == "98"){
				?>
				<tr>
					<td><b>Школы и лагеря <span class="label label-info">beta</span></b></td>
					<td>Форма продажи для школ и детских лагерей</td>
					<td><a class="btn btn-default" href="/sales/company.php?action=school&auth_id=<?= $auth_id?>" role="button">Открыть &raquo;</a></td>
				</tr>

                <tr>
                    <td><b>Школы и лагеря (Редактирование заказа)<span class="label label-info">beta</span></b></td>
                    <td>Форма продажи для школ и детских лагерей</td>
                    <td><a class="btn btn-default" href="/sales/school/school.php?action=school&action_performed=school_find&auth_id=<?= $auth_id?>" role="button">Открыть &raquo;</a></td>
                </tr>

				<tr>
					<td><b>Корпоративная продажа</b></td>
					<td>Форма продажи для предприятий. Создает заказ, сделку из существующей компании</td>
					<td><a class="btn btn-default disabled" href="/sales/company.php?action=corporate&auth_id=<?= $auth_id?>" role="button" >Открыть &raquo;</a></td>
				</tr>
				<?php
			}
			?>

		</table>


	</div>
    <script>
        $('#print').click(function(){
            printContent("tableToPrint");
        });
    </script>
	

	

	<?php require_once($_SERVER["DOCUMENT_ROOT"] . "/sales/shared/footer.php"); ?>