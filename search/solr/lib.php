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

function solr_check_server(global_search_engine $client) {
    try {
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
            'hostname' => isset($CFG->SOLR_SERVER_HOSTNAME) ? $CFG->SOLR_SERVER_HOSTNAME : '',
            'login'    => isset($CFG->SOLR_SERVER_USERNAME) ? $CFG->SOLR_SERVER_USERNAME : '',
            'password' => isset($CFG->SOLR_SERVER_PASSWORD) ? $CFG->SOLR_SERVER_PASSWORD : '',
            'port'     => isset($CFG->SOLR_SERVER_PORT) ? $CFG->SOLR_SERVER_PORT : '',
            'issecure' => isset($CFG->SOLR_SECURE) ? $CFG->SOLR_SECURE : '',
            'ssl_cert' => isset($CFG->SOLR_SSL_CERT) ? $CFG->SOLR_SSL_CERT : '',
            'ssl_cert_only' => isset($CFG->SOLR_SSL_CERT_ONLY) ? $CFG->SOLR_SSL_CERT_ONLY : '',
            'ssl_key' => isset($CFG->SOLR_SSL_KEY) ? $CFG->SOLR_SSL_KEY : '',
            'ssl_password' => isset($CFG->SOLR_SSL_KEYPASSWORD) ? $CFG->SOLR_SSL_KEYPASSWORD : '',
            'ssl_cainfo' => isset($CFG->SOLR_SSL_CAINFO) ? $CFG->SOLR_SSL_CAINFO : '',
            'ssl_capath' => isset($CFG->SOLR_SSL_CAPATH) ? $CFG->SOLR_SSL_CAPATH : '',
            'path' => isset($path) ? $path : '', // a way to use more than one collection/core
        );

        // If php solr extension 1.0.3-alpha installed, one may choose 3.x or 4.x solr from admin settings page.
        $object = new SolrClient($options);
        return new global_search_engine($object);
    }
    return null;
}
