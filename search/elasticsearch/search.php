<?php

function elasticsearch_execute_query($data) {

    $search = array('query' => array('bool' => array('must' => array(array('match' => array('content' => $data->queryfield))))));

    if (!empty($data->titlefilterqueryfield)) {
        $search['query']['bool']['must'][] = array('match' => array('title' => $data->titlefilterqueryfield));
    }
    if (!empty($data->authorfilterqueryfield)) {
        $search['query']['bool']['should'][] = array('match' => array('author' => $data->authorfilterqueryfield));
        $search['query']['bool']['should'][] = array('match' => array('user' => $data->authorfilterqueryfield));
    }
    if (!empty($data->modulefilterqueryfield)) {
        $search['query']['bool']['must'][] = array('match' => array('module' => $data->modulefilterqueryfield));
    }

    return elasticsearch_make_request($search);
}

function elasticsearch_make_request($search) {
    global $CFG;

    $url = $CFG->elasticsearch_server_hostname.'/moodle/_search?pretty';

    $c = new curl();
    $results = json_decode($c->post($url, json_encode($search)));
    $docs = array();
    if (isset($results->hits))  {
        $numgranted = 0;
        foreach ($results->hits->hits as $r) {
            $sourceid = explode('_', $r->_source->id);
            $modname = $sourceid[0];
            $modgssupport = 'gs_support_' . $modname;
            if (!empty($CFG->$modgssupport)) {
                include_once($CFG->dirroot.'/mod/'.$modname.'/db/search.php');
                $access_func = $modname . '_search_access';
                $acc = $access_func($sourceid[1]);
                switch ($acc) {
                    case SEARCH_ACCESS_DELETED:
                        search_delete_index_by_id($value->id);
                        break;
                    case SEARCH_ACCESS_DENIED:
                        break;
                    case SEARCH_ACCESS_GRANTED:
                        if (!isset($r->_source->author)) {
                            $r->_source->author = array($r->_source->user);
                        }
                        $docs[] = $r->_source;
                        $numgranted++;
                        break;
                }
            }
        }
    } else {
        if (!$results) {
            return false;
        }
        return $results->error;
    }
    return $docs;
}

function elasticsearch_get_more_like_this_text($text) {

    $search = array('query' =>
                        array('more_like_this' =>
                                  array('fields' => array('content'),
                                        'like_text' => $text,
                                        'min_term_freq' => 1,
                                        'max_query_terms' => 12)));
    return elasticsearch_make_request($search);
}

function elasticsearch_delete_by_query($query) {
}
