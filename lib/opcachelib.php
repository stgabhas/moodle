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

defined('MOODLE_INTERNAL') || die();

/**
 * Invalida cache dos scripts de um diretório em particular ou dos diretórios do Moodle caso do dirpath não seja informado
 */

/**
 * Invalida cache dos scripts de um diretório em particular ou dos diretórios do Moodle caso do dirpath não seja informado
 *
 * @param string $dirpath diretório cujos scripts devem ser invalidados
 * @param boolean $force se true, invalida os scripts do cache mesmo que eles não tenham sido alterados
 * @return void
 */
function opcache_invalidate_dir($dirpath=false, $force = false) {
    global $CFG;

    if (!function_exists('opcache_invalidate')) {
        return;
    }

    $config = opcache_get_configuration();
    if (!$config['directives']['opcache.enable']) {
        return;
    }

    if ($config['directives']['opcache.use_cwd']) {

        if ($dirpath) {
            $dirs = array(substr($dirpath, -1) == '/' ? $dirpath : $dirpath . '/');
        } else {
            $dirs = array($CFG->dirroot . '/', $CFG->dataroot . '/');

            if (strpos($CFG->dataroot, $CFG->tempdir) !== 0) {
                $dirs[] = substr($CFG->tempdir, -1) == '/' ? $CFG->tempdir : $CFG->tempdir . '/';
            }

            if (strpos($CFG->dataroot, $CFG->cachedir) !== 0) {
                $dirs[] = substr($CFG->cachedir, -1) == '/' ? $CFG->cachedir : $CFG->cachedir . '/';
            }
        }

        $cache = opcache_get_status();
        if (isset($cache['scripts'])) {
            foreach ($cache['scripts'] as $script => $arr) {
                foreach ($dirs as $d) {
                    if (strpos($script, $d) === 0) {
                        opcache_invalidate($script, $force);
                        break;
                    }
                }
            }
        }

    } else if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}
