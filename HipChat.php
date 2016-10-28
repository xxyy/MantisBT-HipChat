<?php
/**
 * HipChat Integration
 * Copyright (C) 2014 Ben Ramsey (ben@benramsey.com)
 *
 * Original Source for Slack Integration
 * Copyright (C) 2014 Karim Ratib (karim.ratib@gmail.com)
 *
 * HipChat Integration is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 2
 * as published by the Free Software Foundation.
 *
 * HipChat Integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with HipChat Integration; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 * or see http://www.gnu.org/licenses/.
 */ //modified by xxyy 2015-09-24 - remove annoying ping

class HipChatPlugin extends MantisPlugin {
    function register() {
        $this->name = plugin_lang_get( 'title' );
        $this->description = plugin_lang_get( 'description' );
        $this->page = 'config';
        $this->version = '0.1-xxyy';
        $this->requires = array(
            'MantisCore' => '1.3.0',
        );
        $this->author = 'Ben Ramsey';
        $this->contact = 'ben@benramsey.com';
        $this->url = 'http://benramsey.com';
    }

    function install() {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            plugin_error(ERROR_PHP_VERSION, ERROR);
            return false;
        }
        if (!extension_loaded('curl')) {
            plugin_error(ERROR_NO_CURL, ERROR);
            return false;
        }
        return true;
    }

    function config() {
        return array(
            'token' => '',
            'bot_name' => 'MantisBT',
            'notify' => '0',
            'color' => 'purple',
            'rooms' => array(),
            'default_room' => '',
            'columns' => array(
                'status',
                'handler_id',
                'target_version',
                'priority',
                'severity',
            ),
        );
    }

    function hooks() {
        return array(
            'EVENT_REPORT_BUG' => 'bug_report',
            'EVENT_UPDATE_BUG' => 'bug_update',
            'EVENT_BUG_DELETED' => 'bug_deleted',
            'EVENT_BUG_ACTION' => 'bug_action',
            'EVENT_BUGNOTE_ADD' => 'bugnote_add_edit',
            'EVENT_BUGNOTE_EDIT' => 'bugnote_add_edit',
            'EVENT_BUGNOTE_DELETED' => 'bugnote_deleted',
        );
    }

    function bug_report($event, $bug, $bug_id) {
        $this->send_bug_notification($bug, 'bug_created');
    }

    function bug_update($event, $initial_bug, $changed_bug) {
        $this->send_bug_notification($changed_bug, $this->find_message_key($initial_bug, $changed_bug));
    }

    function find_message_key($initial_bug, $changed_bug) {
        $initial_status = $initial_bug->status;
        $new_status = $changed_bug->status;
        if($initial_status == $new_status) {
            return $this->find_assignment_message_key($initial_bug, $changed_bug);
        }
        switch($new_status) {
            case FEEDBACK:
                return 'bug_feedback';
            case ACKNOWLEDGED:
                return 'bug_acknowledged';
            case CONFIRMED:
                return 'bug_confirmed';
            case ASSIGNED:
                return $this->find_assignment_message_key($initial_bug, $changed_bug);
            case RESOLVED:
                return 'bug_resolved';
            case CLOSED:
                return 'bug_closed';
            default:
                return 'bug_updated';
        }
    }

    function find_assignment_message_key($initial_bug, $changed_bug)  {
        if($initial_bug->handler_id == $changed_bug->handler_id) {
            return $initial_bug->status == $changed_bug->status ? 'bug_updated' : 'bug_assigned';
        } else if($changed_bug->handler_id == 0) {
            return 'bug_unassigned';
        } else if($changed_bug->handler_id == auth_get_current_user_id()) {
            return 'bug_selfassigned';
        } else {
            return 'bug_assigned';
        }
    }

    function send_bug_notification($bug, $message_key) {
        $project = project_get_name($bug->project_id);
        $url = string_get_bug_view_url_with_fqdn($bug->id);
        $summary = HipChatPlugin::clean_summary(bug_format_summary($bug->id, SUMMARY_FIELD));
        $user_name = user_get_name(auth_get_current_user_id());
        $assignee_name = $this->format_assignee_name($bug);
        $msg = sprintf(plugin_lang_get($message_key),
            $project, $user_name, $summary, $url, $assignee_name
        );
        $this->notify($msg, $this->get_room($project));
    }

    function format_assignee_name($bug) {
        $assignee_id = $bug->handler_id;
        if($assignee_id == 0) {
            return "nobody";
        } else if($assignee_id == auth_get_current_user_id()) {
            return "themselves";
        } else {
            return user_get_name($assignee_id);
        }
    }

    function bug_action($event, $action, $bug_id) {
        if ($action !== 'DELETE') {
            $bug = bug_get($bug_id);
            $this->bug_report_update('EVENT_UPDATE_BUG', $bug, $bug_id);
        }
    }

    function bug_deleted($event, $bug_id) {
        $bug = bug_get($bug_id);
        $this->send_bug_notification($bug, 'bug_deleted');
    }

    function bugnote_add_edit($event, $bug_id, $bugnote_id) {
        $message_key = $this->find_bugnote_message_key($event, $bug_id, $bugnote_id);
        $this->send_bugnote_notification($bug_id, $bugnote_id, $message_key);
    }

    function find_bugnote_message_key($event, $bug_id, $bugnote_id) {
        return ($event === 'EVENT_BUGNOTE_ADD' ? 'bugnote_created' : 'bugnote_updated') .
                 ($this->bugnote_is_private($bug_id, $bugnote_id) ? '_private' : '');
    }

    function bugnote_is_private($bug_id, $bugnote_id) {
        return bugnote_get_field($bugnote_id, 'view_state') == VS_PRIVATE ||
                 bug_get($bug_id)->view_state == VS_PRIVATE;
    }

    function send_bugnote_notification($bug_id, $bugnote_id, $message_key) {
        $bug = bug_get($bug_id);
        $url = string_get_bugnote_view_url_with_fqdn($bug_id, $bugnote_id);
        $project = project_get_name($bug->project_id);
        $summary = HipChatPlugin::clean_summary(bug_format_summary($bug_id, SUMMARY_FIELD));
        $user_name = user_get_name(auth_get_current_user_id());
        $note = bugnote_get_text($bugnote_id);
        $msg = sprintf(plugin_lang_get($message_key),
            $project, $user_name, $summary, $url, $note
        );
        $this->notify($msg, $this->get_room($project));
    }

    function bugnote_deleted($event, $bug_id, $bugnote_id) {
        $bug = bug_get($bug_id);
        $project = project_get_name($bug->project_id);
        $url = string_get_bug_view_url_with_fqdn($bug_id);
        $summary = HipChatPlugin::clean_summary(bug_format_summary($bug_id, SUMMARY_FIELD));
        $reporter = /*'@' .*/ user_get_name(auth_get_current_user_id());
        $msg = sprintf(plugin_lang_get('bugnote_deleted'), $project, $reporter, $summary, $url);
        $this->notify($msg, $this->get_room($project));
    }

    static function clean_summary($summary) {
        return strip_tags(html_entity_decode($summary));
    }

    function format_value($bug, $field_name) {
        $values = array(
            'id' => function($bug) { return sprintf('%s <%s>', $bug->id, string_get_bug_view_url_with_fqdn($bug->id)); },
            'project_id' => function($bug) { return project_get_name($bug->project_id); },
            'reporter_id' => function($bug) { return /*'@' .*/ user_get_name($bug->reporter_id); },
            'handler_id' => function($bug) { return empty($bug->handler_id) ? plugin_lang_get('no_user') : (/*'@' .*/ user_get_name($bug->handler_id)); },
            'duplicate_id' => function($bug) { return sprintf('%s <%s>', $bug->duplicate_id, string_get_bug_view_url_with_fqdn($bug->duplicate_id)); },
            'priority' => function($bug) { return get_enum_element( 'priority', $bug->priority ); },
            'severity' => function($bug) { return get_enum_element( 'severity', $bug->severity ); },
            'reproducibility' => function($bug) { return get_enum_element( 'reproducibility', $bug->reproducibility ); },
            'status' => function($bug) { return get_enum_element( 'status', $bug->status ); },
            'resolution' => function($bug) { return get_enum_element( 'resolution', $bug->resolution ); },
            'projection' => function($bug) { return get_enum_element( 'projection', $bug->projection ); },
            'category_id' => function($bug) { return category_full_name( $bug->category_id, false ); },
            'eta' => function($bug) { return get_enum_element( 'eta', $bug->eta ); },
            'view_state' => function($bug) { return $bug->view_state == VS_PRIVATE ? lang_get('private') : lang_get('public'); },
            'sponsorship_total' => function($bug) { return sponsorship_format_amount( $bug->sponsorship_total ); },
            'os' => function($bug) { return $bug->os; },
            'os_build' => function($bug) { return $bug->os_build; },
            'platform' => function($bug) { return $bug->platform; },
            'version' => function($bug) { return $bug->version; },
            'fixed_in_version' => function($bug) { return $bug->fixed_in_version; },
            'target_version' => function($bug) { return $bug->target_version; },
            'build' => function($bug) { return $bug->build; },
            'summary' => function($bug) { return HipChatPlugin::clean_summary(bug_format_summary($bug->id, SUMMARY_FIELD)); },
            'last_updated' => function($bug) { return date( config_get( 'short_date_format' ), $bug->last_updated ); },
            'date_submitted' => function($bug) { return date( config_get( 'short_date_format' ), $bug->date_submitted ); },
            'due_date' => function($bug) { return date( config_get( 'short_date_format' ), $bug->due_date ); },
            'description' => function($bug) { return string_display_links( $bug->description ); },
            'steps_to_reproduce' => function($bug) { return string_display_links( $bug->steps_to_reproduce ); },
            'additional_information' => function($bug) { return string_display_links( $bug->additional_information ); },
        );
        // Discover custom fields.
        $t_related_custom_field_ids = custom_field_get_linked_ids( $bug->project_id );
        foreach ( $t_related_custom_field_ids as $t_id ) {
            $t_def = custom_field_get_definition( $t_id );
            $values['custom_' . $t_def['name']] = function($bug) use ($t_id) {
                return custom_field_get_value( $t_id, $bug->id );
            };
        }
        if (isset($values[$field_name])) {
            $func = $values[$field_name];
            return $func($bug);
        }
        else {
            return sprintf(plugin_lang_get('unknown_field'), $field_name);
        }
    }

    function get_room($project) {
        $rooms = plugin_config_get('rooms');
        return isset($rooms[$project]) ? $rooms[$project] : plugin_config_get('default_room');
    }

    function notify($msg, $room) {
        $ch = curl_init();
        // @see https://www.hipchat.com/docs/api/method/rooms/message
        $url = sprintf('https://api.hipchat.com/v1/rooms/message?auth_token=%s', plugin_config_get('token'));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $payload = array(
            'room_id' => $room,
            'from' => plugin_config_get('bot_name'),
            'message' => $msg,
            'message_format' => 'html',
            'notify' => plugin_config_get('notify'),
            'color' => plugin_config_get('color'),
            'format' => 'json',
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        curl_close($ch);
    }

}
