<?php

function elasticsearch_execute_query($data) {
    global $CFG;

    $url = $CFG->elasticsearch_server_hostname.'/moodle/_search?pretty';

    $jsonsearch =
    '{
          "query": { "match": { "content": "'.$data->queryfield.'" } }
    }';

    $c = new curl();
    $results = json_decode($c->post($url, $jsonsearch));
    $docs = array();
    if ($results && $results->hits->total)  {
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
                        $docs[] = $r->_source;
                        $numgranted++;
                        break;
                }
            }
        }
    }
    return $docs;
}
