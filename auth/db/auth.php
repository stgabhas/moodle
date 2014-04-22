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
 * Authentication Plugin: External Database Authentication
 *
 * Checks against an external database.
 *
 * @package    auth_db
 * @author     Martin Dougiamas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * External database authentication plugin.
 */
class auth_plugin_db extends auth_plugin_base {

    /**
     * Constructor.
     */
    function __construct() {
        global $CFG;
        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        $this->authtype = 'db';
        $this->config = get_config('auth/db');
        if (empty($this->config->extencoding)) {
            $this->config->extencoding = 'utf-8';
        }
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login($username, $password) {
        global $CFG, $DB;

        $extusername = core_text::convert($username, 'utf-8', $this->config->extencoding);
        $extpassword = core_text::convert($password, 'utf-8', $this->config->extencoding);

        if ($this->is_internal()) {
            // Lookup username externally, but resolve
            // password locally -- to support backend that
            // don't track passwords.

            if (isset($this->config->removeuser) and $this->config->removeuser == AUTH_REMOVEUSER_KEEP) {
                // No need to connect to external database in this case because users are never removed and we verify password locally.
                if ($user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id, 'auth'=>$this->authtype))) {
                    return validate_internal_user_password($user, $password);
                } else {
                    return false;
                }
            }

            $authdb = $this->db_init();

            $rs = $authdb->Execute("SELECT *
                                      FROM {$this->config->table}
                                     WHERE {$this->config->fielduser} = '".$this->ext_addslashes($extusername)."'");
            if (!$rs) {
                $authdb->Close();
                debugging(get_string('cannotconnect','auth_db'));
                return false;
            }

            if (!$rs->EOF) {
                $rs->Close();
                $authdb->Close();
                // User exists externally - check username/password internally.
                if ($user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id, 'auth'=>$this->authtype))) {
                    return validate_internal_user_password($user, $password);
                }
            } else {
                $rs->Close();
                $authdb->Close();
                // User does not exist externally.
                return false;
            }

        } else {
            // Normal case: use external db for both usernames and passwords.

            $authdb = $this->db_init();

            if ($this->config->passtype === 'md5') {   // Re-format password accordingly.
                $extpassword = md5($extpassword);
            } else if ($this->config->passtype === 'sha1') {
                $extpassword = sha1($extpassword);
            }

            $rs = $authdb->Execute("SELECT *
                                      FROM {$this->config->table}
                                     WHERE {$this->config->fielduser} = '".$this->ext_addslashes($extusername)."'
                                           AND {$this->config->fieldpass} = '".$this->ext_addslashes($extpassword)."'");
            if (!$rs) {
                $authdb->Close();
                debugging(get_string('cannotconnect','auth_db'));
                return false;
            }

            if (!$rs->EOF) {
                $rs->Close();
                $authdb->Close();
                return true;
            } else {
                $rs->Close();
                $authdb->Close();
                return false;
            }

        }
    }

    /**
     * Connect to external database.
     *
     * @return ADOConnection
     */
    function db_init() {
        // Connect to the external database (forcing new connection).
        $authdb = ADONewConnection($this->config->type);
        if (!empty($this->config->debugauthdb)) {
            $authdb->debug = true;
            ob_start(); //Start output buffer to allow later use of the page headers.
        }
        $authdb->Connect($this->config->host, $this->config->user, $this->config->pass, $this->config->name, true);
        $authdb->SetFetchMode(ADODB_FETCH_ASSOC);
        if (!empty($this->config->setupsql)) {
            $authdb->Execute($this->config->setupsql);
        }

        return $authdb;
    }

    /**
     * Returns user attribute mappings between moodle and ldap.
     *
     * @return array
     */
    function db_attributes() {
        $moodleattributes = array();
        foreach ($this->userfields as $field) {
            if (!empty($this->config->{"field_map_$field"})) {
                $moodleattributes[$field] = $this->config->{"field_map_$field"};
            }
        }
        $moodleattributes['username'] = $this->config->fielduser;
        return $moodleattributes;
    }

    /**
     * Reads any other information for a user from external database,
     * then returns it in an array.
     *
     * @param string $username
     * @return array
     */
    function get_userinfo($username) {
        global $CFG;

        $extusername = core_text::convert($username, 'utf-8', $this->config->extencoding);

        $authdb = $this->db_init();

        // Array to map local fieldnames we want, to external fieldnames.
        $selectfields = $this->db_attributes();

        $result = array();
        // If at least one field is mapped from external db, get that mapped data.
        if ($selectfields) {
            $select = array();
            foreach ($selectfields as $localname=>$externalname) {
                $select[] = "$externalname AS $localname";
            }
            $select = implode(', ', $select);
            $sql = "SELECT $select
                      FROM {$this->config->table}
                     WHERE {$this->config->fielduser} = '".$this->ext_addslashes($extusername)."'";
            if ($rs = $authdb->Execute($sql)) {
                if (!$rs->EOF) {
                    $fields_obj = $rs->FetchObj();
                    $fields_obj = (object)array_change_key_case((array)$fields_obj , CASE_LOWER);
                    foreach ($selectfields as $localname=>$externalname) {
                        $result[$localname] = core_text::convert($fields_obj->{$localname}, $this->config->extencoding, 'utf-8');
                     }
                 }
                 $rs->Close();
            }
        }
        $authdb->Close();
        return $result;
    }

    /**
     * Change a user's password.
     *
     * @param  stdClass  $user      User table object
     * @param  string  $newpassword Plaintext password
     * @return bool                 True on success
     */
    function user_update_password($user, $newpassword) {
        global $DB;

        if ($this->is_internal()) {
            $puser = $DB->get_record('user', array('id'=>$user->id), '*', MUST_EXIST);
            // This will also update the stored hash to the latest algorithm
            // if the existing hash is using an out-of-date algorithm (or the
            // legacy md5 algorithm).
            if (update_internal_user_password($puser, $newpassword)) {
                $user->password = $puser->password;
                return true;
            } else {
                return false;
            }
        } else {
            // We should have never been called!
            return false;
        }
    }

    /**
     * Synchronizes user from external db to moodle user table.
     *
     * Sync should be done by using idnumber attribute, not username.
     * You need to pass firstsync parameter to function to fill in
     * idnumbers if they don't exists in moodle user table.
     *
     * Syncing users removes (disables) users that don't exists anymore in external db.
     * Creates new users and updates coursecreator status of users.
     *
     * This implementation is simpler but less scalable than the one found in the LDAP module.
     *
     * @param progress_trace $trace
     * @param bool $doupdates  Optional: set to true to force an update of existing accounts
     * @return int 0 means success, 1 means failure
     */
    public function sync_users(progress_trace $trace, $doupdates=false) {
        global $CFG, $DB;

        // First, define the temporary table to be used.
        $trace->output('creating temporary table');

        $table = new xmldb_table('tmp_extuser');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('mnethostid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('username', XMLDB_INDEX_UNIQUE, array('mnethostid', 'username'));

        $fieldsselect = $this->config->fielduser;
        $fieldsupdate = '';
        $fieldsadd = 'e.id, e.username, e.mnethostid';
        if ($doupdates) {

            $sqlfields = array();
            $addarray = array();
            $selarray = array();
            $maparray = array();

            $allkeys = array_keys(get_object_vars($this->config));
            foreach ($allkeys as $key) {
                if (preg_match('/^field_map_(.+)$/', $key, $match)) {
                    if (!empty($this->config->{$key})) {
                        $selarray[] = $this->config->{$key};
                        $addarray[] = "e.{$match[1]}";
                        $uparray[]  = " u.{$match[1]} = t.{$match[1]}";
                        $maparray[$match[1]] = $this->config->{$key};
                    }
                }
            }

            if (!empty($maparray)) {
                foreach ($maparray as $mdlfield => $extfield) {
                    $table->add_field($mdlfield, XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
                }
                $fieldsselect .= ', '.implode($selarray, ',');
                $fieldsadd    .= ','.implode($addarray, ',');
                $fieldsupdate  = implode($uparray, ',');
            }
            unset($allkeys, $key, $addarray, $uparray, $selarray);
        }

        $dbman->create_temp_table($table);

        // Get external users.
        if ($userlist = $externaldbconnection->Execute("SELECT {$fieldsselect} FROM {$this->config->table}")) {

            foreach ($userlist as $u) {
                $data = new stdclass();
                $data->username = $u['username'];
                $data->mnethostid = $CFG->mnet_localhost_id;
                foreach ($maparray as $mdlfield => $extfield) {
                    $data->{$mdlfield} = $u[$extfield];
                }
                if (!isset($data->lastname) || empty($data->lastname)) {
                    $data->lastname = '';
                }
                try {
                    $DB->insert_record_raw('tmp_extuser', $data, false);
                } catch (Exception $e) {
                    $dbman->drop_table($table);
                    $trace->output($e->getMessage());
                    $trace->finished();
                    return 1;
                }
            }
        } else {
            $dbman->drop_table($table);
            $trace->output('could not get users');
            $trace->finished();
            // Exit so as to avoid mass deletion of users.
            return 1;
        }

        // If the temp table is empty, it probably means that something went wrong.
        // Exit so as to avoid mass deletion of users.
        $count = $DB->count_records_sql('SELECT COUNT(*) AS count FROM {tmp_extuser}');
        if ($count < 1) {
            $dbman->drop_table($table);
            $trace->output('could not get users');
            $trace->finished();
            return 1;
        } else {
            $trace->output('users found: '.$count);
        }
        $trace->output("done importing users from external database. will begin sync.");

        // Delete obsolete internal users.
        if (!empty($this->config->removeuser) and $this->config->removeuser !== AUTH_REMOVEUSER_KEEP) {

            $sql = 'SELECT u.id, u.username, u.email, u.auth
                      FROM {user} u
                 LEFT JOIN {tmp_extuser} e
                        ON (u.username = e.username AND
                            u.mnethostid = e.mnethostid)
                     WHERE u.auth = ?
                       AND u.deleted = 0
                       AND e.username IS NULL';
            $removeusers = $DB->get_records_sql($sql, array($this->authtype));

            if (!empty($removeusers)) {
                require_once($CFG->dirroot.'/user/lib.php');
                $trace->output('users to remove: '. count($removeusers));

                foreach ($removeusers as $user) {
                    if ($this->config->removeuser == AUTH_REMOVEUSER_FULLDELETE) {
                        delete_user($user);
                        $trace->output('deleted user with username: '. $user->username. 'and id: '.$user->id);
                    } else if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                        $updateuser = new stdClass();
                        $updateuser->id   = $user->id;
                        $updateuser->suspended = 1;
                        user_update_user($updateuser, false);
                        $trace->output('suspended user with username:'. $user->username. 'and id: '.$user->id);
                    }
                }
            } else {
                $trace->output('no user entries to remove');
            }
            unset($removeusers);
        }

        // Revive suspended users.
        if (!empty($this->config->removeuser) and $this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
            $sql = "SELECT u.id, u.username
                      FROM {user} u
                      JOIN {tmp_extuser} e
                        ON (u.username = e.username AND
                            u.mnethostid = e.mnethostid)
                     WHERE u.suspended = 1
                       AND u.deleted = 0";
            $reviveusers = $DB->get_records_sql($sql);

            if (!empty($reviveusers)) {
                $trace->output('users to revive: '. count($reviveusers));

                foreach ($reviveusers as $user) {
                    $updateuser = new stdClass();
                    $updateuser->id = $user->id;
                    $updateuser->suspended = 0;
                    $DB->update_record('user', $updateuser);
                    $trace->output('revived user with username:'. $user->username. 'and id:'.$user->id);
                }
            } else {
                $trace->output('no users to revive');
            }

            unset($reviveusers);
        }

        // Update existing accounts.
        if (!empty($fieldsupdate)) {

            $trace->output('updating users');
            $sql = "UPDATE {user} u
                      JOIN {tmp_extuser} t
                        ON t.username = u.username
                       SET {$fieldsupdate}
                     WHERE u.deleted = 0
                       AND u.auth = :authtype
                       AND u.mnethostid = :mnethostid ";
            $DB->execute($sql, array('authtype' => $this->authtype, 'mnethostid' => $CFG->mnet_localhost_id));
            $trace->output('users updated');

        } else {
            $trace->output('no users to update');
        }

        // Create missing accounts.
        $suspendselect = "";
        if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
            $suspendselect = "AND u.suspended = 0";
        }
        $sql = "SELECT {$fieldsadd}
                  FROM {tmp_extuser} e
             LEFT JOIN {user} u
                    ON (u.username = e.username AND
                        u.auth = :authtype AND
                        u.mnethostid = :mnethostid {$suspendselect})
                 WHERE u.id IS NULL";

        $addusers = $DB->get_records_sql($sql, array('authtype' => $this->authtype, 'mnethostid' => $CFG->mnet_localhost_id));

        if (!empty($addusers)) {
            $trace->output('users to add: '. count($addusers));
            // Do not use transactions around this foreach, we want to skip problematic users, not revert everything.
            foreach ($addusers as $user) {
                $username = $user->username;
                if ($this->config->removeuser == AUTH_REMOVEUSER_SUSPEND) {
                    if ($olduser = $DB->get_record('user', array('username' => $username, 'deleted' => 0,
                                                                 'suspended' => 1, 'mnethostid' => $CFG->mnet_localhost_id,
                                                                 'auth' => $this->authtype))) {

                        $DB->set_field('user', 'suspended', 0, array('id' => $olduser->id));
                        $trace->output('unsuspended user with username: '. $username. 'and id: '.$olduser->id);
                        continue;
                    }
                }

                // Do not try to undelete users here, instead select suspending if you ever expect users will reappear.

                // Prep a few params.
                $user->confirmed  = 1;
                $user->auth       = $this->authtype;
                $user->mnethostid = $CFG->mnet_localhost_id;
                if (empty($user->lang)) {
                    $user->lang = $CFG->lang;
                }
                if (empty($user->calendartype)) {
                    $user->calendartype = $CFG->calendartype;
                }
                $user->timecreated = time();
                $user->timemodified = $user->timecreated;
                if ($collision = $DB->get_record_select('user',
                                                        "username = :username AND mnethostid = :mnethostid AND auth <> :auth",
                                                        array('username' => $user->username,
                                                              'mnethostid' => $CFG->mnet_localhost_id,
                                                              'auth' => $this->authtype), 'id,username,auth')) {

                    $trace->output('duplicated user with username: '. $user->username. 'and id: '.$user->id);
                    continue;
                }
                try {
                    $id = $DB->insert_record('user', $user);
                    $trace->output('added user with username: '. $user->username. ' and id: '. $id);
                } catch (moodle_exception $e) {
                    $trace->output('error adding user with username: '. $user->username);
                    continue;
                }
                // If relevant, tag for password generation.
                if ($this->is_internal()) {
                    set_user_preference('auth_forcepasswordchange', 1, $id);
                    set_user_preference('create_password',          1, $id);
                }
                // Make sure user context is present.
                context_user::instance($id);
            }
            unset($addusers);
        }
        $dbman->drop_table($table);
        $trace->output('done');
        $trace->finished();
        return 0;
    }

    function user_exists($username) {

        // Init result value.
        $result = false;

        $extusername = core_text::convert($username, 'utf-8', $this->config->extencoding);

        $authdb = $this->db_init();

        $rs = $authdb->Execute("SELECT *
                                  FROM {$this->config->table}
                                 WHERE {$this->config->fielduser} = '".$this->ext_addslashes($extusername)."' ");

        if (!$rs) {
            print_error('cannottconnect','auth_db');
        } else if (!$rs->EOF) {
            // User exists externally.
            $result = true;
        }

        $authdb->Close();
        return $result;
    }


    /**
     * will update a local user record from an external source.
     * is a lighter version of the one in moodlelib -- won't do
     * expensive ops such as enrolment.
     *
     * If you don't pass $updatekeys, there is a performance hit and
     * values removed from DB won't be removed from moodle.
     *
     * @param string $username username
     * @param bool $updatekeys
     * @return stdClass
     */
    function update_user_record($username, $updatekeys=false) {
        global $CFG, $DB;

        //just in case check text case
        $username = trim(core_text::strtolower($username));

        // get the current user record
        $user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id));
        if (empty($user)) { // trouble
            error_log("Cannot update non-existent user: $username");
            print_error('auth_dbusernotexist','auth_db',$username);
            die;
        }

        // Ensure userid is not overwritten.
        $userid = $user->id;
        $needsupdate = false;

        $updateuser = new stdClass();
        $updateuser->id = $userid;
        if ($newinfo = $this->get_userinfo($username)) {
            $newinfo = truncate_userinfo($newinfo);

            if (empty($updatekeys)) { // All keys? This does not support removing values.
                $updatekeys = array_keys($newinfo);
            }

            foreach ($updatekeys as $key) {
                if (isset($newinfo[$key])) {
                    $value = $newinfo[$key];
                } else {
                    $value = '';
                }

                if (!empty($this->config->{'field_updatelocal_' . $key})) {
                    if (isset($user->{$key}) and $user->{$key} != $value) { // Only update if it's changed.
                        $needsupdate = true;
                        $updateuser->$key = $value;
                    }
                }
            }
        }
        if ($needsupdate) {
            require_once($CFG->dirroot . '/user/lib.php');
            user_update_user($updateuser);
        }
        return $DB->get_record('user', array('id'=>$userid, 'deleted'=>0));
    }

    /**
     * Called when the user record is updated.
     * Modifies user in external database. It takes olduser (before changes) and newuser (after changes)
     * compares information saved modified information to external db.
     *
     * @param stdClass $olduser     Userobject before modifications
     * @param stdClass $newuser     Userobject new modified userobject
     * @return boolean result
     *
     */
    function user_update($olduser, $newuser) {
        if (isset($olduser->username) and isset($newuser->username) and $olduser->username != $newuser->username) {
            error_log("ERROR:User renaming not allowed in ext db");
            return false;
        }

        if (isset($olduser->auth) and $olduser->auth != $this->authtype) {
            return true; // Just change auth and skip update.
        }

        $curruser = $this->get_userinfo($olduser->username);
        if (empty($curruser)) {
            error_log("ERROR:User $olduser->username found in ext db");
            return false;
        }

        $extusername = core_text::convert($olduser->username, 'utf-8', $this->config->extencoding);

        $authdb = $this->db_init();

        $update = array();
        foreach($curruser as $key=>$value) {
            if ($key == 'username') {
                continue; // Skip this.
            }
            if (empty($this->config->{"field_updateremote_$key"})) {
                continue; // Remote update not requested.
            }
            if (!isset($newuser->$key)) {
                continue;
            }
            $nuvalue = $newuser->$key;
            if ($nuvalue != $value) {
                $update[] = $this->config->{"field_map_$key"}."='".$this->ext_addslashes(core_text::convert($nuvalue, 'utf-8', $this->config->extencoding))."'";
            }
        }
        if (!empty($update)) {
            $authdb->Execute("UPDATE {$this->config->table}
                                 SET ".implode(',', $update)."
                               WHERE {$this->config->fielduser}='".$this->ext_addslashes($extusername)."'");
        }
        $authdb->Close();
        return true;
    }

    /**
     * A chance to validate form data, and last chance to
     * do stuff before it is inserted in config_plugin
     *
     * @param stfdClass $form
     * @param array $err errors
     * @return void
     */
     function validate_form($form, &$err) {
        if ($form->passtype === 'internal') {
            $this->config->changepasswordurl = '';
            set_config('changepasswordurl', '', 'auth/db');
        }
    }

    function prevent_local_passwords() {
        return !$this->is_internal();
    }

    /**
     * Returns true if this authentication plugin is "internal".
     *
     * Internal plugins use password hashes from Moodle user table for authentication.
     *
     * @return bool
     */
    function is_internal() {
        if (!isset($this->config->passtype)) {
            return true;
        }
        return ($this->config->passtype === 'internal');
    }

    /**
     * Indicates if moodle should automatically update internal user
     * records with data from external sources using the information
     * from auth_plugin_base::get_userinfo().
     *
     * @return bool true means automatically copy data from ext to user table
     */
    function is_synchronised_with_external() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return ($this->is_internal() or !empty($this->config->changepasswordurl));
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    function change_password_url() {
        if ($this->is_internal() || empty($this->config->changepasswordurl)) {
            // Standard form.
            return null;
        } else {
            // Use admin defined custom url.
            return new moodle_url($this->config->changepasswordurl);
        }
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    function can_reset_password() {
        return $this->is_internal();
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param stdClass $config
     * @param array $err errors
     * @param array $user_fields
     * @return void
     */
    function config_form($config, $err, $user_fields) {
        include 'config.html';
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     *
     * @param srdClass $config
     * @return bool always true or exception
     */
    function process_config($config) {
        // set to defaults if undefined
        if (!isset($config->host)) {
            $config->host = 'localhost';
        }
        if (!isset($config->type)) {
            $config->type = 'mysql';
        }
        if (!isset($config->sybasequoting)) {
            $config->sybasequoting = 0;
        }
        if (!isset($config->name)) {
            $config->name = '';
        }
        if (!isset($config->user)) {
            $config->user = '';
        }
        if (!isset($config->pass)) {
            $config->pass = '';
        }
        if (!isset($config->table)) {
            $config->table = '';
        }
        if (!isset($config->fielduser)) {
            $config->fielduser = '';
        }
        if (!isset($config->fieldpass)) {
            $config->fieldpass = '';
        }
        if (!isset($config->passtype)) {
            $config->passtype = 'plaintext';
        }
        if (!isset($config->extencoding)) {
            $config->extencoding = 'utf-8';
        }
        if (!isset($config->setupsql)) {
            $config->setupsql = '';
        }
        if (!isset($config->debugauthdb)) {
            $config->debugauthdb = 0;
        }
        if (!isset($config->removeuser)) {
            $config->removeuser = AUTH_REMOVEUSER_KEEP;
        }
        if (!isset($config->changepasswordurl)) {
            $config->changepasswordurl = '';
        }

        // Save settings.
        set_config('host',          $config->host,          'auth/db');
        set_config('type',          $config->type,          'auth/db');
        set_config('sybasequoting', $config->sybasequoting, 'auth/db');
        set_config('name',          $config->name,          'auth/db');
        set_config('user',          $config->user,          'auth/db');
        set_config('pass',          $config->pass,          'auth/db');
        set_config('table',         $config->table,         'auth/db');
        set_config('fielduser',     $config->fielduser,     'auth/db');
        set_config('fieldpass',     $config->fieldpass,     'auth/db');
        set_config('passtype',      $config->passtype,      'auth/db');
        set_config('extencoding',   trim($config->extencoding), 'auth/db');
        set_config('setupsql',      trim($config->setupsql),'auth/db');
        set_config('debugauthdb',   $config->debugauthdb,   'auth/db');
        set_config('removeuser',    $config->removeuser,    'auth/db');
        set_config('changepasswordurl', trim($config->changepasswordurl), 'auth/db');

        return true;
    }

    /**
     * Add slashes, we can not use placeholders or system functions.
     *
     * @param string $text
     * @return string
     */
    function ext_addslashes($text) {
        if (empty($this->config->sybasequoting)) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'', '"', "\0"), array('\\\'', '\\"', '\\0'), $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    /**
     * Test if settings are ok, print info to output.
     * @private
     */
    public function test_settings() {
        global $CFG, $OUTPUT;

        // NOTE: this is not localised intentionally, admins are supposed to understand English at least a bit...

        raise_memory_limit(MEMORY_HUGE);

        if (empty($this->config->table)) {
            echo $OUTPUT->notification('External table not specified.', 'notifyproblem');
            return;
        }

        if (empty($this->config->fielduser)) {
            echo $OUTPUT->notification('External user field not specified.', 'notifyproblem');
            return;
        }

        $olddebug = $CFG->debug;
        $olddisplay = ini_get('display_errors');
        ini_set('display_errors', '1');
        $CFG->debug = DEBUG_DEVELOPER;
        $olddebugauthdb = $this->config->debugauthdb;
        $this->config->debugauthdb = 1;
        error_reporting($CFG->debug);

        $adodb = $this->db_init();

        if (!$adodb or !$adodb->IsConnected()) {
            $this->config->debugauthdb = $olddebugauthdb;
            $CFG->debug = $olddebug;
            ini_set('display_errors', $olddisplay);
            error_reporting($CFG->debug);
            ob_end_flush();

            echo $OUTPUT->notification('Cannot connect the database.', 'notifyproblem');
            return;
        }

        $rs = $adodb->Execute("SELECT *
                                 FROM {$this->config->table}
                                WHERE {$this->config->fielduser} <> 'random_unlikely_username'"); // Any unlikely name is ok here.

        if (!$rs) {
            echo $OUTPUT->notification('Can not read external table.', 'notifyproblem');

        } else if ($rs->EOF) {
            echo $OUTPUT->notification('External table is empty.', 'notifyproblem');
            $rs->close();

        } else {
            $fields_obj = $rs->FetchObj();
            $columns = array_keys((array)$fields_obj);

            echo $OUTPUT->notification('External table contains following columns:<br />'.implode(', ', $columns), 'notifysuccess');
            $rs->close();
        }

        $adodb->Close();

        $this->config->debugauthdb = $olddebugauthdb;
        $CFG->debug = $olddebug;
        ini_set('display_errors', $olddisplay);
        error_reporting($CFG->debug);
        ob_end_flush();
    }
}
