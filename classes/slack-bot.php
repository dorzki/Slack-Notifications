<?php
/**
 * Slack Bot Class.
 *
 * @package   Slack Notifications
 * @since     1.0.0
 * @version   1.0.10
 * @author    Dor Zuberi <me@dorzki.co.il>
 * @link      https://www.dorzki.co.il
 */

if ( ! class_exists( 'SlackBot' ) ) {

	/**
	 * Class SlackBot
	 */
	class SlackBot {

		/**
		 * Slack Webhook Endpoint
		 *
		 * @var    string
		 * @since   1.0.0
		 */
		private $apiEndpoint;


		/**
		 * Slack Channel
		 *
		 * @var    string
		 * @since   1.0.0
		 */
		private $slackChannel;


		/**
		 * Slack Name
		 *
		 * @var    string
		 * @since   1.0.0
		 */
		private $botName;


		/**
		 * Slack Image
		 *
		 * @var    string
		 * @since   1.0.0
		 */
		private $botIcon;


		/**
		 * Get slack bot details.
		 *
		 * @since   1.0.0
		 */
		public function __construct() {

			$this->apiEndpoint  = get_option( 'slack_webhook_endpoint' );
			$this->slackChannel = $this->parse_channel_names( get_option( 'slack_channel_name' ) );
			$this->botName      = ( get_option( 'slack_bot_username' ) === '' ) ? 'Slack Bot' : get_option( 'slack_bot_username' );
			$this->botIcon      = ( get_option( 'slack_bot_image' ) === '' ) ? PLUGIN_ROOT_URL . 'assets/images/default-bot-icon.png' : get_option( 'slack_bot_image' );

		}


		/**
		 * Send the notification through the API.
		 *
		 * @param   string $theMessage the notification to send.
		 *
		 * @return  boolean                 did the message sent successfully?
		 * @since   1.0.0
		 */
		public function send_message( $theMessage ) {

			$sendValid = true;

			foreach ( $this->slackChannel as $channel ) {

				$apiResponse = wp_remote_post( $this->apiEndpoint, array(
					'method'      => 'POST',
					'timeout'     => 30,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => array(
						'payload' => wp_json_encode( array(
							'channel'  => $channel,
							'username' => $this->botName,
							'icon_url' => $this->botIcon,
							'text'     => sprintf( '%s @ *<%s|%s>*', $theMessage, get_bloginfo( 'url' ), get_bloginfo( 'name' ) ),
						) ),
					),
				) );

				if ( is_wp_error( $apiResponse ) ) {
					$sendValid = false;
				}

			}

			// Check if there is an error.
			if ( ! $sendValid ) {

				$this->display_send_error();

				return false;

			}

			return true;

		}

		/**
		 * Send post notification through the API.
		 *
		 * @param   string $action text notification to send.
		 * @param   string $title title of the post.
		 * @param   string $url link to the post.
		 * @param   string $author author of the post.
		 * @param   string $summary summary of the post.
		 *
		 * @return  boolean did the message sent successfully?
		 * @since   1.1.0
		 */
		public function send_post_message($action, $title, $url, $author, $summary) {

			$sendValid = true;
			foreach ( $this->slackChannel as $channel ) {

				$apiResponse = wp_remote_post( $this->apiEndpoint, array(
					'method'      => 'POST',
					'timeout'     => 30,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => array(
						'payload' => wp_json_encode( array(
							'channel'  => $channel,
							'username' => $this->botName,
							'icon_url' => $this->botIcon,
							'text'     => $action,
							'attachments' => array(
								array(
									'fallback' => "$title - $author",
									'author_name' => $author,
									'title' => $title,
									'title_link' => $url,
									'text' => $summary,
								)
							)
						) ),
					),
				) );

				if ( is_wp_error( $apiResponse ) ) {
					$sendValid = false;
				}

			}

			// Check if there is an error.
			if ( ! $sendValid ) {
				$this->display_send_error();
				return false;
			}

			return true;
		}

		/**
		 * Set the plugin to show an error.
		 *
		 * @since 1.0.5
		 */
		private function display_send_error() {

			update_option( 'slack_notice_connectivity', 1 );

		}


		/**
		 * Splits list of comma separated channels.
		 *
		 * @param string $channels_string comma separated values
		 *
		 * @return array
		 * @since 1.0.10
		 */
		private function parse_channel_names( $channels_string ) {

			if ( false !== strpos( ',', $channels_string ) ) {
				return array( $channels_string );
			}

			$channels = explode( ',', $channels_string );

			if ( ! is_array( $channels ) || 1 === count( $channels ) ) {
				return array( $channels_string );
			}

			return $channels;

		}

	}

}
