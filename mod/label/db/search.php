<?php

/**
 * Global Search API
 * @package mod_label
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function label_search_iterator($from = 0) {
    global $DB;

    $sql = "SELECT id, timemodified AS modified FROM {label} WHERE timemodified > ? ORDER BY timemodified ASC";

    return $DB->get_recordset_sql($sql, array($from));
}

function label_search_get_documents($id) {
    global $DB;

    $docs = array();
    try {
        $label = $DB->get_record('label', array('id' => $id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('label', $label->id, $label->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
    } catch (mdml_missing_record_exception $ex) {
        return $docs;
    }

    // Declare a new Solr Document and insert fields into it from DB
    $doc = new SolrInputDocument();
    $doc->addField('type', SEARCH_TYPE_HTML);
    $doc->addField('id', 'label_' . $label->id);
    $doc->addField('modified', gmdate('Y-m-d\TH:i:s\Z', $label->timemodified));
    $doc->addField('intro', strip_tags($label->intro));
    $doc->addField('name', $label->name);
    $doc->addField('courseid', $label->course);
    $doc->addField('contextlink', '/course/view.php?id=' . $label->course);
    $doc->addField('modulelink', '/course/view.php?id=' . $label->course);
    $doc->addField('module', 'label');
    $docs[] = $doc;

    return $docs;
}

function label_search_access($id) {
    global $DB;
    try {
        $label = $DB->get_record('label', array('id'=>$id), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('label', $label->id, $label->course, MUST_EXIST);
        $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    } catch (dml_missing_record_exception $ex) {
        return SEARCH_ACCESS_DELETED;
    }

    $context = context_course::instance($label->course);
    if (!is_enrolled($context) and !is_viewing($context)) {
        return SEARCH_ACCESS_DENIED;
    }

    return SEARCH_ACCESS_GRANTED;
}
