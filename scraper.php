<?php
require 'scraperwiki.php';
require 'scraperwiki/simple_html_dom.php';
######################################
# Basic PHP scraper
######################################


$max = 10062383;
$counter = scraperwiki::get_var('counter', 10000000);

for ($i=0; $i<1000; $i++) {
//while (true) {
    $html = oneline(scraperwiki::scrape("http://www.ukrlp.co.uk/ukrlp/ukrlp_provider.page_pls_provDetails?x=&pn_p_id=$counter&pv_status=VERIFIED&pv_vis_code=L"));
    print "Getting UKPRN $counter\n";
    
    preg_match_all('|<div class="pod_main_body">(.*?<div )class="searchleft">|', $html, $arr);
    $code = (isset($arr[1][0])) ? $arr[1][0] : '';
    
    if ($code != '') {
        preg_match_all('|<div class="provhead">UKPRN: ([0-9]*?)</div>|', $code, $arr);
        $num = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
        
        preg_match_all('|</div>.*?<div class="pt">(.*?)<|', $code, $arr);
        $name = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
        
        preg_match_all('|<div class="tradingname">Trading Name: <span>(.*?)</span></div>|', $code, $arr);
        $trading = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
        
        preg_match_all('|<div class="assoc">Legal Address</div>(.*?)<div|', $code, $arr);
        $legal = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
        
        preg_match_all('|<div class="assoc">Primary contact address</div>(.*?)<div|', $code, $arr);
        $primary = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
        
        $primary = parseAddress($primary);
        $legal= parseAddress($legal);
        
        if (trim($name)!='') {
            print "Saving Inforamtion for UKPRN $counter\n";
            scraperwiki::save_sqlite(
                array('num'),
                array(
                    'num' => "" . clean($num),
                    'name' => clean($name),
                    'trading' => clean($trading),
                    'legal_address' => clean($legal['address']),
                    'legal_phone' => clean($legal['phone']),
                    'legal_fax' => clean($legal['fax']),
                    'legal_email' => clean($legal['email']),
                    'legal_web' => clean($legal['web']),
                    'primary_address' => clean($primary['address']),
                    'primary_phone' => clean($primary['phone']),
                    'primary_fax' => clean($primary['fax']),
                    'primary_email' => clean($primary['email']),
                    'primary_web' => clean($primary['web']),
                    'primary_courses' => clean($primary['courses'])
                )
            );
        }
        scraperwiki::save_var('counter',$counter);
    }
    $counter++;
    
    if ($counter == $max) {
        scraperwiki::save_var('counter', 10000000);
        break;
    }
}

function parseAddress($val) {
    preg_match_all('|<strong>Telephone: </strong>(.*?)<br />|', $val, $arr);
    $dat['phone'] = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
    
    preg_match_all('|<strong>E-mail: </strong><a href="mailto:(.*?)">.*?</a><br />|', $val, $arr);
    $dat['email'] = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
    
    preg_match_all('|<strong>Website: </strong><a target="_blank" href="(.*?)">.*?</a><br />|', $val, $arr);
    $dat['web'] = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
    
    preg_match_all('|<strong>Fax: </strong>(.*?)<br />|', $val, $arr);
    $dat['fax'] = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
    
    preg_match_all('|<strong>Courses: </strong>(.*?)<br />|', $val, $arr);
    $dat['courses'] = (isset($arr[1][0])) ? trim($arr[1][0]) : '';
    
    $p = explode('<strong>',$val);
    $p = explode('<br />',$p[0]);
    
    $dat['address'] = '';
    foreach ($p as $a) {
        $a = trim($a);
        if ($a != '') {
            if ($dat['address'] != '') { $dat['address'] .= ', '; }
            $dat['address'] .= $a;
        }
    }
    
    if ($dat['address'] == 'Not specified. Please use the above.') {
        $dat['address'] = '';
    }
    
    return $dat;

}

function clean($val) {
    $val = str_replace('&nbsp;', ' ', $val);
    $val = str_replace('&amp;', '&', $val);
    $val = html_entity_decode($val);
    $val = strip_tags($val);
    $val = trim($val);
    $val = utf8_decode($val);
    
    return($val);
}

function oneline($code) {
    $code = str_replace("\n", '', $code);
    $code = str_replace("\r", '', $code);
    
    return $code;
}

?>
