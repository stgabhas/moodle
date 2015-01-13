<?php

/**
 * Invalida cache dos scripts de um diretório em particular ou dos diretórios do Moodle caso do dirpath não seja informado
 *
 * @access private
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
        foreach($cache['scripts'] AS $script=>$arr) {
            foreach($dirs AS $d) {
                if (strpos($script, $d) === 0) {
                    opcache_invalidate($script, $force);
                    break;
                }
            }
        }

    } else if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

/**
 * Invalida cache de todos os scripts php
 *
 * @access private
 * @param boolean $force se true, invalida os scripts do cache mesmo que eles não tenham sido alterados
 * @return void
 */

function opcache_invalidate_all($force = false) {
    if (!function_exists('opcache_invalidate')) {
        return;
    }

    $config = opcache_get_configuration();
    if (!$config['directives']['opcache.enable']) {
        return;
    }

    $cache = opcache_get_status();
    foreach($cache['scripts'] AS $script=>$arr) {
        opcache_invalidate($script, $force);
    }
}
