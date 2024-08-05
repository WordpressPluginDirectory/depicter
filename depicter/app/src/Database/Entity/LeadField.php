<?php
namespace Depicter\Database\Entity;

use Averta\WordPress\Database\Entity\Model;

class LeadField extends Model
{
	/**
	 * Resource name.
	 *
	 * @var string
	 */
	protected $resource = 'depicter_lead_fields';

	/**
	 * Determines what fields can be saved without be explicitly.
	 *
	 * @var array
	 */
	protected $builtin = [
		'lead_id',
		'field_id',
		'field_type',
		'field_value',
		'created_at',
		'updated_at'
	];

	protected $guard = [ 'id' ];

	protected $format = [
		'created_at'  => 'currentDateTime',
		'updated_at'  => 'currentDateTime'
	];

	public function currentDateTime() {
        return gmdate('Y-m-d H:i:s', time());
    }
}
