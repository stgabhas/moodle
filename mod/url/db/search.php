<?php

/**
 * Global Search API
 * @package mod_url
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function url_search_iterator($from = 0) {
    global $DB;

    $sql = "SELECT id, timemodified AS modified FROM {url} WHERE timemodified > ? ORDER BY timemodified ASC";

    return $DB->get_recordset_sql($sql, array($from));
}

function url_search_get_documents($id) {
    global $DB;

    $docs = array();
    try {
        $url = $DB->get_record('url', array('id' => $id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('url', $url->id, $url->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
    } catch (mdml_missing_record_exception $ex) {
        return $docs;
    }

    // Declare a new Solr Document and insert fields into it from DB
    $doc = new SolrInputDocument();
    $doc->addField('type', SEARCH_TYPE_HTML);
    $doc->addField('id', 'url_' . $url->id);
    $doc->addField('modified', gmdate('Y-m-d\TH:i:s\Z', $url->timemodified));
    $doc->addField('intro', strip_tags($url->intro));
    $doc->addField('name', $url->name);
    $doc->addField('content', $url->externalurl);
    $doc->addField('courseid', $url->course);
    $doc->addField('contextlink', '/mod/url/view.php?id=' . $cm->id);
    $doc->addField('modulelink', '/mod/url/view.php?id=' . $cm->id);
    $doc->addField('module', 'url');
    $docs[] = $doc;

    return $docs;
}

function url_search_access($id) {
    global $DB;
    try {
        $url = $DB->get_record('url', array('id'=>$id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('url', $url->id, $url->course, MUST_EXIST);
        $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    } catch (dml_missing_record_exception $ex) {
        return SEARCH_ACCESS_DELETED;
    }

    if (!can_access_course($course, null, '', true)) {
        echo 'no';
        return SEARCH_ACCESS_DENIED;
    }

    try {
        $context = context_module::instance($cm->id);
        require_capability('mod/url:view', $context);
    } catch (moodle_exception $ex) {
        return SEARCH_ACCESS_DENIED;
    }

    return SEARCH_ACCESS_GRANTED;
}
