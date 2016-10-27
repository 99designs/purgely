<?php
/**
 * Singleton for registering default WP purges.
 */

class Purgely_Purges {
	/**
	 * The one instance of Purgely_Purges.
	 *
	 * @var Purgely_Purges
	 */
	private static $instance;

	/**
	 * Instantiate or return the one Purgely_Purges instance.
	 *
	 * @return Purgely_Purges
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initiate actions.
	 *
	 * @return Purgely_Purges
	 */
	public function __construct() {
		foreach ( $this->_purge_post_actions() as $action ) {
			add_action( $action, array( $this, 'purge' ), 10, 1 );
		}

		add_action('transition_comment_status', array( $this, 'purge_cache_on_transition_comment_status' ), 10, 3);
		add_action( 'comment_post', array( $this, 'purge_cache_on_comment_post' ), 10, 3);
	}

	/**
	 * Callback for comment status transitioned to purge URLs on approval status change.
	 *
	 * @param  int|string $new_status
	 * @param  int|string $old_status
	 * @param  object $comment
	 * @return void
	 */
	public function purge_cache_on_transition_comment_status( $new_status, $old_status, $comment ) {
		if ($new_status == 'approved' || $old_status == 'approved') {
			$this->purge($comment->comment_post_ID);
		}
	}

	/**
	 * Callback for approved post comment entered to purge URLs.
	 *
	 * @param  int $comment_ID
	 * @param  int|string $comment_approved
	 * @param  array $commentdata
	 * @return void
	 */
	public function purge_cache_on_comment_post( $comment_ID, $comment_approved, $commentdata ) {
		if ($comment_approved) {
			$this->purge($commentdata['comment_post_ID']);
		}
	}

	/**
	 * Callback for post changing events to purge URLs.
	 *
	 * @param  int $post_id Post ID.
	 * @return void
	 */
	public function purge( $post_id ) {
		if ( ! in_array( get_post_status( $post_id ), array( 'publish', 'trash' ) ) ) {
			return;
		}

		purgely_purge_surrogate_key( 'post-' . absint( $post_id ) );
	}

	/**
	 * A list of actions to purge URLs.
	 *
	 * @return array    List of actions.
	 */
	private function _purge_post_actions() {
		return array(
			'save_post',
			'deleted_post',
			'trashed_post',
			'delete_attachment',
		);
	}
}

/**
 * Instantiate or return the one Purgely_Purges instance.
 *
 * @return Purgely_Purges
 */
function get_purgely_purges_instance() {
	return Purgely_Purges::instance();
}

get_purgely_purges_instance();
