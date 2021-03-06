
<?php
	require($_SERVER["DOCUMENT_ROOT"]."/include/config.php");
	require($_SERVER["DOCUMENT_ROOT"]."/server/request_processor.php");
	//---------------------------------------------
	$error = "";

	
	if(!isset($_SESSION)) session_start();

	if(isset($_REQUEST["code"]))
	{
		$code = $_REQUEST["code"];
		$params = ApplicationHelper::constructFirstAuthParams($code);
		$query_data = query("GET", "https://next.bitrix24.kz/oauth/token/", $params);

		if(isset($query_data["access_token"]))
		{
			
			//$_SESSION["query_data"]["ts"] = time();
			$query_data["ts"] = time();
			$json = json_encode($query_data);
			$writeResult = ApplicationHelper::writeToFile(AUTH_FILENAME, $json);

			$_SESSION["access_data"] = $query_data;
			$current_user = BitrixHelper::getCurrentUser($query_data["access_token"]);
			setcookie("user_email", $current_user["EMAIL"], time() + 3600, "/");
			$_SESSION["user_email"] = $current_user["EMAIL"];
			redirect(PATH);
			die();
		}
		else
		{
			$error = "Произошла ошибка авторизации! ".print_r($query_data, 1);
		}
	}

	$access_source = ApplicationHelper::readFromFile(AUTH_FILENAME);
	$access_data = $access_source != "null" ? ApplicationHelper::toJson($access_source) : NULL;

	if(!is_null($access_data) && isset($_REQUEST["action"]) && $_REQUEST["action"] == "refresh")
	{
		$params = ApplicationHelper::constructRefreshParams($access_data["refresh_token"]);
		$query_data = query("GET", "https://next.bitrix24.kz/oauth/token/", $params);
		
		if(isset($query_data["access_token"]))
		{
			//$_SESSION["query_data"] = $query_data;
			//$_SESSION["query_data"]["ts"] = time();
			$query_data["ts"] = time();
			$json = json_encode($query_data);
			$writeResult = ApplicationHelper::writeToFile(AUTH_FILENAME, $json);

			$_SESSION["access_data"] = $query_data;
			$current_user = BitrixHelper::getCurrentUser($query_data["access_token"]);
			setcookie("user_email", $current_user["EMAIL"], time() + 3600, "/");
			redirect(PATH);
			die();
		}
		else
		{
			$error = "Произошла ошибка авторизации! ".print_r($query_data);
		}
	}

	if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "clear") {
		$access_data = null;
		//$writeResult = write_to_file(AUTH_FILENAME, "null");
		setcookie("user_email", "", time() - 3600, "/");
		redirect(PATH);
		die();
	}
	$pageTitle = "Сервер Next.kz";
	require_once($_SERVER["DOCUMENT_ROOT"]."/web/header.php");

	if( is_null($access_data) || !isset($access_data["access_token"]) || (time() - $access_data["ts"]) > 3600)
	{
		if($error) echo '<b>'.$error.'</b>';
		setcookie("user_email", "", time() - 3600, "/");
		$link = "https://next.bitrix24.kz/oauth/authorize/?client_id=".CLIENT_ID."&state=JJHgsdgfkdaslg7lbadsfg";
	?>

		<div class="container">
			<div class="jumbotron">
				<h1>Сервер авторизации next.bitrix24.kz</h1> 
				<p>Авторизационные данные отсутствуют или устарели. Вы можете авторизоваться <a href="<?= $link ?>">снова</a></p>
			</div>
		</div>
	<?php
	}
	else
	{
		$currUser = BitrixHelper::getCurrentUser($access_data["access_token"]);
		$_SESSION["tokenemail"] = $currUser["EMAIL"];
		require_once($_SERVER["DOCUMENT_ROOT"]."/server/content.php");
		?>
		<div id="output" class="container alert alert-info">
		</div>


		<?php


		$data = process_user_request($_REQUEST, $access_data);

		if (!is_null($data)){
		?>
		<div class="container alert alert-info">
			<pre id="output"><?= var_export($data,true)?></pre>
		</div>
	<?php	
		}
	}
	require_once($_SERVER["DOCUMENT_ROOT"]."/web/footer.php");
?>
<script>
    $('#get_fields_sbt').on('click', function () {
        var method = $('#method_select_fields option:selected').val();
        //console.log("method is "+method);
        var response = getBitrixInstance(method);

    });

    $('#get_instance_sbt').on('click', function () {
        var method = $('#method_select_instance option:selected').val();
        var id = $('#deal_id').val();
        //console.log("method is "+method+". id="+id);
        var response = getBitrixInstance(method, id);

    });
</script>
