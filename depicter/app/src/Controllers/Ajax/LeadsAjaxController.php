<?php

namespace Depicter\Controllers\Ajax;

use Averta\Core\Utility\Arr;
use Depicter\Database\Entity\Lead;
use Depicter\Utility\Sanitize;
use WPEmerge\Requests\RequestInterface;

class LeadsAjaxController
{
	public function index( RequestInterface $request, $view ) {

		$args = [
			's' => Sanitize::textfield( $request->query('s', '')),
			'sources' => Sanitize::textfield( $request->query('sources', '')),
			'dateStart' => Sanitize::textfield( $request->query('dateStart', '')),
			'dateEnd' => Sanitize::textfield( $request->query('dateEnd', '')),
			'order' => Sanitize::textfield( $request->query('order', 'DESC')),
			'orderBy' => Sanitize::textfield( $request->query('orderBy', 'id')),
			'page' => Sanitize::int( $request->query('page', 1)),
			'perPage' => Sanitize::int( $request->query('perpage', 10 ) )
		];

		$response = \Depicter::lead()->get( $args );
		$statusCode = isset( $response['errors'] ) ? 400 : 200;
		return \Depicter::json( $response )->withStatus( $statusCode );
	}

	public function submit( RequestInterface $request, $view ) {

		$sourceId  = Sanitize::textfield( $request->body('_sourceId' , '') );
		$contentId = Sanitize::textfield( $request->body('_contentId', '') );
		$csrfToken = Sanitize::textfield( $request->body('_csrfToken', '') );

		if ( empty( $sourceId ) || empty( $contentId ) ) {
			return \Depicter::json([
				'errors' => [ __( 'Source ID or content ID is missing.', 'depicter' ) ]
			])->withStatus( 400 );
		}
		if ( empty( $csrfToken ) || ! wp_verify_nonce( $csrfToken, 'depicter-csrf-lead-' . $sourceId ) ) {
			return \Depicter::json([
				'errors' => [ __( 'Invalid or missing CSRF token. The request could not be completed.', 'depicter' ) ]
			])->withStatus( 400 );
		}

		try{
			$result = \Depicter::lead()->add( $sourceId, $contentId, $request );
			if ( $result['success'] ) {
				return \Depicter::json( $result )->withStatus( 200 );
			} else {
				return \Depicter::json( $result )->withStatus( 400 );
			}
		} catch( \Exception $e ){
			return \Depicter::json([ 'errors' => [ $e->getMessage() ] ])->withStatus( 400 );
		}
	}

	public function update( RequestInterface $request, $view )
	{
		$id = Sanitize::int( $request->body('id', 0 ) );
		$fields = [];

		if ( false !== $sourceId = $request->body('source_id', false ) ) {
			$fields['source_id'] = Sanitize::textfield( $sourceId );
		}

		if ( false !== $sourceType = $request->body('source_type', false) ) {
			$fields['source_type'] = Sanitize::textfield( $sourceType );
		}

		if ( empty( $id ) || empty( $fields ) ) {
			return \Depicter::json([
				'errors' => [ __( 'Both lead id and lead data are required.', 'depicter' ) ]
            ])->withStatus(400);
		}

		try{
			if ( \Depicter::leadRepository()->update( $id, $fields ) ) {
				return \Depicter::json( [ 'success' => true ] )->withStatus( 200 );
			}
		} catch( \Exception $e ){
			return \Depicter::json( [ 'errors' => [ $e->getMessage() ] ] )->withStatus( 400 );
		}

		return \Depicter::json( [ 'errors' => [ __( 'Error while updating the lead', 'depicter' ) ] ] )->withStatus( 400 );

	}

	public function delete( RequestInterface $request, $view ) {

		$id = Sanitize::int( $request->body('ID', 0 ) );
		if ( empty( $id ) ) {
			return \Depicter::json([
				'errors' => [ __( 'Lead id is required.', 'depicter' ) ]
			])->withStatus(400);
		}

		try{
			if ( \Depicter::leadRepository()->delete( $id ) ) {
				return \Depicter::json( [ 'success' => true ] )->withStatus( 200 );
			}
		} catch( \Exception $e ){
			return \Depicter::json( [ 'errors' => [ $e->getMessage() ] ] )->withStatus( 400 );
		}

		return \Depicter::json( [ 'errors' => [ __( 'Error while deleting the lead.', 'depicter' ) ] ] )->withStatus( 400 );
	}
}
