<?php

function elasticsearch_installed() {
    // Elastic Search only needs curl,
    // and Moodle already requires it,
    // so it is ok to just return true.
    return true;
}

function elasticsearch_check_server() {
    global $CFG;
    $url = $CFG->elasticsearch_server_hostname.'/?pretty';
    $c = new curl();
    $response = json_decode($c->get($url));
    return $response->status == 200;
}
