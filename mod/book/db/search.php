<?php

/**
 * Global Search API
 * @package mod_book
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function book_search_iterator($from = 0) {
    global $DB;

    $sql = "SELECT id, timemodified AS modified FROM {book_chapters} WHERE timemodified > ? ORDER BY timemodified ASC";

    return $DB->get_recordset_sql($sql, array($from));
}

function book_search_get_documents($id) {
    global $DB;

    $docs = array();
    try {
        $chapter = $DB->get_record('book_chapters', array('id' => $id), '*', MUST_EXIST);
        $book = $DB->get_record('book', array('id' => $chapter->bookid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('book', $book->id, $book->course, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
    } catch (mdml_missing_record_exception $ex) {
        return $docs;
    }

    // Declare a new Solr Document and insert fields into it from DB
    $doc = new SolrInputDocument();
    $doc->addField('id', 'book_' . $chapter->id);
    $doc->addField('created', gmdate('Y-m-d\TH:i:s\Z', $chapter->timecreated));
    $doc->addField('modified', gmdate('Y-m-d\TH:i:s\Z', $chapter->timemodified));
    $doc->addField('name', $book->name);
    $doc->addField('intro', strip_tags($book->intro));
    $doc->addField('title', $chapter->title);
    $doc->addField('content', strip_tags($chapter->content));
    $doc->addField('type', SEARCH_TYPE_HTML);
    $doc->addField('courseid', $book->course);
    $doc->addField('contextlink', '/mod/book/view.php?id=' . $cm->id .'&chapterid=' . $book->id);
    $doc->addField('modulelink', '/mod/book/view.php?id=' . $cm->id);
    $doc->addField('module', 'book');
    $docs[] = $doc;

    return $docs;
}

function book_search_access($id) {
    global $DB;
    try {
        $chapter = $DB->get_record('book_chapters', array('id'=>$id), '*', MUST_EXIST);
        $book = $DB->get_record('book', array('id'=>$chapter->bookid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('book', $book->id, $book->course, MUST_EXIST);
        $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    } catch (dml_missing_record_exception $ex) {
        return SEARCH_ACCESS_DELETED;
    }

    try {
        $context = context_module::instance($cm->id);
        require_capability('mod/book:read', $context);
    } catch (moodle_exception $ex) {
        return SEARCH_ACCESS_DENIED;
    }

    return SEARCH_ACCESS_GRANTED;
}
