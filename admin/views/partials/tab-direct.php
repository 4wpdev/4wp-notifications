<?php
/**
 * Direct notifications tab — admin broadcast form.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$forwp_notif_users_by_role = ForWP_Notifications_Recipient_Resolver::get_users_by_role();
$forwp_notif_wp_roles      = wp_roles();
?>
<div class="forwp-notifications-intro-card">
	<h2 class="forwp-notifications-intro-card__title"><?php esc_html_e( 'Send a direct notification', '4wp-notifications' ); ?></h2>
	<p class="forwp-notifications-intro-card__text"><?php esc_html_e( 'Broadcast a message to one or more roles and/or individual users. Recipients see it in the bell dropdown and on their notifications page.', '4wp-notifications' ); ?></p>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="forwp-notifications-card forwp-notifications-direct-form">
	<input type="hidden" name="action" value="forwp_notifications_send" />
	<?php wp_nonce_field( 'forwp_notifications_send' ); ?>

	<div class="forwp-notifications-field">
		<label class="forwp-notifications-field__label" for="forwp_notif_title"><?php esc_html_e( 'Title', '4wp-notifications' ); ?></label>
		<input type="text" name="title" id="forwp_notif_title" class="regular-text" required />
	</div>

	<div class="forwp-notifications-field">
		<label class="forwp-notifications-field__label" for="forwp_notif_message"><?php esc_html_e( 'Message', '4wp-notifications' ); ?></label>
		<textarea name="message" id="forwp_notif_message" class="large-text" rows="3"></textarea>
	</div>

	<div class="forwp-notifications-field">
		<label class="forwp-notifications-field__label" for="forwp_notif_url"><?php esc_html_e( 'Link URL (optional)', '4wp-notifications' ); ?></label>
		<input type="url" name="url" id="forwp_notif_url" class="regular-text" placeholder="https://" />
	</div>

	<div class="forwp-notifications-field">
		<span class="forwp-notifications-field__label"><?php esc_html_e( 'Recipients', '4wp-notifications' ); ?></span>
		<?php if ( empty( $forwp_notif_users_by_role ) ) : ?>
			<p class="forwp-notifications-admin-muted"><?php esc_html_e( 'No users found.', '4wp-notifications' ); ?></p>
		<?php else : ?>
			<div class="forwp-notifications-recipient-toolbar">
				<button type="button" class="button button-small" data-forwp-select-all><?php esc_html_e( 'Select all users', '4wp-notifications' ); ?></button>
				<button type="button" class="button button-small" data-forwp-select-none><?php esc_html_e( 'Clear all', '4wp-notifications' ); ?></button>
			</div>
			<div class="forwp-notifications-recipient-groups">
				<?php foreach ( $forwp_notif_users_by_role as $forwp_notif_role_key => $forwp_notif_role_users ) : ?>
					<?php
					$forwp_notif_role_label = isset( $forwp_notif_wp_roles->roles[ $forwp_notif_role_key ]['name'] )
						? translate_user_role( $forwp_notif_wp_roles->roles[ $forwp_notif_role_key ]['name'] )
						: ucfirst( $forwp_notif_role_key );
					?>
					<div class="forwp-notifications-recipient-group" data-role-group="<?php echo esc_attr( $forwp_notif_role_key ); ?>">
						<div class="forwp-notifications-recipient-group__head">
							<label class="forwp-notifications-role-pick">
								<input type="checkbox" name="role_slugs[]" value="<?php echo esc_attr( $forwp_notif_role_key ); ?>" class="forwp-notif-role-cb" data-role="<?php echo esc_attr( $forwp_notif_role_key ); ?>" />
								<strong><?php echo esc_html( $forwp_notif_role_label ); ?></strong>
							</label>
							<span class="forwp-notifications-recipient-group__count"><?php
							/* translators: %d: number of users in the role */
							echo esc_html( sprintf( _n( '%d user', '%d users', count( $forwp_notif_role_users ), '4wp-notifications' ), count( $forwp_notif_role_users ) ) );
							?></span>
							<button type="button" class="button-link" data-forwp-select-role="<?php echo esc_attr( $forwp_notif_role_key ); ?>"><?php esc_html_e( 'Select users in role', '4wp-notifications' ); ?></button>
						</div>
						<ul class="forwp-notifications-recipient-list">
							<?php foreach ( $forwp_notif_role_users as $forwp_notif_user ) : ?>
								<li>
									<label class="forwp-notifications-recipient-row">
										<input type="checkbox" name="user_ids[]" value="<?php echo (int) $forwp_notif_user->ID; ?>" class="forwp-notif-user-cb" data-role="<?php echo esc_attr( $forwp_notif_role_key ); ?>" />
										<span class="forwp-notifications-recipient-row__name"><?php echo esc_html( $forwp_notif_user->display_name ); ?></span>
										<?php if ( $forwp_notif_user->user_login !== $forwp_notif_user->display_name ) : ?>
											<span class="forwp-notifications-recipient-row__login"><?php echo esc_html( $forwp_notif_user->user_login ); ?></span>
										<?php endif; ?>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="forwp-notifications-admin-muted"><?php esc_html_e( 'Check a role to include every user with that role, or pick individual users below each role.', '4wp-notifications' ); ?></p>
		<?php endif; ?>
	</div>

	<p class="forwp-notifications-form-actions">
		<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Send notification', '4wp-notifications' ); ?></button>
	</p>
</form>
