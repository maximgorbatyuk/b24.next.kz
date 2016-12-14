<?php

    require($_SERVER["DOCUMENT_ROOT"]."/include/helper.php");

    $eventToken = $_REQUEST["event_token"];
    $properties = $_REQUEST["properties"];

    $authToken = $_REQUEST["auth"]["access_token"];

    $returnParams = array(
        "EVENT_TOKEN" => $eventToken,
        "auth" => $authToken,
    );

    switch ($_REQUEST["code"]){
        case "calendar_accessibility":
            /*
            "RETURN_PROPERTIES" => array(
                "total" => array(
                    "NAME" => "Количество событий в периоде",
                    "TYPE" => "int",
                    "Multiple" => "N"
                ),
                "events" => array(
                    "NAME" => "Массив строк (title, datetimeStart, datetimeEnd)",
                    "TYPE" => "string",
                    "Multiple" => "Y"
                ),
            )*/
            $startDate = strtotime($properties["startDate"]);
            $endDate = strtotime($properties["endDate"]);
            $calendarType = $properties["calendarType"];
            $sectionId = $properties["sectionId"];
            $userId = $properties["userId"];

            $startAtom = str_replace(" ", "T", date("Y-m-d H:m:s+06:00", $startDate)); // Y-m-d H:m:s+06:00
            $sendAtom = str_replace(" ", "T", date("Y-m-d H:m:s+06:00", $endDate));

            $calendarEvents = BitrixHelper::callMethod("calendar.event.get", array(
                "type" => $calendarType,
                "ownerId" => $userId,
                "from" => $startAtom,
                "to" => $sendAtom,
                "section" => array(
                    0 => $sectionId
                ),
                "auth" => $authToken,
            ));
            log_debug(var_export($calendarEvents, true));
            $total = count($calendarEvents["result"]);
            $titleArray = array();
            if ($total > 0){

                $events = $calendarEvents["result"];
                foreach ($events as $event){
                    $title = $event["NAME"]." (".$event["DATE_FROM"]." - ".$event["DATE_TO"].". ID ".$event["ID"].")";
                    $titleArray[] = $title;
                }
            }
            $returnParams["RETURN_VALUES"] = array(
                "total" => $total,
                "events" => $titleArray,
            );

            break;

        default:
            $returnParams = null;
    }
    $response = false;

    if (!is_null($returnParams)){
        $returnResult = BitrixHelper::callMethod("bizproc.event.send", $returnParams);

        $response = true;

    } else {

    }

    $logText = "Ответ в бизнес-активити: code=".$_REQUEST["code"]. ". Ответ: ".$response;
    log_event($logText);
    echo $response;
    //-------------------

