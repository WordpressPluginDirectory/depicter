<?php
namespace Depicter\Document\Models\Traits;

use Averta\Core\Utility\JSON;

trait DocumentAdminNoticeTrait {
	use HasDocumentIdTrait;
	use EntityPropertiesTrait;

	/**
	 * @var int|null
	 */
	protected $showUnpublishedNotice = false;

	/**
	 * Gets document ID
	 *
	 * @return int|null
	 */
	public function showUnpublishedNotice() {
		return $this->showUnpublishedNotice;
	}

	/**
	 * Enables or disables unpublished notice of document
	 *
	 * @param int $showUnpublishedNotice
	 *
	 * @return mixed
	 */
	public function setUnpublishedNotice( $showUnpublishedNotice = false ) {
		$this->showUnpublishedNotice = $showUnpublishedNotice;
		return $this;
	}

	/**
	 * Render unpublished changes notice
	 *
	 * @return string
	 */
	public function getUnpublishedChangesNotice() {
		if( ! $this->showUnpublishedNotice() || ! $this->getDocumentID() ) {
			return '';
		}
		$markup = '';

		if ( $this->getEntityProperty('status') === 'draft' ) {
			$markup = \Depicter::view('admin/notices/slider-draft-notice')->with( 'view_args', [
				'isPublishedBefore' => \Depicter::documentRepository()->isPublishedBefore( $this->getDocumentID() ),
				'editUrl'           => \Depicter::editor()->getEditUrl( $this->getDocumentID() )
			])->toString();
		}

		$rule = \Depicter::metaRepository()->get( $this->getDocumentID(), 'rules', '' );
		if ( JSON::isJson( $rule ) ) {
			$rule = JSON::decode( $rule );
			if ( !empty( $rule->visibilitySchedule ) && !empty( $rule->visibilitySchedule->enable ) ) {
				$visibilityTime = $rule->visibilitySchedule;
				if ( !empty( $visibilityTime->start ) && ! \Depicter::schedule()->isDatePassed( $visibilityTime->start ) ) {
					$markup = \Depicter::view('admin/notices/slider-schedule-notice')->with( 'view_args', [
						'editUrl'           => \Depicter::editor()->getEditUrl( $this->getDocumentID() )
					])->toString();
				} else if ( !empty( $visibilityTime->end ) && \Depicter::schedule()->isDatePassed( $visibilityTime->end ) ) {
					$markup = \Depicter::view('admin/notices/slider-schedule-notice')->with( 'view_args', [
						'editUrl'           => \Depicter::editor()->getEditUrl( $this->getDocumentID() )
					])->toString();
				}
			}
		}

		return $markup;
	}

	/**
	 * Render expired subscription notice
	 *
	 * @return string
	 */
	public function getExpiredSubscriptionNotice() {
		if( ! $this->getDocumentID() ) {
			return '';
		}

		if ( ! \Depicter::auth()->isSubscriptionExpired() ) {
			return '';
		}

		$documentType = \Depicter::documentRepository()->findOne( $this->getDocumentID() )->getProperty('type');
		if ( in_array( $documentType, ['popup', 'banner-bar'] ) ) {
			return '';
		}

		$subscription_id = \Depicter::options()->get('subscription_id' , '');
		return \Depicter::view('admin/notices/subscription-expire-notice')->with( 'view_args',[
			'subscription_id' => $subscription_id
		])->toString();
	}
}
