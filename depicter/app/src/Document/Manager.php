<?php
namespace Depicter\Document;


use Averta\WordPress\Utility\JSON;
use Depicter\Database\Repository\DocumentRepository;
use Depicter\Database\Repository\MetaRepository;
use Depicter\Document\Models\Document;
use Depicter\Exception\DocumentNoContentException;
use Depicter\Exception\EntityException;
use Depicter\Front\Preview;
use Depicter\Rules\DisplayRules;

class Manager
{

	public function bootstrap()
	{
	}

	/**
	 * Returns the instance of preview class
	 *
	 * @return Preview
	 */
	public function preview()
	{
		return \Depicter::resolve('depicter.front.document.preview');
	}

	/**
	 * Returns the instance of documentRepository class
	 *
	 * @return DocumentRepository
	 */
	public function repository()
	{
		return \Depicter::resolve('depicter.database.repository.document');
	}

	/**
	 * Returns the instance of metaRepository class
	 *
	 * @return MetaRepository
	 */
	public function meta()
	{
		return \Depicter::resolve('depicter.database.repository.meta');
	}

	/**
	 * Retrieves object of document editor data
	 *
	 * @param       $documentId
	 * @param array $where
	 *
	 * @return bool|mixed
	 * @throws EntityException
	 */
	public function getEditorData( $documentId, $where = [] ){
		if( ! $documentEditorJson = $this->repository()->getContent( $documentId, $where ) ){
			throw new DocumentNoContentException( 'No content yet.', 0, $where );
		}
		$data =  JSON::decode( $documentEditorJson, false );
		unset( $data->computedValues );
		return $data;
	}

	/**
	 * Retrieves json of document editor data
	 *
	 * @param       $documentId
	 * @param array $where
	 *
	 * @return bool|mixed
	 */
	public function getEditorRawData( $documentId, $where = [] ){
		try{
			if( ! $documentEditorJson = $this->repository()->getContent( $documentId, $where ) ){
				return '';
			}
		} catch( \Exception $e ){
			return '';
		}
		return $documentEditorJson;
	}

	/**
	 * Converts document slug or alias to document ID
	 *
	 * @param string|array $documentIDs  A document ID/Slug/Alias or list of them
	 *
	 * @return mixed
	 */
	public function getID( $documentIDs ){
		if( empty( $documentIDs ) ){
			return false;
		}

		try{
			if( is_array( $documentIDs ) ){
				$realDocumentIDs = [];
				foreach( $documentIDs as $documentID ){
					$realDocumentIDs = $this->getID( $documentID );
				}
				return $realDocumentIDs;

			} elseif ( ! is_numeric( $documentIDs ) && is_string( $documentIDs ) ) {
				if ( $document = \Depicter::document()->repository()->findOne( null, ['slug' => $documentIDs] ) ) {
					return $document->getID();
				}
			} else {
				return $documentIDs;
			}
		} catch( \Exception $e ){
			return false;
		}

		return false;
	}

	/**
	 * Get a document model by ID
	 *
	 * @param       $documentId
	 * @param array $where
	 *
	 * @return bool|Document
	 * @throws EntityException
	 * @throws \JsonMapper_Exception
	 */
	public function getModel( $documentId, $where = [] ){
		$startSection = 0;

		if( array_key_exists( 'start', $where ) ){
			$startSection = (int) $where['start'];
			unset( $where['start'] );
		}

		if ( ! $editorDataArray = $this->getEditorData( $documentId, $where ) ) {
			return false;
		}

		$editorDataArray = \Depicter::document()->migrations()->apply( $editorDataArray );

		$editorDataArray->startSection = $startSection;

		// make document model base on editor data
		$mapper = new Mapper();
		$documentModel = $mapper->hydrate( $editorDataArray, $documentId )->get();

		// set document entity properties
		$documentModel->setDocumentId( $documentId );
		$documentModel->setShowAdminNotice( $documentId );
		$documentModel->setEntityProperty( 'status', $this->getStatus( $documentId ) );

		return $documentModel;
	}

	/**
	 * Retrieves custom css file of a document if exists
	 *
	 * @param int   $documentId
	 * @param array $where
	 *
	 * @return bool|string
	 */
	public function getCssFileUrl( $documentId, $where = [] ){
		try{
			if( $document = $this->getModel( $documentId, $where = [] ) ){
				if( $cssFile = $document->styleGenerator()->getCssFileUrl() ){
					return $cssFile;
				}
			}
		} catch( \Exception $e ){}

		return false;
	}


	/**
	 * Cache custom styles for a document
	 *
	 * @param $documentId
	 *
	 * @return void
	 */
	public function cacheCustomStyles( $documentId )
	{
		if( ! \Depicter::cache( 'document' )->get( $documentId . '_css_files' ) && ! \Depicter::authorization()->currentUserCanPublishDocument() ){
			$where  = [ 'status' => 'publish' ];
			try {
				if( $documentModel = $this->getModel( $documentId, $where ) ){

					$documentModel->prepare()->render();
					$documentModel->styleGenerator();
					$documentModel->saveCss();

					$cssLinksToEnqueue = $documentModel->getCustomCssFiles( 'all' );

					\Depicter::cache('document')->set( $documentId . '_css_files', $cssLinksToEnqueue, WEEK_IN_SECONDS );
				}
			} catch( EntityException|\JsonMapper_Exception $e ){
			}
		}
	}

	/**
	 * Get status of document
	 *
	 * @param int $documentID
	 * @return string
	 */
	public function getStatus( $documentID ) {
		return $this->repository()->getStatus( $documentID );
	}

	/**
	 * Get displayRules for a document
	 *
	 * @param int $documentID
	 *
	 * @return DisplayRules
	 */
	public function displayRules( $documentID ){
		return new DisplayRules( $documentID );
	}


	/**
	 * Retrieves IDs of all conditional documents
	 *
	 * @return array
	 */
	public function getConditionalDocumentIDs( $force_flush = false ){

		$conditionalDocumentIDs = \Depicter::cache('base')->get( '_conditional_document_ids' );

		if( ! $force_flush && ( false !== $conditionalDocumentIDs ) ){
			return $conditionalDocumentIDs;
		}

		$conditionalDocumentIDs = $this->repository()->getConditionalDocumentIDs();

		\Depicter::cache('base')->set( '_conditional_document_ids', $conditionalDocumentIDs, HOUR_IN_SECONDS );

		return $conditionalDocumentIDs;
	}

	/**
	 * do migration related to document Data
	 *
	 * @return \Depicter\Document\Migrations\DocumentMigration
	 */
	public function migrations() {
		return \Depicter::documentMigration();
	}
}
