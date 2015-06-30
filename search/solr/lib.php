<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Solr Wraper class
 * Listing down the exposed functions of SolrClient class to be used here in Global Search
 *
 * @package    Global Search
 * @subpackage solr
 * @copyright  Prateek Sachan {@link http://prateeksachan.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/lib.php');  // needed for course search

class global_search_engine {
    private $client;

    public function __construct(SolrClient $object) {
        return $this->client = $object;
    }

    public function ping() {
        return $this->client->ping();
    }

    public function add_document(SolrInputDocument $doc) {
        return $this->client->addDocument($doc);
    }

    public function commit() {
        return $this->client->commit();
    }

    public function optimize() {
        return $this->client->optimize();
    }

    public function query(SolrParams $query) {
        return $this->client->query($query);
    }

    public function delete_by_id($id) {
        return $this->client->deleteById($id);
    }

    public function delete_by_query($query) {
        return $this->client->deleteByQuery($query);
    }
}

function solr_add_document($doc) {
    $solrdoc = new SolrInputDocument();
    foreach ($doc as $field => $value) {
        $doc->addField($field, $value);
    }
    $client = solr_get_search_client();
    return $this->client->addDocument($doc);
}

function solr_commit() {
    $client = solr_get_search_client();
    return $this->client->commit();
}

function solr_optimize() {
    $client = solr_get_search_client();
    return $this->client->optimize();
}

function solr_delete_by_id($id) {
    $client = solr_get_search_client();
    return $this->client->deleteById($id);
}

function solr_delete_by_query($query) {
    $client = solr_get_search_client();
    return $this->client->deleteByQuery($query);
}

function solr_check_server() {
    try {
        $client = solr_get_search_client();
        $client->ping();
        return 1;
    } catch (SolrClientException $ex) {
        return 0;
    }
}

function solr_installed() {
    function_exists('solr_get_version') ? $x = 1 : $x = 0;
    return $x;
}

function solr_get_search_client() {
    global $CFG;

    if (function_exists('solr_get_version')) {
        // Solr connection options.
        $options = array(
            'hostname' => isset($CFG->solr_server_hostname) ? $CFG->solr_server_hostname : '',
            'login'    => isset($CFG->solr_server_username) ? $CFG->solr_server_username : '',
            'password' => isset($CFG->solr_server_password) ? $CFG->solr_server_password : '',
            'port'     => isset($CFG->solr_server_port) ? $CFG->solr_server_port : '',
            'issecure' => isset($CFG->solr_secure) ? $CFG->solr_secure : '',
            'ssl_cert' => isset($CFG->solr_ssl_cert) ? $CFG->solr_ssl_cert : '',
            'ssl_cert_only' => isset($CFG->solr_ssl_cert_only) ? $CFG->solr_ssl_cert_only : '',
            'ssl_key' => isset($CFG->solr_ssl_key) ? $CFG->solr_ssl_key : '',
            'ssl_password' => isset($CFG->solr_ssl_keypassword) ? $CFG->solr_ssl_keypassword : '',
            'ssl_cainfo' => isset($CFG->solr_ssl_cainfo) ? $CFG->solr_ssl_cainfo : '',
            'ssl_capath' => isset($CFG->solr_ssl_capath) ? $CFG->solr_ssl_capath : '',
            'path' => isset($path) ? $path : '', // a way to use more than one collection/core
        );

        // If php solr extension 1.0.3-alpha installed, one may choose 3.x or 4.x solr from admin settings page.
        $object = new SolrClient($options);
        return new global_search_engine($object);
    }
    return null;
}
