<?php
namespace Depicter\Database\Entity;

use Averta\WordPress\Database\Entity\Model;

class Lead extends Model
{
	/**
	 * Resource name.
	 *
	 * @var string
	 */
	protected $resource = 'depicter_leads';

	/**
	 * Determines what fields can be saved without be explicitly.
	 *
	 * @var array
	 */
	protected $builtin = [
		'source_id',
		'content_id',
		'content_name',
		'created_at'
	];

	protected $guard = [ 'id' ];

	protected $format = [
		'created_at'  => 'currentDateTime'
	];

	public function currentDateTime() {
        return gmdate('Y-m-d H:i:s', time());
    }
}
