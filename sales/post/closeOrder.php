<?php
require($_SERVER["DOCUMENT_ROOT"]."/include/config.php");

$_REQUEST["action"] = isset($_REQUEST["action"]) ? $_REQUEST["action"] : null;
$_REQUEST["authId"] = isset($_REQUEST["authId"]) ? $_REQUEST["authId"] : null;

if (is_null($_REQUEST["authId"])) {
    redirect("../sales/index.php");
}

$_REQUEST["adminToken"] = isset($_REQUEST["adminToken"]) ? $_REQUEST["adminToken"] : ApplicationHelper::readAccessData(true);



$curr_user = BitrixHelper::getCurrentUser($_REQUEST["authId"]);
$_SESSION["user_name"] =  $curr_user["EMAIL"];
$_SESSION["user_id"] =  $curr_user["ID"];
$userId = $curr_user["ID"];

$actionPerformed = isset($_REQUEST["actionPerformed"]) ? $_REQUEST["actionPerformed"] : "initiated";
switch ($actionPerformed){
    case "initiated":

        require_once($_SERVER["DOCUMENT_ROOT"] . "/sales/shared/header.php");
        ?>
        <div class="container">
            <h1>Закрытие аренды и подсчет доп.заказа</h1>
            <div>
                Выберите из списка нужный номер заказа и осуществите подсчет доп.заказа, чтобы узнать, какие заказы были совершены на баре и сколько необходимо взять денег с клиента.
            </div>

            <div>
                <form id="form" class="form-horizontal" method="post" action="closeOrder.php">

                    <input type="hidden" name="actionPerformed" value="dealSelected">
                    <input type="hidden" name="userFullName" value="<?= $curr_user["NAME"]." ".$curr_user["LAST_NAME"] ?>">

                    <?php require_once $_SERVER["DOCUMENT_ROOT"]."/sales/shared/hidden-inputs.php"; ?>
                    <?php require_once $_SERVER["DOCUMENT_ROOT"]."/sales/post/centerDealSelect.php"; ?>

                    <?php
                    if ($userId == "30"){
                        ?>
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">Закрыть со статусом</span>
                                <select class="form-control" id="statusSelect" name="statusSelect">
                                    <option value="rentClosed">Аренда проведена</option>
                                    <option value="dealClosed">Сделка закрыта</option>

                                </select>
                                
                            </div>
                        </div>
                        <?php
                    } else {
                        echo "<input type='hidden' name='statusSelect' value='rentClosed'>";
                    }
                    ?>

                    <div class="form-group">

                        <div class="col-sm-9 col-sm-offset-3">
                            <div class="checkbox">
                                <label><input type="checkbox" id="confirmAction" name="confirmAction" required> Подтвердить закрытие</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-3">
                            <a href="https://b24.next.kz/sales/index.php?authId=<?= $_REQUEST["authId"] ?>" id="back" class="btn btn-default">В главное меню</a>
                            <button type="submit" id="submit-btn" class="btn btn-primary">Закрыть аренду</button>
                        </div>
                    </div>



                </form>
            </div>

            <div id="alert"></div>
        </div>

        <?php
        require_once $_SERVER["DOCUMENT_ROOT"]."/sales/post/postScript.php";
        ?>


        <?php

        break;

    case "dealSelected":

        if (!isset($_REQUEST["dealSelect"])) {

            ApplicationHelper::redirect("https://b24.next.kz/sales/post/closeOrder.php?error=Номер заказа пустой. Введите данные");
        }
        $deal = BitrixHelper::getDeal($_REQUEST["dealSelect"], $_REQUEST["adminToken"]);
        $title = $deal["TITLE"];
        $orderId = substr($title, 0, strpos($title, " "));
        $orderId = substr($orderId, 2);
        /*
        $params = [
            "event" => "CloseOrder",
            "orderId" => $orderId,
            "userId" => $userId
        ];
        $closeResponse = queryGoogleScript($params);
        $closeResponse = $closeResponse["result"];
        */
        $params = [
            "action" => "order.rent.close",
            "orderId" => $orderId,
            "userId" => $userId,
            "userFullName" => $_REQUEST["userFullName"],
            "status" => $_REQUEST["statusSelect"]

        ];
        $closeResponse = query("POST", "https://b24.next.kz/rest/order.php", $params);
        $closeResponse = $closeResponse["result"];

        $remainder = $closeResponse["remainder"];
        $barItems = $closeResponse["barItems"];
        $payed = $closeResponse["payed"];
        $totalCost = $closeResponse["totalCost"];

        $barItemsCount = count($barItems);
        $message = $closeResponse["message"];

        ApplicationHelper::debug(var_export($closeResponse, true));

        if($closeResponse["saveResult"] == false){
            $_GET["error"] = $message;
        } else {
            $_GET["success"] = $message;
        }
        require_once($_SERVER["DOCUMENT_ROOT"] . "/sales/shared/header.php");
        ?>
        <div class="container">
            <h1>Результат закрытия заказа</h1>
            <div id="toPrint">
                <h3>Заказ ID<?= $orderId?></h3>

                <?php
                require_once $_SERVER["DOCUMENT_ROOT"]."/sales/post/resultInfo.php";
                ?>


            </div>

            <div class="text-center">
                <a href="#" id="print" class="btn btn-default">Печать</a>
                <a href="https://b24.next.kz/sales/index.php?authId=<?= $_REQUEST["authId"] ?>" id="back" class="btn btn-default">В главное меню</a>
            </div>

        </div>
        <?php

        break;
}
?>
<script>
    $("#dealSelect").select2();
    $('#form').submit(function(){
        $("#submit-btn").prop('disabled',true); //
        $("a").addClass('disabled');
    });

    $('#print').click(function(){
        printContent("toPrint");
    });
    </script>
<?php
    require_once($_SERVER["DOCUMENT_ROOT"] . "/sales/shared/footer.php");
?>
