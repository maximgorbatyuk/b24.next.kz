<?php
    require($_SERVER["DOCUMENT_ROOT"]."/include/config.php");
    require($_SERVER["DOCUMENT_ROOT"]."/include/help.php");
    require($_SERVER["DOCUMENT_ROOT"]."/Helpers/BitrixHelperClass.php");
    require($_SERVER["DOCUMENT_ROOT"]."/include/order_helper.php");


    $action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : null;
    $response = array("result" => null);

    if (is_null($action)) {
        $response["error"] = "not_implemented";
        header('Content-Type: application/json');
        echo json_encode($response);
        die();
    }

    switch ($action) {
        case 'schoolGetCost':
        case 'schoolGetCostSave':
            include $_SERVER["DOCUMENT_ROOT"]."/sales/shared/prices.php";

            log_debug(var_export($_REQUEST,true));

            switch ($_REQUEST["pack"]) {
                case 'basepack':
                    $packName = "Базовый";
                    break;
                case 'standartpack':
                    $packName = "Стандартный";
                    break;
                case 'newyear':
                    $packName = "Новогодний";
                    break;
                case 'allinclusive':
                    $packName = "Все включено";
                    break;
                default:
                    $packName = "Пакет не определен";
                    break;
            }

            switch ($_REQUEST["center"]) {
                case 'next_ese':
                    $centerName = "NEXT Esentai";
                    break;
                case 'next_apo':
                    $centerName = "NEXT Aport";
                    break;

                case 'next_pro':
                    $centerName = "NEXT Promenade";
                    break;
            }

            //$packPrice = GetPackPrice($_REQUEST["pack"], $_REQUEST["center"]);
            $packPrice = floatval($_REQUEST["package_price"]);

            $pupilCount = intval($_REQUEST["pupil_count"]);
            $teacherCount = intval($_REQUEST["teacher_count"]);
            $foodPackCount = intval($_REQUEST["foodpack_count"]);
            $pupilAge = $_REQUEST["pupil_age"];

            $packCost = $packPrice * $pupilCount;
            $teacherPackCost = $teacherCount * TEACHERPACK_COST;
            $foodPackCost = $pupilCount * FOODPACK_COST;
            $transferCost = floatval($_REQUEST["transfer_cost"]);

            $discount = floatval($_REQUEST["discount"]);
            //---------------------------------------------------------
            $orderCost = $packCost - $discount;
            $orderCost = $orderCost > 0 ? $orderCost : 0;

            $bribePercent = floatval($_REQUEST["bribe_percent"]);
            $bribePercent = $bribePercent > $packPrice ? $packPrice : $bribePercent; // вдруг ввели скидку больше, чем нужно представить
            $bribe = $pupilCount * $bribePercent;


            $totalCost = $orderCost - $transferCost;
            //$moneyToCash = $orderCost + $foodPackCost + $teacherPackCost - $bribe;
            $moneyToCash = $totalCost - $bribe;

            /*$bribePercent = $bribePercent >= 1 ? $bribePercent / 100 : $bribePercent;

            $bribe = $totalCost * $bribePercent;*/




            $result = array(
                "totalCost" => $totalCost + $discount,
                "totalCostDiscount" => $totalCost,
                "moneyToCash" => $moneyToCash,
                //"driverCost" => $driverCost,
                "foodCost" => $foodPackCost + $teacherPackCost,
                "orderCost" => $orderCost,
                "packCost" => $packCost,
                "packPrice" => $packPrice,
                "transferCost" => $transferCost,
                //"transferToCash" => $transferToCash,
                "bribe" => $bribe
            );
            $response["result"] = $result;


            if ($action == "schoolGetCostSave") {

                if ($_REQUEST["order_id"] == "" ){
                    $url = "https://script.google.com/macros/s/AKfycbxjyTPPbRdVZ-QJKcWLFyITXIeQ1GwI7fAi0FgATQ0PsoGKAdM/exec";
                    $idData = query("GET", $url, array(
                        "event" => "OnIdIncrementedRequested"
                    ));

                    $id = $idData["result"];
                } else {
                    $id = $_REQUEST["order_id"];
                }
                log_debug(var_export($_REQUEST, true));

                $order = array();
                $order["Id"] = $id;
                $order["Status"] = "Заказ подтвержден";
                $order["DealId"] = $_REQUEST["deal_id"];
                $order["ContactId"] = $_REQUEST["contact_id"];
                $order["CompanyId"] = $_REQUEST["company_id"];
                //--------------------------------------------
                $order["ClientName"] = $_REQUEST["contact_name"];
                $order["KidName"] = $_REQUEST["company_name"];
                $order["Phone"] = $_REQUEST["contact_phone"];
                $order["Center"] = $centerName;

                $datetime = BitrixHelper::constructDatetime($_REQUEST["date"], $_REQUEST["time"]);
                $ts = BitrixHelper::constructTimestamp($_REQUEST["date"], $_REQUEST["time"]);

                $order["ts"] = $ts;
                $order["DateOfEvent"] = $datetime; //sourceOrder["DateOfEvent"];
                $time = strtotime($datetime);
                $time = $time - (3 * 3600);


                $order["Date"] = str_replace(" ", "T", formatDate($datetime, "Y-m-d H:i:s+06:00"));
                $order["DateAtom"] = str_replace(" ", "T", formatDate($datetime, "Y-m-d H:i:s+06:00"));

                $order["TotalCost"] = $moneyToCash;
                $order["UserId"] = $_REQUEST["user_id"];
                $order["User"] = $_REQUEST["user_fullname"];
                $order["FullPriceType"] = isDateHoliday($datetime);
                //------------------------------------
                $event = array(
                    'Event' => "Школа/лагерь",
                    'Zone' => "Без зоны",
                    'Date' => $ts,
                    'StartTime' => str_replace(":", "-", $_REQUEST["time"]),
                    'Duration' => $_REQUEST["duration"],
                    'GuestCount' => $_REQUEST["pupil_count"],
                    'Cost' => $moneyToCash,
                    //------------------------------
                    // дополнительные данные для школ
                    'TeacherCount' => $teacherCount,
                    'Pack' => $_REQUEST["pack"],
                    'PackPrice' => $packPrice,
                    'PupilCount' => $pupilCount,
                    'PupilAge' => $pupilAge,
                    'Subject' => $_REQUEST["subject"],

                    'HasTransfer' => $_REQUEST["has_transfer"],
                    'TransferCost' => $transferCost,

                    'TeacherBribePercent' => $bribePercent,
                    'TeacherBribe' => $bribe,

                    'Comment' => $_REQUEST["comment"],
                    
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
                    'CompanyId' => $_REQUEST["company_id"],
                );
                $order["VerifyInfo"] = $clientInfo;
                //----------------------------
                $paymentDate = formatDate($datetime, "Y-m-d H:i");
                $financeInfo = array (
                    'Id' => $id,
                    'Remainder' =>  0,
                    'Payed' => $moneyToCash,
                    'PaymentsView' => "[".$paymentDate."] Сумма ".$moneyToCash."\n",
                    'Payments' => array(
                        "paymentValue" => $moneyToCash,
                        "receiptDate" => $paymentDate,
                        "receiptNumber" => 1
                    ),
                    'TotalDiscount' => $_REQUEST["discount"],
                    'Increase' => 0,
                    'IncreaseComment' => "",
                    'Discount' => $_REQUEST["discount"],
                    'DiscountComment' => $_REQUEST["discount_comment"],
                    'LoyaltyCode' => "",
                    'LoyaltyDiscount' => 0,
                    'AgentCode' => "",
                    'AgentDiscount' => 0,
                );
                if ($financeInfo["DiscountComment"] == "") $financeInfo["Discount"] = 0;
                $order["FinanceInfo"] = $financeInfo;
                //--------------------------------------------
                $order["BanquetInfo"] = null;
                $order["OptionalInfo"] = null;

                $comment = $_REQUEST["comment"] != "" ? $_REQUEST["comment"]."\n" : "";
                $comment .= "=== Служебная информация ===\n";
                $comment .= "Возраст детей: ".$pupilAge."\n";
                $comment .= "Тема урока: ".$_REQUEST["subject"]."\n";
                $comment .= "Выбранный пакет: ".$packName."\n";
                $comment .= "Процент учителю: ".$bribe."\n";
                $comment .= "Стоимость трансфера: ".$transferCost."\n";

                $order["Comment"] = $comment;
                //--------------------------------
                $order["CreatedAt"] = str_replace(" ", "T", date("Y-m-d H:i:s+06:00", time() + 3600*6));
                $order["UpdatedAt"] = $order["CreatedAt"];
                $order["TaskId"] = null;

                $admin_token = get_access_data(true);

                $contact = BitrixHelper::getContact($_REQUEST["contact_id"], $admin_token);
                $order["ContactSource"] = !is_null($contact) ?  BitrixHelper::getInstanceSource($contact["ID"], "contact", $admin_token) : "Ошибка. Контакт не существует";
                $order["LeadSource"] = !is_null($contact) && !is_null($contact["LEAD_ID"]) ?  BitrixHelper::getInstanceSource($contact["LEAD_ID"], "lead", $admin_token) : "Лид отсутствует";



                if ($_REQUEST["order_id"] != "") {
                    $updateResult = updateOrderDeal($order, $admin_token, true, $_REQUEST["order_id"]);
                    log_debug("updateResult = ".$updateResult."\n".var_export($_REQUEST, true));
                } else {
                    $order["DealId"] = updateOrderDeal($order, $admin_token);
                }
                
                $updateProductsResult = updateDealProductSet($order, $admin_token);
                $response["order"] = $order;
                


                $saveResult = query("POST", $url, array(
                    "event" => "OnOrderSaveRequested",
                    "orderJson" => json_encode($order)
                ));

                $response["saveResult"] = $saveResult;
            }
            break;
        
    }




    header('Content-Type: application/json');
    echo json_encode($response);


    function GetPackPrice($pack, $center){
        $packPrice = 0;


        switch ($center) {
            case 'next_ese':
                $packPrice = ESE_BASE_PACK_PRICE;
                switch ($pack) {
                    case 'basepack':
                        $packPrice = ESE_BASE_PACK_PRICE;
                        break;
                    case 'standartpack':
                        $packPrice = ESE_STD_PACK_PRICE;
                        break;
                    case 'newyear':
                        $packPrice = ESE_NEWYEAR_COST;
                        break;
                    case 'allinclusive':
                        $packPrice = ESE_ALL_PACK_PRICE;
                        break;
                }


                break;
            case 'next_apo':
                switch ($pack) {
                    case 'basepack':
                        $packPrice = APO_BASE_PACK_PRICE;
                        break;
                    case 'standartpack':
                        $packPrice = APO_STD_PACK_PRICE;
                        break;
                    case 'newyear':
                        $packPrice = APO_NEWYEAR_COST;
                        break;
                    case 'allinclusive':
                        $packPrice = APO_ALL_PACK_PRICE;
                        break;
                }
                break;

            case 'next_pro':
                switch ($pack) {
                    case 'basepack':
                        $packPrice = PRO_BASE_PACK_PRICE;
                        break;
                    case 'standartpack':
                        $packPrice = PRO_STD_PACK_PRICE;
                        break;
                    case 'newyear':
                        $packPrice = PRO_NEWYEAR_COST;
                        break;
                    case 'allinclusive':
                        $packPrice = PRO_ALL_PACK_PRICE;
                        break;
                }
                break;
        }
        return $packPrice;
    }


/*
 * order["Id"] = id;
  order["Status"] = "Заказ подтвержден";
  order["DealId"] = sourceOrder["DealId"];
  order["ContactId"] = sourceOrder["ContactId"];
  //--------------------------------------------
  order["ClientName"] = sourceOrder["ClientName"];
  order["KidName"] = sourceOrder["KidName"];
  order["Phone"] = sourceOrder["Phone"];
  order["Center"] = hash[3];
  order["DateOfEvent"] = hash[6]; //sourceOrder["DateOfEvent"];

  order["TotalCost"] = 0;
  order["User"] = sourceOrder["User"];
  order["FullPriceType"] = true;
  //------------------------------------
  var event = {
    'Event' : hash[4],
    'Zone' : hash[5],
    'Date' : hash[6],
    'StartTime' : hash[7],
    'Duration' : hash[8],
    'GuestCount' : hash[9],
    'Cost' : 0
  };
  event["Date"] = FormatTime(event["StartTime"], event["Date"]);
  order["Event"] = event;

  var endDateTime = CunstructEndTime(event["Date"], event["Duration"]);
  order["FullPriceType"] = IsFullPrices(endDateTime);
  //----------------------------
  var clientInfo = {
    'Id' : order["Id"],
    'ClientName' : order["ClientName"],
    'KidName' : order["KidName"],
    'Code' : verifyData["Code"],
    'Status' : verifyData["Status"],
    'Date' : verifyData["Date"],
  };
  order["VerifyInfo"] = clientInfo;
  //-----------------------------
  var financeInfo = {
    'Id' : order["Id"],
    'Remainder' :  0,
    'Payed' : 0,
    'PaymentsView' : "",
    'Payments' : [],
    'TotalDiscount' : 0,
    'Increase' : hash[17] != "" ? hash[17] : 0,
    'IncreaseComment' : hash[18],
    'Discount' : hash[14] != "" ? hash[14] : 0,
    'DiscountComment' : hash[15],
    'LoyaltyCode' : hash[12],
    'LoyaltyDiscount' : 0,
    'AgentCode' : hash[13],
    'AgentDiscount' : 0,
  };
  if (financeInfo["IncreaseComment"] == "") financeInfo["Increase"] = 0;
  if (financeInfo["DiscountComment"] == "") financeInfo["Discount"] = 0;

  order["FinanceInfo"] = financeInfo;
  //----------------------------
  var banquetId = hash[10];
  order["BanquetInfo"] = banquetId != "" ? GetBanquetTranscript(banquetId, links) : null;
  var banquetInfo = {
    'BanquetId' : hash[10],
    'Comment' : "",
    'TranscriptStr' : "",
    'Items' : null,
    'Cost' : 0,
    'Cake' : "",
    'Candybar' : "",
    'Pinata' : "",
    'Date' : null
  };

    //-------------------------------
    var optionalId = hash[11];
    order["OptionalInfo"] = optionalId != "" ? GetOptionalTranscript(optionalId, links) : null;

    var optionalInfo = {
      'OptionalId' : hash[11],
      'Comment' : "",
      'TranscriptStr' : "",
      'Items' : null,
      'Cost' : 0,
      'Entertainment' : "",
      'Cameraman' : "",
      'TableBooking' : "",
      'MonitorSaver' : "",
      'Date' : null
    };


    //-------------------------------------
    order["Comment"] = hash[16];
    order["CreatedAt"] = new Date();
    order["UpdatedAt"] = order["CreatedAt"];
    order["TaskId"] = null;
*/