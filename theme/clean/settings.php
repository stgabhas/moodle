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
 * Moodle's Clean theme, an example of how to make a Bootstrap theme
 *
 * DO NOT MODIFY THIS THEME!
 * COPY IT FIRST, THEN RENAME THE COPY AND MODIFY IT INSTEAD.
 *
 * For full information about creating Moodle themes, see:
 * http://docs.moodle.org/dev/Themes_2.0
 *
 * @package   theme_clean
 * @copyright 2013 Moodle, moodle.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Invert Navbar to dark background.
    $name = 'theme_clean/invert';
    $title = get_string('invert', 'theme_clean');
    $description = get_string('invertdesc', 'theme_clean');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Logo file setting.
    $name = 'theme_clean/logo';
    $title = get_string('logo','theme_clean');
    $description = get_string('logodesc', 'theme_clean');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Custom CSS file.
    $name = 'theme_clean/customcss';
    $title = get_string('customcss', 'theme_clean');
    $description = get_string('customcssdesc', 'theme_clean');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Footnote setting.
    $name = 'theme_clean/footnote';
    $title = get_string('footnote', 'theme_clean');
    $description = get_string('footnotedesc', 'theme_clean');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Logo Esquerda.
    $name = 'theme_clean/logo_left';
    $title = get_string('logo_left','theme_clean');
    $description = get_string('logo_left_desc', 'theme_clean');
    $default = 'ufsc';
    $choices = array('none' => 'Não exibir', 'ufsc' => 'UFSC', 'ead' => 'EaD', 'unasus' => 'UNA-SUS');
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $settings->add($setting);

    // Logo Central
    $name = 'theme_clean/logo_center';
    $title = get_string('logo_center','theme_clean');
    $description = get_string('logo_center_desc', 'theme_clean');
    $default = 'none';
    $choices = array('none' => 'Não exibir',
                     'sitefullname' => 'Nome completo do site',
                     'presencial' => 'Presencial',
                     'provas' => 'Provas',
                     'category' => 'Configurar por categoria');
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $settings->add($setting);

    // Mapeamento de Categorias x Nome de Curso.
    $name = 'theme_clean/course_names_mapping';
    $heading = get_string('course_mapping', 'theme_clean');
    $setting = new admin_setting_heading($name, $heading, null);
    $settings->add($setting);

    $categories = $DB->get_records('course_categories', array('depth' => 1));
    foreach ($categories as $key => $category) {

        // Título
        $name = "theme_clean/course_category_title_{$category->id}";
        $visiblename = "Título ({$category->name})";
        $description = get_string('course_category_title_description', 'theme_clean', $category->name);
        $defaultsetting = $category->name;
        $setting = new admin_setting_configtext($name, $visiblename, $description, $defaultsetting);
        $settings->add($setting);

        // Subtítulo
        $name = "theme_clean/course_category_subtitle_{$category->id}";
        $visiblename = "Subtítulo ({$category->name})";
        $description = get_string('course_category_subtitle_description', 'theme_clean', $category->name);
        $setting = new admin_setting_configtext($name, $visiblename, $description, null);
        $settings->add($setting);
    }

}
