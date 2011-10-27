<?php
class Zend_View_Helper_PrettyDate {
    function prettyDate($time) {
        $dateObject = new Zend_Date($time);
        $date = intval($dateObject->get(Zend_Date::TIMESTAMP));
        $diff = time() - $date;
        $day_diff = floor($diff / 86400);
                
        if ( is_nan($day_diff) || $day_diff < 0 || $day_diff >= 31 )
            return $time;
            
        if ($diff < 60) {
            return "just now";
        } else if ($diff < 120) {
            return "1 minute ago";
        } else if ($diff < 3600) {
            return floor($diff / 60)." minutes ago";
        } else if ($diff < 7200) {
            return "1 hour ago";
        } else if ($diff < 86400) {
            return floor($diff / 3600)." hours ago";
        } else if ($day_diff == 1) {
            return "Yesterday";
        } else if ($day_diff < 7) {
            return $day_diff." days ago";
        } else if ($day_diff < 31) {
            return ceil($day_diff / 7)." weeks ago";
        } else {
            return $time;
        }
    }
}