<?php
/**
 * Description of MailQueue
 *
 * @author abdirahmanhassan
 */
class MailQueue {

    function __construct() {
        
    }

    public static function addToQueue($source, $receipient, $attachment, $labels, $values) {
        $lbs = implode(",", $labels);
        $vls = implode(",", $values);
        
        $db = new Database();
        
        $data = array(
            'source'=>$source,
            'recipient'=>$receipient,
            'attachment'=>$attachment,
            'e_labels'=>$lbs,
            'e_values'=>$vls
        );
        
        $result = $db->save("mx_email_queue", $data);
   
        if ($result) {            
            //self::sendQueued();
            return array('status' => 200);
        } else {
            return array('status' => 100);
            
        }
    }

    function sendQueued() {
        // Linux Version       
        $command = 'php ../../api/mabrex_mail_processor.php&';
        $handler = popen($command, "r");
        pclose($handler);

        // Windows Version
//        $command = "start php ../../api/mabrex_mail_processor.php";
//        $handler = popen($command, "r");
//        pclose($handler);
    } 

    function addToQueueBulk($source, $receipient, $attachment, $labels, $values) {
        $lbs = implode(",", $labels);
        $vls = implode(",", $values);
              $db = new Database();
        
        $result = $db->save("mx_email_queue", array(
            'source'=>$source,
            'recipient'=>$receipient,
            'attachment'=>$attachment,
            'e_labels'=>$lbs,
            'e_values'=>$vls
        ));
        
        if ($result) {
            return array('status' => 200);
        } else {
            return array('status' => 100);
        }
    }
}
//include '../inc/config.php';
//include 'Database.php';
//$mq = new MailQueue();
//
//print_r( $mq->addToQueue(2, "bagashy@gmail.com", null, array('jina','simu'), array('Ali','255776936000')));