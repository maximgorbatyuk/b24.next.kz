<?php

/**
 * Created by PhpStorm.
 * User: Next
 * Date: 14.12.2016
 * Time: 8:40
 */
class OrderHelper
{

    public static function ConstructOrder(array $request, $adminToken){
        if ($request["orderId"] == "" ){
            $url = "https://script.google.com/macros/s/AKfycbxjyTPPbRdVZ-QJKcWLFyITXIeQ1GwI7fAi0FgATQ0PsoGKAdM/exec";
            $idData = query("GET", $url, array(
                "event" => "OnIdIncrementedRequested"
            ));

            $id = $idData["result"];
            $order = array();
            $order["Id"] = $id;



        } else {
            $id = $request["orderId"];
            $data = queryGoogleScript(array(
                "event" => "OnOrderRequested",
                "id" => $id
            ));
            $order = $data["result"];
            //$order["Id"] = $id;
        }

        switch ($request["status"]){
            case "initiated":
                $order["Status"] = "Заказ подтвержден";
                break;
            case "conducted":
                $order["Status"] = "Аренда проведена";
                break;
            case "closed":
                $order["Status"] = "Сделка закрыта";
                break;
            case "canceled":
                $order["Status"] = "Аренда отменена";
                break;

            default:
                $order["Status"] = "Заказ подтвержден";
                break;
        }

        $order["DealId"] = $request["dealId"];
        $order["ContactId"] = $request["contactId"];
        // $order["CompanyId"] = $request["companyId"];
        //--------------------------------------------
        $order["ClientName"] = $request["contactName"];
        $order["KidName"] = "";
        $order["Phone"] = $request["contactPhone"];
        $order["Center"] = $request["centerName"];

        $datetime = BitrixHelper::constructDatetime($request["date"], $request["time"]);
        $ts = BitrixHelper::constructTimestamp($request["date"], $request["time"]);

        $order["ts"] = $ts;
        $order["DateOfEvent"] = $datetime; //sourceOrder["DateOfEvent"];
        $time = strtotime($datetime);
        $time = $time - (3 * 3600);


        $order["Date"] = str_replace(" ", "T", formatDate($datetime, "Y-m-d H:i:s+06:00"));
        $order["DateAtom"] = str_replace(" ", "T", formatDate($datetime, "Y-m-d H:i:s+06:00"));

        $order["TotalCost"] = $request["moneyToCash"];
        $order["UserId"] = $request["userId"];
        $order["User"] = $request["userFullname"];
        $order["FullPriceType"] = OrderHelper::isDateHoliday($datetime);
        //------------------------------------
        $event = array(
            'Event' => "",
            'Zone' => "",
            'Date' => str_replace(" ", ".", formatDate($datetime, "d m Y")),
            'StartTime' => str_replace(":", "-", $request["time"]),
            'Duration' => "",
            'GuestCount' => "",
            'Cost' => 0,

        );
        $order["Event"] = $event;
        //----------------------------
        $clientInfo = array(
            'Id' => $id,
            'ClientName' => $order["ClientName"],
            'KidName' => $order["KidName"],
            'Code' => "",
            'Status' => "Не требуется",
            'Date' => "",
        );
        $order["VerifyInfo"] = $clientInfo;
        //----------------------------
        $financeInfo = array (
            'Id' => $id,
            'Remainder' =>  0,
            'Payed' => 0,
            'PaymentsView' => "",
            'Payments' => array(),
            'TotalDiscount' => $request["discount"],
            'Increase' => 0,
            'IncreaseComment' => "",
            'Discount' => $request["discount"],
            'DiscountComment' => $request["discountComment"],
            'LoyaltyCode' => "",
            'LoyaltyDiscount' => 0,
            'AgentCode' => "",
            'AgentDiscount' => 0,
        );
        if ($financeInfo["DiscountComment"] == "") $financeInfo["Discount"] = 0;
        $order["FinanceInfo"] = $financeInfo;
        //--------------------------------------------
        if (isset($request["hasFood"]) && $request["hasFood"] == "yes") {

            $banquet = array(
                'BanquetId' => "",
                'Comment' => "",
                'TranscriptStr' => "",
                'Items' => array(
                    /*
                    0 => array(
                        'name' => '',
                        'price' => "",
                        'measure' => 'шт',
                        'note' => '',
                        'increasePercent' => 0,
                        'itemId' => "",
                        'count' => "",
                        'cost' => "",
                    )*/
                ),
                'Cost' => "",
                'Cake' => "",
                'Candybar' => "",
                'Pinata' => "",
                'Date' => date("d.m.Y", $datetime),
                'CandybarCost' => 0,
                'CakeCost' => 0,
                'PinataCost' => 0,

            );

            $order["BanquetInfo"] = $banquet;
        } else {
            $order["BanquetInfo"] = null;
        }

        if (isset($request["hasOptional"]) && $request["hasOptional"] == "yes"){
            $optional = [
                'OptionalId' => "",
                'Comment' => "",
                'TranscriptStr' => "",
                'Items' => [
                    /*
                   0 => array(
                       'name' => '',
                       'price' => "",
                       'measure' => 'шт',
                       'note' => '',
                       'increasePercent' => 0,
                       'itemId' => "",
                       'count' => "",
                       'cost' => "",
                   )*/
                ],
                'Cost' => "",
                'Entertainment' => "",
                'Cameraman' => "",
                'TableBooking' => "",
                'MonitorSaver' => "",
                'Date' => "",
            ];

            $order["OptionalInfo"] = $optional;
        }else {
            $order["OptionalInfo"] = null;
        }


        $order["Comment"] = $request["comment"];
        //--------------------------------
        $order["CreatedAt"] = str_replace(" ", "T", date("Y-m-d H:i:s+06:00", time() + 3600*6));
        $order["UpdatedAt"] = $order["CreatedAt"];
        $order["TaskId"] = null;

        $contact = BitrixHelper::getContact($request["contactId"], $adminToken);
        $order["ContactSource"] = !is_null($contact) ?  BitrixHelper::getInstanceSource($contact["ID"], "contact", $adminToken) : "Ошибка. Контакт не существует";
        $order["LeadSource"] = !is_null($contact) && !is_null($contact["LEAD_ID"]) ?  BitrixHelper::getInstanceSource($contact["LEAD_ID"], "lead", $adminToken) : "Лид отсутствует";

        return $order;
    }

    public static function ConstructSchoolOrder(array $request, $adminToken){
        if ($request["orderId"] == "" ){
            $url = "https://script.google.com/macros/s/AKfycbxjyTPPbRdVZ-QJKcWLFyITXIeQ1GwI7fAi0FgATQ0PsoGKAdM/exec";
            $idData = query("GET", $url, array(
                "event" => "OnIdIncrementedRequested"
            ));

            $id = $idData["result"];
            $order = array();
            $order["Id"] = $id;



        } else {
            $id = $request["orderId"];
            $data = queryGoogleScript(array(
                "event" => "OnOrderRequested",
                "id" => $id
            ));
            $order = $data["result"];
            //$order["Id"] = $id;
        }

        switch ($request["status"]){
            case "initiated":
                $order["Status"] = "Заказ подтвержден";
                break;
            case "conducted":
                $order["Status"] = "Аренда проведена";
                break;
            case "closed":
                $order["Status"] = "Сделка закрыта";
                break;
            case "canceled":
                $order["Status"] = "Аренда отменена";
                break;

            default:
                $order["Status"] = "Заказ подтвержден";
                break;
        }

        $order["DealId"] = $request["dealId"];
        $order["ContactId"] = $request["contactId"];
        $order["CompanyId"] = $request["companyId"];
        //--------------------------------------------
        $order["ClientName"] = $request["contactName"];
        $order["KidName"] = $request["companyName"];
        $order["Phone"] = $request["contactPhone"];
        $order["Center"] = $request["centerName"];

        $datetime = BitrixHelper::constructDatetime($request["date"], $request["time"]);
        $ts = BitrixHelper::constructTimestamp($request["date"], $request["time"]);

        $order["ts"] = $ts;
        $order["DateOfEvent"] = $datetime; //sourceOrder["DateOfEvent"];
        $time = strtotime($datetime);
        $time = $time - (3 * 3600);


        $order["Date"] = str_replace(" ", "T", formatDate($datetime, "Y-m-d H:i:s+06:00"));
        $order["DateAtom"] = str_replace(" ", "T", formatDate($datetime, "Y-m-d H:i:s+06:00"));

        $order["TotalCost"] = $request["moneyToCash"];
        $order["UserId"] = $request["userId"];
        $order["User"] = $request["userFullname"];
        $order["FullPriceType"] = OrderHelper::isDateHoliday($datetime);
        //------------------------------------
        $event = array(
            'Event' => "Школа/лагерь",
            'Zone' => "Без зоны",
            'Date' => str_replace(" ", ".", formatDate($datetime, "d m Y")),
            'StartTime' => str_replace(":", "-", $request["time"]),
            'Duration' => $request["duration"],
            'GuestCount' => $request["pupilCount"],
            'Cost' => $request["moneyToCash"],
            //------------------------------
            // дополнительные данные для школ
            'TeacherCount' => $request["teacherCount"],
            'Pack' => $request["pack"],
            'PackPrice' => $request["packagePrice"],
            'PupilCount' => $request["pupilCount"],
            'PupilAge' => $request["pupilAge"],
            'Subject' => $request["subject"],

            'HasTransfer' => $request["hasTransfer"],
            'HasFood' => $request["hasFood"],
            'TransferCost' => $request["transferCost"],

            'TeacherBribePercent' => $request["bribePercent"],
            'TeacherBribe' => $request["bribe"],

            'Comment' => $request["comment"],
            'FoodPackCount' => $request["foodPackCount"],
            'FoodPackPrice' => 0,

        );
        $order["Event"] = $event;
        //----------------------------
        $clientInfo = array(
            'Id' => $id,
            'ClientName' => $order["ClientName"],
            'KidName' => $order["KidName"],
            'Code' => "No code",
            'Status' => "Не требуется",
            'Date' => "",
            'CompanyId' => $request["companyId"],
        );
        $order["VerifyInfo"] = $clientInfo;
        //----------------------------
        $paymentDate = formatDate($datetime, "Y-m-d H:i");
        $financeInfo = array (
            'Id' => $id,
            'Remainder' =>  $request["moneyToCash"],
            'Payed' => 0,
            'PaymentsView' => "",
            'Payments' => array(),
            'TotalDiscount' => $request["discount"],
            'Increase' => 0,
            'IncreaseComment' => "",
            'Discount' => $request["discount"],
            'DiscountComment' => $request["discountComment"],
            'LoyaltyCode' => "",
            'LoyaltyDiscount' => 0,
            'AgentCode' => "",
            'AgentDiscount' => 0,
        );
        if ($financeInfo["DiscountComment"] == "") $financeInfo["Discount"] = 0;
        $order["FinanceInfo"] = $financeInfo;
        //--------------------------------------------
        if ($request["hasFood"] == "yes") {

            if (is_null($order["BanquetInfo"])) {
                $isNew = "1";
                $tzfId = "";
            }
            else {
                $isNew = "0";
                $tzfId = $order["BanquetInfo"]["BanquetId"];
            }

            $tzfData = queryGoogleScript(array(
                "event" => "OnTzfSchoolCreateRequested",
                "user" => $request["userFullname"],
                "itemCount" => $request["pupilCount"],
                "center" => $request["centerNameRu"],
                "date" => str_replace(" ", ".", formatDate($datetime, "d m Y")),
                "orderId" => $id,
                "isNew" => $isNew,
            ));
            $tzf = $tzfData["result"];
            if (is_null($tzf)) $order["BanquetInfo"] = null;
            else {

                if ($isNew == "1") $tzfId = $tzf["tzfId"];
                $banquet = array(
                    'BanquetId' => $tzfId,
                    'Comment' => "",
                    'TranscriptStr' => "Фуд пакет для школ (цена ".$tzf["price"].", кол-во ".$request["pupilCount"].") - ".$tzf["cost"],
                    'Items' => array(
                        0 => array(
                            'name' => 'Фуд пакет для школ',
                            'price' => $tzf["price"],
                            'measure' => 'шт',
                            'note' => '',
                            'increasePercent' => 0,
                            'itemId' => $tzf["bitrixId"],
                            'count' => $request["pupilCount"],
                            'cost' => $tzf["cost"],
                        )
                    ),
                    'Cost' => $tzf["cost"],
                    'Cake' => "",
                    'Candybar' => "",
                    'Pinata' => "",
                    'Date' => date("d.m.Y", $datetime),

                    'CandybarCost' => 0,
                    'CakeCost' => 0,
                    'PinataCost' => 0,

                );

                $order["BanquetInfo"] = $banquet;
                $order["Event"]["FoodPackPrice"] = $tzf["price"];
            }


        } else {
            $order["BanquetInfo"] = null;
        }

        $order["OptionalInfo"] = null;

        $comment = $request["comment"] != "" ? $request["comment"]."\n" : "";
        $comment .= "--- Служебная информация ---\n";
        $comment .= "Возраст детей: ".$request["pupilAge"]."\n";
        $comment .= "Тема урока: ".$request["subject"]."\n";
        $comment .= "Выбранный пакет: ".$request["packName"]."\n";

        if ($request["hasFood"] == "yes"){
            $comment .= "Фуд-пакет, наличие: есть\n";
            $comment .= "Фуд-пакет, стоимость: ".$request["foodPackCost"]."\n";
        } else $comment .= "Фуд-пакет, наличие: отсутствует\n";


        $comment .= "Процент учителю: ".$request["bribe"]."\n";
        $comment .= "Стоимость трансфера: ".$request["transferCost"]."\n";

        $order["Comment"] = $comment;
        //--------------------------------
        $order["CreatedAt"] = str_replace(" ", "T", date("Y-m-d H:i:s+06:00", time() + 3600*6));
        $order["UpdatedAt"] = $order["CreatedAt"];
        $order["TaskId"] = null;

        $contact = BitrixHelper::getContact($request["contactId"], $adminToken);
        $order["ContactSource"] = !is_null($contact) ?  BitrixHelper::getInstanceSource($contact["ID"], "contact", $adminToken) : "Ошибка. Контакт не существует";
        $order["LeadSource"] = !is_null($contact) && !is_null($contact["LEAD_ID"]) ?  BitrixHelper::getInstanceSource($contact["LEAD_ID"], "lead", $adminToken) : "Лид отсутствует";

        return $order;
    }

    public static function EventEmailContent(array $order){
        $content = "";
        $content .= "<table border=0 cellspacing=0 cellpadding=5> ";
        $content .= "<tr><td>Клиент</td> <td>".$order["ClientName"]." (".$order["ClientName"]["KidName"].")</td> </tr>";
        $content .= "<tr><td>Номер телефона</td> <td>".$order["Phone"]."</td> </tr>";
        $content .= "<tr><td>Центр проведения</td> <td>".$order["Center"]."</td> </tr>";
        $content .= "<tr><td>Дата мероприятия</td> <td>".$order["Event"]["Date"]."</td> </tr>";
        $content .= "<tr><td>Начало в</td> <td>".$order["Event"]["StartTime"]."</td> </tr>";
        $content .= "<tr><td>Приглашено гостей</td> <td>".$order["Event"]["GuestCount"]."</td> </tr>";
        $content .= "</table>";
        return $content;
    }

    public static function FullEventEmailContent(array $order){
        $content = "";
        $content .= "<table border=1 bordercolor=#c0c0c0 cellspacing=0 cellpadding=5> ";
        $content .= "<tr><th>Опция</th> <th>Описание</th> <th>Стоимость</th> </tr>";
        $content .= "<tr><td>Мероприятие</td> <td>".$order["Event"]["Event"]." (".$order["Event"]["Zone"].")</td> <td>".$order["Event"]["Cost"]."</td> </tr>";

        $banquetText = "";
        $banquetCost = 0;
        if (!is_null($order["BanquetInfo"])){
            $count = 1;
            foreach ($order["BanquetInfo"]["Items"] as $item){
                $banquetText .= $count.") ".$item["name"]." (цена ".$item["price"].", кол-во ".$item["count"]."), стоимость ".$item["cost"]."<br>";
                $count++;
            }
            $banquetCost = $order["BanquetInfo"]["Cost"];
        }

        $content .= "<tr><td>Фуршет</td> <td>$banquetText</td> <td>$banquetCost</td> </tr>";
        //---------------------------------------
        $optionalText = "";
        $optionalCost = 0;
        if (!is_null($order["OptionalInfo"])){
            $count = 1;
            foreach ($order["OptionalInfo"]["Items"] as $item){
                $optionalText .= $count.") ".$item["name"]." (цена ".$item["price"].", кол-во ".$item["count"]."), стоимость ".$item["cost"]."<br>";
                $count++;
            }
            $optionalCost = $order["OptionalInfo"]["Cost"];
        }
        $content .= "<tr><td>Дополнительные услуги</td> <td>$optionalText</td> <td>$optionalCost</td> </tr>";
        //------------------------------------------
        $content .= "<tr><td>Комментарий к заказу</td> <td>".$order["Comment"]."</td> <td></td> </tr>";
        $discountText = "";
        if ($order["FinanceInfo"]["Discount"] != 0) $discountText .= $order["FinanceInfo"]["Comment"]."<br>";
        if ($order["FinanceInfo"]["LoyaltyDiscount"] != 0) $discountText .= "Скидка по коду лояльности<br>";
        if ($order["FinanceInfo"]["AgentDiscount"] != 0) $discountText .= "Скидка по коду агента<br>";

        $content .= "<tr><td>Скидки</td> <td>$discountText</td> <td>-".$order["FinanceInfo"]["TotalDiscount"]."</td> </tr>";
        $content .= "<tr><td>Надбавки к стоимости</td> <td>".$order["FinanceInfo"]["IncreaseComment"]."</td> <td>+".$order["FinanceInfo"]["Increase"]."</td> </tr>";
        $content .= "<tr><td>ИТОГО</td> <td>Итоговая стоимость заказа</td> <td>".$order["TotalCost"]."</td> </tr>";
        $content .= "</table>";
        return $content;
    }

    public static function GetOrderEmailContent(array $order, $title = "Создан заказ") {



        $content = "";
        $content .= "<h1>$title ID".$order["Id"]."</h1>";
        $content .= "<h2>".$order["Event"]["Zone"]."</h2>";
        $content .= "<hr>";
        $content .= "<p>Информация о мероприятии в центре развлечения NEXT. Дата создания: ".$order["CreatedAt"]."</p>";
        
        $content .= "<p>";
        $content .= self::EventEmailContent($order);
        $content .= "</p>";
        
        $content .= "<p>";
        $content .= self::FullEventEmailContent($order);
        $content .= "</p>";
        
        #CHANGES_IF_NECCESSARY
        
        $content .= "<p>Спасибо за размещение заказа в нашей компании! ".
            "Если у Вас возникнут вопросы или дополнения по Вашему заказу, обращайтесь по следующим телефонам:".
            "</p>";

        $content .= "<p>";
        $content .= "<b>Служба заботы о клиенте:</b> #SERVICE_PHONE";
        $content .= "<br>";
        $content .= "<br>";
        $content .= "<b>#CENTER_NAME:</b> #CENTER_PHONE";
        $content .= "</p>";
        
        $content .= "<table border=0 cellspacing=0 cellpadding=5> ";
        $content .= "<tr><td><br><br>Подпись клиента:</td> <td><br><br>___________________________</td> </tr>";
        $content .= "<tr><td><br><br>Подпись менеджера:</td> <td><br><br>___________________________</td> </tr>";
        $content .= "</table>";
    }

    public static function GetOrderEmailContentOfFood(array $order){
        $content = "<h1>Заказ аренды ID".$order["Id"]."</h1>";
        $content .= "<h2>Сводка фуршета ".$order["BanquetInfo"]["BanquetId"]."</h2>";
        $content .= "<hr>";
        $content .= "<p>Информация о фуршете для заказа аренды</p>";
                    
        $content .= "<p>";
        $content .= "<table border=0 cellspacing=0 cellpadding=5> ";
        $content .= "<tr><td>Клиент</td> <td>".$order["Id"]."</td> </tr>";
        $content .= "<tr><td>Номер телефона</td> <td>#PHONE</td> </tr>";
        $content .= "<tr><td>Центр проведения</td> <td>#CENTER_NAME</td> </tr>";
        $content .= "<tr><td>Дата мероприятия</td> <td>#EVENT_DATE</td> </tr>";
        $content .= "<tr><td>Начало в</td> <td>#EVENT_TIME</td> </tr>";
        $content .= "<tr><td>Приглашено гостей</td> <td>#GUEST_COUNT</td> </tr>";
        $content .= "</table>";
        $content .= "</p>";

        $content .= "<p>#BANQUET_TABLE_TEXT</p>";

        $content .= "<hr>";
        $content .= "<p><b>Комментарий к заказу фуршета:</b><br>#BANQUET_COMMENT</p>";

        $content .= "<p>";
        $content .= "<b>Комментарий к заказу:</b> <br>#ORDER_COMMENT</p>";
    }

    public static function GetPaymentEmailContent(array $order, $action = "created"){
        $content = "<h2>Оплата за заказ ID#ORDER_ID</h2>";
        $content .= "<hr>";
        $content .= "<p>Внесена оплата за заказ ID#ORDER_ID. Информация о заказе и внесенной оплате:</p>";
        $content .= "<p>";
        $content .= "<table border=0 cellspacing=0 cellpadding=5> ";
        $content .= "<tr><td>Сумма оплаты:</td> <td>#PAYMENT_VALUE</td> </tr>";
        $content .= "<tr><td>Дата чека:</td> <td>#RECEIPT_DATE</td> </tr>";
        $content .= "<tr><td>Номер чека:</td> <td>#RECEIPT_NUMBER</td> </tr>";
        $content .= "</table>";
        $content .= "</p>";

#ORDER_INFO_TEMPLATE";
    }


    public static function GetOrderInfoEmail(array $order){
        $content = "<p>";
        $content .= "<table border=1 bordercolor=#c0c0c0 cellspacing=0 cellpadding=5>";
        $content .= "<tr><th>Опция</th> <th>Значение</th></tr>";
        $content .= "<tr><td>Мероприятие</td> <td>#EVENT</td> </tr>";
        $content .= "<tr><td>Клиент</td> <td>#CLIENT_NAME</td> </tr>";
        $content .= "<tr><td>Номер телефона</td> <td>#PHONE</td> </tr>";
        $content .= "<tr><td>Центр проведения</td> <td>#CENTER_NAME</td> </tr>";
        $content .= "<tr><td>Дата мероприятия</td> <td>#EVENT_DATE</td> </tr>";
   
        $content .= "<tr> <td>Оплачено за заказ</td> <td>#PAYMENT_VALUE</td> </tr>";
        $content .= "<tr> <td>Остаток по оплате</td> <td>#PAYMENT_REMAINDER</td> </tr>";
        $content .= "<tr> <td>Итоговая сумма за заказ</td> <td>#TOTAL_COST</td> </tr>";
        $content .= "</table>";
        $content .= "</p>";

        $content .= "<p>";
        $content .= "<b>Служба заботы о клиенте:</b> #SERVICE_PHONE";
        $content .= "<br>";
        $content .= "<br>";
        $content .= "<b>#CENTER_NAME:</b> #CENTER_PHONE";
        $content .= "</p>";

        $content .= "<p>";
        $content .= "<table border=0 cellspacing=0 cellpadding=5> ";
        $content .= "<tr><td><br><br>Подпись клиента:</td> <td><br><br>___________________________</td> </tr>";
        $content .= "<tr><td><br><br>Подпись менеджера:</td> <td><br><br>___________________________</td> </tr>";
        $content .= "</table>";
        $content .= "</p>";
    }

    public static function updateOrderDeal($order, $admin_token, $isExisted = false, $id = 0) {
        $payload = array();
        $payload["fields[CONTACT_ID]"] = $order["ContactId"];
        switch ($order["Event"]["Event"]){
            case "Аренда зоны":
                $payload["fields[TYPE_ID]"] = "SALE";
                break;

            case "Happy Birthday Pack":
                $payload["fields[TYPE_ID]"] = "1";
                break;

            case "Продажа буса":
                $payload["fields[TYPE_ID]"] = "5";
                break;
        }
        $payload["fields[ASSIGNED_BY_ID]"] = $order["UserId"];
        $payload["fields[TITLE]"] = "ID".$order["Id"]." ".$order["Event"]["Event"]." (".$order["Event"]["Zone"].")";
        switch($order["Status"]) {
            //
            case "Заказ подтвержден":
                $payload["fields[STAGE_ID]"] = "2"; // Подготовка аренды
                break;
            case "Аренда проведена":
                $payload["fields[STAGE_ID]"] = "7"; // Аренда проведена
                break;
            case "Аренда отменена":
                $payload["fields[STAGE_ID]"] = "4"; // Аренда отменена / сделка закрыта
                break;
            case "Сделка закрыта":
                $payload["fields[STAGE_ID]"] = "WON"; // оплата в нуле
                break;
        }
        $payload["fields[OPPORTUNITY]"] = $order["TotalCost"]; // UF_CRM_1474887988
        $payload["fields[UF_CRM_1474887988]"] = $order["TotalCost"];

        $payload["fields[UF_CRM_1467690712]"] = $order["Date"]; // string-UF_CRM_1474793606 datetime-UF_CRM_1467690712
        $payload["fields[UF_CRM_1474793606]"] = $order["DateAtom"];
        $payload["fields[UF_CRM_1467878967]"] = $order["Event"]["GuestCount"];

        $centerB24 = "128"; // NEXT Aport по дефолту UF_CRM_1468830187
        if ($order["Center"] == "NEXT Esentai") $centerB24 = "130";
        if ($order["Center"] == "NEXT Promenade") $centerB24 = "132";
        $payload["fields[UF_CRM_1468830187]"] = $centerB24;

        $payload["fields[UF_CRM_1471942476]"] = $order["FinanceInfo"]["Payed"];
        $payload["fields[UF_CRM_1473564600]"] = $order["FinanceInfo"]["Remainder"];

        $count = 0;
        foreach ($order["FinanceInfo"]["Payments"] as $key => $payment) {
            $paymentText = "[".$payment["receiptDate"]."] ".$payment["paymentValue"];
            $payload["fields[UF_CRM_1474861311][".$count."]"] = $paymentText;
            $count++;
        }
        $payload["fields[COMMENTS]"] = $order["Comment"];

        $banquetStr = !is_null($order["BanquetInfo"]) ? $order["BanquetInfo"]["TranscriptStr"] : "Информации по фуршету нет";
        $optionalStr = !is_null($order["OptionalInfo"]) ? $order["OptionalInfo"]["TranscriptStr"] : "Информации по опционалке нет";
        $payload["fields[UF_CRM_1474794453]"] = $banquetStr;
        $payload["fields[UF_CRM_1474794474]"] = $optionalStr;

        $banquetId = !is_null($order["BanquetInfo"]) ? $order["BanquetInfo"]["BanquetId"] : "Без фуршета";
        $optionalId = !is_null($order["OptionalInfo"]) ? $order["OptionalInfo"]["OptionalId"] : "Без опционалки";
        $payload["fields[UF_CRM_1474795341]"] = $banquetId;
        $payload["fields[UF_CRM_1474795352]"] = $optionalId;

        $payload["fields[UF_CRM_1474794962]"] = $order["FinanceInfo"]["TotalDiscount"];
        $payload["fields[UF_CRM_1474794983]"] = $order["FinanceInfo"]["LoyaltyDiscount"];

        $discountIndex = 0;
        //UF_CRM_1467881660 - [] КОды на скидку
        if ($order["FinanceInfo"]["LoyaltyDiscount"] != 0) {
            $payload["fields[UF_CRM_1467881660][".$discountIndex."]"] = $order["FinanceInfo"]["LoyaltyCode"];
            $discountIndex++;
        }
        if ($order["FinanceInfo"]["AgentDiscount"] != 0) {
            //
            $payload["fields[UF_CRM_1467881660][".$discountIndex . "]"] = $order["FinanceInfo"]["AgentCode"];
            $discountIndex++;
        }

        $payload["fields[UF_CRM_1474794998]"] = $order["FinanceInfo"]["AgentDiscount"];
        $payload["fields[UF_CRM_1474795020]"] = $order["FinanceInfo"]["Discount"];
        $payload["fields[UF_CRM_1474795029]"] = $order["FinanceInfo"]["DiscountComment"];

        $payload["fields[UF_CRM_1474795045]"] = $order["FinanceInfo"]["Increase"];
        $payload["fields[UF_CRM_1474797140]"] = $order["FinanceInfo"]["IncreaseComment"];

        foreach ($payload as $key => $item) {//
            if (gettype($item) !== 'string') continue;
            $item = str_replace("\n", ". ", $item);
            $item = str_replace("\"", "'", $item);
            $payload[$key] = $item;
        }
        $payload["auth"] = $admin_token;


        if ($isExisted == true) {
            $method = "crm.deal.update";
            $payload["id"] = $id;
        } else {
            $method = "crm.deal.add";
        }

        $createResult = call($method, $payload);
        $dealId = $createResult["result"];
        return $dealId;

    }

    public static function updateDealProductSet(array $order, $token = null){
        $token = is_null($token) ? get_access_data(true) : $token;
        $eventId = 0;
        // $banquetItems = [];
        // $optionalItems = [];

        //----------------------
        $event = $order["Event"];
        $zone = $event["Zone"];
        $eventPrice = $order["Event"]["Cost"];
        switch($event["Event"]) {
            /*
            case  "Аренда зоны":
                $prices = GetAreaPrices();
                foreach ($prices as $key => $value) {
                if ($value["Zone"] != $zone) continue;
                $eventId = $value["ProductId"];
                break;
            }
            break;*/

            case  "Happy Birthday Pack":
                $eventId = 1636;
                break;
            case  "Корпоративное мероприятие":
                $eventId = 1638;
                break;
            case  "Школы и лагеря":
                $eventId = 1640;
                break;
        }
        //--------------------------------
        // $banquetItems = $order["BanquetInfo"] != null ? $order["BanquetInfo"]["Items"] : null;
        // $optionalItems = $order["OptionalInfo"] != null ? $order["OptionalInfo"]["Items"] : null;

        $payload = array();
        $payload["id"] = $order["DealId"];
        $payload["rows[0][PRODUCT_ID]"] = $eventId;
        $payload["rows[0][PRICE]"] = $eventPrice;
        $payload["rows[0][QUANTITY]"] = "1";
        $payload["auth"] = $token;
        //-----------------------------------------
        /*
        $itemIndex = 1;
        if ($banquetItems != null) {
          //
          for (var i in $banquetItems) {
              //
                $itemId = $banquetItems[i]["itemId"];
                $itemPrice = $banquetItems[i]["price"];
                $itemCount = $banquetItems[i]["count"];
              if ($itemId == "" || $itemId == 0) {
                  //
                  var $searchUrl = "https://next.bitrix24.kz/rest/crm.product.list";
                  var $payload = {
                      'filter[NAME]': banquetItems[i]["name"],
              'auth' : auth
            };
            var searchResult = PostJsonResponse(searchUrl, payload);
            if (searchResult != null && searchResult["total"] > 0) {
                //
                itemId = searchResult["result"][0]["ID"];
            }
            else {
                //
                var addResult = AddOrUpdateProduct(banquetItems[i], "208", auth); // 208 - неучтенные товары
                itemId = addResult != null ? addResult["result"] : null;
            }

          }
              if (itemId == null) continue;
              payload["rows["+itemIndex+"][PRODUCT_ID]"] = itemId;
              payload["rows["+itemIndex+"][PRICE]"] = itemPrice;
              payload["rows["+itemIndex+"][QUANTITY]"] = itemCount;
              itemIndex++;
          }
        }
        //---------------------------------------------------

        if (optionalItems != null) {
          for (var i in optionalItems) {
              //
              var itemId = optionalItems[i]["itemId"];
              var itemPrice = optionalItems[i]["price"];
              var itemCount = optionalItems[i]["count"];
              if (itemId == "" || itemId == 0) {
                  var addResult = AddOrUpdateProduct(optionalItems[i], "208", auth); // 208 - неучтенные товары
                  itemId = addResult != null ? addResult["result"] : null;
              }
              if (itemId == null) continue;
              payload["rows["+itemIndex+"][PRODUCT_ID]"] = itemId;
              payload["rows["+itemIndex+"][PRICE]"] = itemPrice;
              payload["rows["+itemIndex+"][QUANTITY]"] = itemCount;
              itemIndex++;
          }
        }
        */
        $productUpdate = call("crm.deal.productrows.set", $payload);
        return $productUpdate;
    }

    /**
     * Проверка, является ли дата выходным днем
     *
     * @param $date
     * @return bool
     */
    public static function isDateHoliday($date) {
        try {
            if (gettype($date) == "string") $date = new DateTime($date);
            if (is_null($date)) return true;
            $timestamp = $date->getTimestamp();
            $dayOfWeek = date("N", $timestamp);
            log_debug(var_export($dayOfWeek, true));
            return $dayOfWeek == 6 || $dayOfWeek == 7;

        } catch (Exception $ex){
            process_error(var_export($ex, true));
        }
        return true;
    }


}