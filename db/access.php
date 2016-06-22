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
 * RememberMe is a plugin for Moodle v2.0 that automatically sends a reminder
 * email to members of a course who have not logged in recently.  
 *
 * RememberMe is a block type plugin for Moodle v2.0 made by students of the
 * Berlin School of Economics and Law. It periodically checks if a user has not
 * logged in for a specified amount of time and if so, it sends a reminder
 * email to this user. Both the time without log-in required to trigger the
 * reminder, as well as the content of the email can be customised.
 *
 * @package    moodle-block_RememberMe
 * @category   block-plugin
 * @copyright  2016 ProPlug
 * @license
 */

$capabilities = array(
    'block/rememberme:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
    'block/rememberme:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);

