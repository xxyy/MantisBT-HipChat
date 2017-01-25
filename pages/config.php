<?php
/**
 * HipChat Integration
 * Copyright (C) 2015 - 2016 Philipp Nowak (foss@l1t.li)
 * Copyright (C) 2014        Ben Ramsey    (ben@benramsey.com)
 *
 * Original Source for Slack Integration
 * Copyright (C) 2014        Karim Ratib   (karim.ratib@gmail.com)
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
 */

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

layout_page_header( plugin_lang_get( 'title' ) );
layout_page_begin( 'manage_overview_page.php' );

print_manage_menu( 'manage_plugin_page.php' );

?>

<div class="col-md-12 col-xs-12">
  <div class="space-10"></div>

  <div class="form-container">
    <form action="<?php echo plugin_page( 'config_edit' )?>" method="post">
    <?php echo form_security_field( 'plugin_HipChat_config_edit' ) ?>
    <div class="widget-box widget-color-blue2">
      <div class="widget-header widget-header-small">
        <h4 class="widget-title lighter">
          <i class="ace-icon fa fa-comment"></i>
          <?php echo plugin_lang_get( 'title' ) . ' : ' . plugin_lang_get( 'config' ) ?>
        </h4>
      </div>
    </div>

    <div class="widget-body">
      <div class="widget-main no-padding">
        <div class="table-responsive">
          <table class="table table-bordered table-condensed table-striped">
            <tr>
              <th class="category width-40"><?php echo plugin_lang_get( 'token' ) ?></th>
              <td>
                <span class="input">
                   <input type="text" name="token" class="form-control" value="<?php echo plugin_config_get( 'token' ) ?>" />
                </span>
              </td>
            </tr>
            <tr>
              <th class="category width-40"><?php echo plugin_lang_get( 'bot_name' ) ?></th>
              <td>
                <span class="input">
                   <input type="text" name="bot_name" class="form-control" value="<?php echo plugin_config_get( 'bot_name' ) ?>" />
                </span>
              </td>
            </tr>
            <tr>
              <th class="category width-40"><?php echo plugin_lang_get( 'notify' ) ?></th>
              <td>
                <span class="input">
                   <input type="text" name="notify" class="form-control" value="<?php echo plugin_config_get( 'notify' ) ?>" />
                </span>
              </td>
            </tr>
            <tr>
              <th class="category width-40"><?php echo plugin_lang_get( 'color' ) ?></th>
              <td>
                <span class="input">
                   <input type="text" name="color" class="form-control" value="<?php echo plugin_config_get( 'color' ) ?>" />
                </span>
              </td>
            </tr>
            <tr>
              <th class="category width-40"><?php echo plugin_lang_get( 'default_room' ) ?></th>
              <td>
                <span class="input">
                   <input type="text" name="default_room" class="form-control" value="<?php echo plugin_config_get( 'default_room' ) ?>" />
                </span>
              </td>
            </tr>
            <tr>
              <th class="category width-40">
                <?php echo plugin_lang_get( 'rooms' ) ?><br />
                <span class="small">
                  Specifies the mapping between Mantis project names and HipChat rooms, as an array.<br />
                  However, it must be set using the <a href="adm_config_report.php">Configuration Report</a> screen, via the <strong>plugin_HipChat_rooms</strong> setting.
                </span>
              </th>
              <td>
                <pre><?php var_export(plugin_config_get( 'rooms' ))?></pre>
              </td>
            </tr>
            <tr>
              <th class="category width-40">
                <?php echo plugin_lang_get( 'columns' ) ?><br />
                <span class="small">
                  Specifies the bug fields that should be attached to the HipChat notifications.<br />
                  However, it must be set using the <a href="adm_config_report.php">Configuration Report</a> screen, via the <strong>plugin_HipChat_columns</strong> setting.<br />
                  <strong>This setting does not have any effect currrently.</strong>
                </span>
              </th>
              <td>
                <pre><?php var_export(plugin_config_get( 'columns' ))?></pre>
              </td>
            </tr>
          </table>
        </div>
      </div> <!-- /.widget-main -->

      <div class="widget-toolbox padding-8 clearfix">
        <input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo lang_get( 'change_configuration' )?>" />
      </div>
    </div> <!-- /.widget-body -->
  </form>
  </div>
</div>

<?php
layout_page_end();
