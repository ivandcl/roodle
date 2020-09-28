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
 * Roodle filter settings
 *
 * @package    filter
 * @subpackage roodle
 * @copyright  2020 Claros,Morales,Echeverria
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
	$url = new moodle_url('/admin/settings.php', ['section' => 'managemediaplayers']);
    $item = new admin_setting_heading('filter_roodle/about',
        '',
        new lang_string('settingformats_desc', 'filter_roodle', $url->out()));
    $settings->add($item);
    

    $settings->add(new  admin_setting_configtext('filter_roodle/temporal',
            get_string('temporal', 'filter_roodle'),
            get_string('temporal_desc', 'filter_roodle'),
            '/tmp'));
			/*
	$settings->add(new admin_setting_configmulticheckbox('filter_roodle/formats',
            get_string('settingformats', 'filter_roodle'),
            get_string('settingformats_desc', 'filter_roodle'),
            array(FORMAT_MOODLE => 1), format_text_menu()));*/
}