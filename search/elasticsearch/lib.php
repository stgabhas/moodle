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

function elasticsearch_add_document($doc) {

    global $CFG;
    $url = $CFG->elasticsearch_server_hostname.'/moodle/'.$doc['id'];

    $jsondoc = json_encode($doc);

    $c = new curl();
    $result = json_decode($c->post($url, $jsondoc));

    return $result->created == true;
}

function elasticsearch_commit() {
}

function elasticsearch_optimize() {
}
