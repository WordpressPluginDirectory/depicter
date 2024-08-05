<?php

namespace Depicter\Database\Repository;

use Depicter\Database\Entity\LeadField;

class LeadFieldRepository
{

	/**
	 * @var LeadField
	 */
	private $leadField;


	/**
	 * @throws \Exception
	 */
	public function __construct(){
		$this->leadField = LeadField::new();
	}

	/**
	 * @return LeadField
	 *
	 * @throws \Exception
	 */
	public function leadField(): LeadField{
		return LeadField::new();
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
		if( $leadField = $this->leadField()->findById( $id ) ){
			return $leadField->delete();
		}
	}

	/**
	 * Create a lead record
	 *
	 * @param int $leadId
	 * @param string $fieldName
	 * @param string $fieldValue
	 * @param string $fieldType
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function create( $leadId, $fieldName, $fieldValue, $fieldType = '' ) {
		return $this->leadField()->create([
			'lead_id' => $leadId,
			'name' => $fieldName,
			'value' => $fieldValue,
			'type' => $fieldType,
			'created_at' => $this->leadField()->currentDateTime(),
			'updated_at' => $this->leadField()->currentDateTime()
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

		$leadField =  $this->leadField()->findById( $id );

		if ( $leadField && $leadField->count() ){
			return $leadField->first()->update($fields);
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
		$leadField  = $this->leadField()->findById($id)->get();

		return $leadField ? $leadField->first()->toArray() : [];
	}

	/**
	 * Queries records of leads with specified fields
	 *
	 * @param array $fields
	 *
	 * @return LeadField
	 * @throws \Exception
	 */
	public function select( array $fields = [] )
	{
		$columnsName = !empty( $fields ) ? $fields : ['id', 'lead_id' ,'name', 'type', 'value', 'created_at', 'updated_at'];
		return $this->leadField()->reselect( $columnsName );
	}
}
