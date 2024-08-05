<?php

namespace Depicter\Database\Repository;

use Depicter\Database\Entity\Lead;

class LeadRepository
{

	/**
	 * @var Lead
	 */
	private $lead;


	/**
	 * @throws \Exception
	 */
	public function __construct(){
		$this->lead = Lead::new();
	}

	/**
	 * @return Lead
	 *
	 * @throws \Exception
	 */
	public function lead(): Lead{
		return Lead::new();
	}

	/**
	 * Removes a lead.
	 *
	 * @param $id
	 *
	 * @return array|false|int|object|void|null
	 * @throws \Exception
	 */
	public function delete( $id )
	{
		if( $lead = $this->lead()->findById( $id ) ){
			return $lead->delete();
		} else {
			throw new \Exception("Lead with ID {$id} not found.");
		}
	}

	/**
	 * Create a lead record
	 *
	 * @param int $sourceId    Document ID
	 * @param int $contentId   The ID of form or survey
	 * @param int $contentName The Name of form or survey
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function create( $sourceId, $contentId, $contentName = '' ) {
		return $this->lead()->create([
			'source_id'    => $sourceId,
			'content_id'   => $contentId,
			'content_name' => $contentName,
			'created_at'   => $this->lead()->currentDateTime()
        ]);
	}

	/**
	 * Update a meta by relation, relation ID and meta key
	 *
	 * @param       $id
	 * @param array $fields
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function update( $id, array $fields = [] ) {
		if ( empty( $fields ) ) {
			return false;
		}

		$lead =  $this->lead()->findById( $id );

		if ( $lead && $lead->count() ){
			return $lead->first()->update($fields);
		}

		return false;
	}

	/**
	 * Get meta value by relation, relation ID and meta key
	 *
	 * @param $id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get( $id ): array{
		$lead  = $this->lead()->findById($id)->get();

		return $lead ? $lead->first()->toArray() : [];
	}

	/**
	 * Get list of leads
	 *
	 * @param array $fields
	 * @param array $args
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getList( array $fields = [], $args = [] )
	{
		$columnsName = !empty( $fields ) ? $fields : ['id', 'source_id', 'content_id', 'content_name', 'created_at'];
		$numberOfPages = '';

		if ( !empty( $args['orderBy'] )  && !empty( $args['order'] ) ) {
			$leads = $this->select( $columnsName )->orderBy( $args['orderBy'], $args['order'] );
		} else {
			$leads = $this->select( $columnsName );
		}

		if ( !empty( $args['dateStart'] ) ) {
			$leads = $leads->where( 'created_at', '>=', $args['dateStart'] );
		}

		if ( !empty( $args['dateEnd'] ) ) {
			$leads = $leads->where( 'created_at', '<=', $args['dateEnd'] );
		}

		if ( !empty( $args['s'] ) ) {
			$leads = $leads->where( 'content_name', 'like', '%' . $args['s'] . '%' );
		}

		if ( !empty( $args['sources'] ) ) {
			$leads = $leads->where( 'source_id', 'in', explode(',', $args['sources'] ) );
		}

		$numberOfLeads = $leads->findAll()->count();

		if ( !empty( $args['page'] ) && !empty( $args['perPage'] ) ) {
			$pager = $leads->paginate( $args['perPage'], $args['page'] );
			if ( $pager ) {
				$numberOfPages = $pager->getNumberOfPages();
				$leads = $pager->getResults();
			} else {
				$leads = [];
			}

		} else {
			$leads = $leads->findAll()->get();
		}


		$leads = $leads ? $leads->toArray() : [];

		if ( !empty( $numberOfPages ) ) {
			return [
				'page' => $args['page'],
				'perPage' => $args['perPage'],
				'numberOfPages' => $numberOfPages,
				'numberOfLeads' => $numberOfLeads,
				'leads' => $leads
			];
		}

		return $leads;
	}

	/**
	 * Queries records of leads with specified fields
	 *
	 * @param array $fields
	 *
	 * @return Lead
	 * @throws \Exception
	 */
	public function select( array $fields = [] )
	{
		$columnsName = !empty( $fields ) ? $fields : ['id', 'source_id', 'content_id'];
		return $this->lead()->reselect( $columnsName );
	}
}
