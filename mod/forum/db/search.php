<?php

/**
 * Global Search API
 * @package mod_forum
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function forum_search_iterator($from = 0) {
    global $DB;

    $sql = "SELECT id, modified FROM {forum_posts} WHERE modified > ? ORDER BY modified ASC";

    return $DB->get_recordset_sql($sql, array($from));
}

function forum_search_get_documents($id) {
    global $DB, $CFG;

    $docs = array();
    try {
        if ($post = forum_get_post_full($id)) {
            $forum = $DB->get_record('forum', array('id' => $post->forum), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('forum', $forum->id, $forum->course);
            $context = context_module::instance($cm->id);
            $user = $DB->get_record('user', array('id' => $post->userid));
        } else {
            return $docs;
        }
    } catch (mdml_missing_record_exception $ex) {
        return $docs;
    }

    $contextlink = '/mod/forum/discuss.php?d=' . $post->discussion . '#p' . $post->id;
    $modulelink = '/mod/forum/view.php?id=' . $cm->id;

    // Declare a new Solr Document and insert fields into it from DB
    $doc = new SolrInputDocument();
    $doc->addField('type', SEARCH_TYPE_HTML);
    $doc->addField('id', 'forum_' . $post->id);
    $doc->addField('user', $user->firstname . ' ' . $user->lastname);
    $doc->addField('created', gmdate('Y-m-d\TH:i:s\Z', $post->created));
    $doc->addField('modified', gmdate('Y-m-d\TH:i:s\Z', $post->modified));
    $doc->addField('intro', strip_tags($forum->intro));
    $doc->addField('name', $forum->name);
    $doc->addField('title', $post->subject);
    $doc->addField('content', strip_tags($post->message));
    $doc->addField('courseid', $forum->course);
    $doc->addField('contextlink', $contextlink);
    $doc->addField('modulelink', $modulelink);
    $doc->addField('module', 'forum');
    $docs[] = $doc;

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_forum', 'attachment', $id, "timemodified", false);

    $numfile = 1;
    foreach ($files as $file) {
        if (strpos($mime = $file->get_mimetype(), 'image') === false) {
            $filename = urlencode($file->get_filename());
            $directlink = '/pluginfile.php/' . $context->id . '/mod_forum/attachment/' . $id . '/' . $filename;
            $url = 'literal.id=' . 'forum_' . $id . '_file_' . $numfile . '&literal.modulelink=' . $modulelink .
                    '&literal.module=forum&literal.type=3' . '&literal.directlink=' . $directlink .
                    '&literal.courseid=' . $forum->course . '&literal.contextlink=' . $contextlink;

            $index_file_function = $CFG->search_engine . '_post_file';
            $index_file_function($file, $url);
            $numfile++;
        }
    }

    return $docs;
}

function forum_search_access($id) {
    global $DB, $USER;

    try {
        $post = $DB->get_record('forum_posts', array('id' => $id), '*', MUST_EXIST);
        $discussion = $DB->get_record('forum_discussions', array('id' => $post->discussion), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $forum->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('forum', $forum->id, $course->id, false, MUST_EXIST);
    } catch (dml_missing_record_exception $ex) {
        return SEARCH_ACCESS_DELETED;
    }

    $context = context_module::instance($cm->id);

    if ($discussion->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {
        if (!groups_group_exists($discussion->groupid)) {
            return SEARCH_ACCESS_DENIED;
        }

        if (!groups_is_member($discussion->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
            return SEARCH_ACCESS_DENIED;
        }
    }

    if (!forum_user_can_see_post($forum, $discussion, $post, $USER, $cm)) {
        return SEARCH_ACCESS_DENIED;
    }

    return SEARCH_ACCESS_GRANTED;
}
