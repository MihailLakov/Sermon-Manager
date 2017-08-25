<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$key_id   = ! empty( $key_id ) ? $key_id : - 1;
$key_data = ! empty( $key_data ) ? $key_data : array();
?>

<!--suppress XmlDefaultAttributeValue -->
<div id="key-fields" class="settings-panel">
    <h2><?php _e( 'Key details', 'sermon-manager' ); ?></h2>

    <input type="hidden" id="key_id" value="<?php echo esc_attr( $key_id ); ?>"/>

    <table id="api-keys-options" class="form-table">
        <tbody>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="key_description"><?php _e( 'Description', 'sermon-manager' ); ?></label>
				<?php echo sm_help_tip( __( 'Friendly name for identifying this key.', 'sermon-manager' ) ); ?>
            </th>
            <td class="forminp">
                <input id="key_description" type="text" class="input-text regular-input"
                       value="<?php echo esc_attr( $key_data['description'] ); ?>"/>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="key_user"><?php _e( 'User', 'sermon-manager' ); ?></label>
				<?php echo sm_help_tip( __( 'Owner of these keys.', 'sermon-manager' ) ); ?>
            </th>
            <td class="forminp">
				<?php
				$curent_user_id = get_current_user_id();
				$user_id        = ! empty( $key_data['user_id'] ) ? absint( $key_data['user_id'] ) : $curent_user_id;
				$user           = get_user_by( 'id', $user_id );
				/* translators: 1: user display name 2: user ID 3: user email */
				$user_string = sprintf(
					esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'sermon-manager' ),
					$user->display_name,
					absint( $user->ID ),
					$user->user_email
				);
				?>
                <select class="sm-customer-search" id="key_user"
                        data-placeholder="<?php esc_attr_e( 'Search for a user&hellip;', 'sermon-manager' ); ?>"
                        data-allow_clear="true">
                    <option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo $user_string; ?>
                    <option>
                </select>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="key_permissions"><?php _e( 'Permissions', 'sermon-manager' ); ?></label>
				<?php echo sm_help_tip( __( 'Select the access type of these keys.', 'sermon-manager' ) ); ?>
            </th>
            <td class="forminp">
                <select id="key_permissions" class="sm-enhanced-select">
					<?php
					$permissions = array(
						'read'       => __( 'Read', 'sermon-manager' ),
						'write'      => __( 'Write', 'sermon-manager' ),
						'read_write' => __( 'Read/Write', 'sermon-manager' ),
					);

					foreach ( $permissions as $permission_id => $permission_name ) : ?>
                        <option value="<?php echo esc_attr( $permission_id ); ?>" <?php selected( $key_data['permissions'], $permission_id, true ); ?>><?php echo esc_html( $permission_name ); ?></option>
					<?php endforeach; ?>
                </select>
            </td>
        </tr>

		<?php if ( 0 !== $key_id ) : ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
					<?php _e( 'Consumer key ending in', 'sermon-manager' ); ?>
                </th>
                <td class="forminp">
                    <code>&hellip;<?php echo esc_html( $key_data['truncated_key'] ); ?></code>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
					<?php _e( 'Last access', 'sermon-manager' ); ?>
                </th>
                <td class="forminp">
						<span><?php
							if ( ! empty( $key_data['last_access'] ) ) {
								/* translators: 1: last access date 2: last access time */
								$date = sprintf( __( '%1$s at %2$s', 'sermon-manager' ), date_i18n( sm_date_format(), strtotime( $key_data['last_access'] ) ), date_i18n( sm_time_format(), strtotime( $key_data['last_access'] ) ) );

								echo apply_filters( 'sm_api_key_last_access_datetime', $date, $key_data['last_access'] );
							} else {
								_e( 'Unknown', 'sermon-manager' );
							}
							?></span>
                </td>
            </tr>
		<?php endif ?>
        </tbody>
    </table>

	<?php do_action( 'sm_admin_key_fields', $key_data ); ?>

	<?php
	if ( 0 == $key_id ) {
		submit_button( __( 'Generate API key', 'sermon-manager' ), 'primary', 'update_api_key' );
	} else {
		?>
        <p class="submit">
			<?php submit_button( __( 'Save changes', 'sermon-manager' ), 'primary', 'update_api_key', false ); ?>
            <a style="color: #a00; text-decoration: none; margin-left: 10px;"
               href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'revoke-key' => $key_id ), admin_url( 'admin.php?page=sm-settings&tab=api&section=keys' ) ), 'revoke' ) ); ?>"><?php _e( 'Revoke key', 'sermon-manager' ); ?></a>
        </p>
		<?php
	}
	?>
</div>

<script type="text/template" id="tmpl-api-keys-template">
    <p id="copy-error"></p>
    <table class="form-table">
        <tbody>
        <tr valign="top">
            <th scope="row" class="titledesc">
				<?php _e( 'Consumer key', 'sermon-manager' ); ?>
            </th>
            <td class="forminp">
                <input title="<?php _e( 'Consumer key', 'sermon-manager' ); ?>" id="key_consumer_key" type="text"
                       value="{{ data.consumer_key }}" size="55" readonly="readonly">
                <button type="button" class="button-secondary copy-key"
                        data-tip="<?php esc_attr_e( 'Copied!', 'sermon-manager' ); ?>"><?php _e( 'Copy', 'sermon-manager' ); ?></button>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
				<?php _e( 'Consumer secret', 'sermon-manager' ); ?>
            </th>
            <td class="forminp">
                <input title="<?php _e( 'Consumer secret', 'sermon-manager' ); ?>" id="key_consumer_secret" type="text"
                       value="{{ data.consumer_secret }}" size="55" readonly="readonly">
                <button type="button" class="button-secondary copy-secret"
                        data-tip="<?php esc_attr_e( 'Copied!', 'sermon-manager' ); ?>"><?php _e( 'Copy', 'sermon-manager' ); ?></button>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
				<?php _e( 'QRCode', 'sermon-manager' ); ?>
            </th>
            <td class="forminp">
                <div id="keys-qrcode"></div>
            </td>
        </tr>
        </tbody>
    </table>
</script>
