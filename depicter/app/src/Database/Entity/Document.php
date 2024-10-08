<?php
namespace Depicter\Database\Entity;

use Averta\WordPress\Database\Entity\Model;
use Averta\WordPress\Utility\Sanitize;

class Document extends Model
{
	protected $idColumn = 'id';

	/**
	 * Resource name.
	 *
	 * @var string
	 */
	protected $resource = 'depicter_documents';

	protected $routeResource = 'depicter-documents';

	/**
	 * Determines what fields can be saved without be explicitly.
	 *
	 * @var array
	 */
	protected $builtin = [
        'id',
		'name',
        'slug',
        'type',
		'author',
		'sections_count',
		'created_at',
        'modified_at',
		'thumbnail',
		'content',
		'status',
		'parent',
		'password'
    ];

	protected $guard = [
        'id'
    ];

	protected $private = [
		'password'
    ];

	protected $cast = [];

	protected $format = [
        'name'         => 'sanitizeName',
        'slug'         => 'sanitizeSlug',
        'modified_at'  => 'currentDateTime'
    ];

	/**
	 * Determines what fields should be updated automatically.
	 *
	 * @var array
	 */
	protected $autoFill = [
        'modified_at' => ''
    ];


	public function sanitizeName( $value ) {
		return Sanitize::plaintext( $value );
    }

    public function sanitizeSlug( $value ) {
        $value = $value ?? 'document 1';
		return Sanitize::slug( $value );
    }

    public function currentDateTime( $value ) {
        return gmdate('Y-m-d H:i:s', time());
    }

	/**
	 * Renames the document
	 *
	 * @param string $newName
	 *
	 * @return string|int|null
	 */
    public function rename( $newName )
    {
        return $this->save( ['name' => $newName] );
    }

	/**
	 * Changes the document slug
	 *
	 * @param string $newSlug
	 *
	 * @return string|int|null
	 */
    public function changeSlug( $newSlug )
    {
        return $this->save( ['slug' => Sanitize::slug( $newSlug )] );
    }

	/**
     * Get User ID
     *
     * @return string|int|null
     */
    public function getUserID()
    {
        return $this->properties['author'] ?? null;
    }

    /**
     * Get parent ID
     *
     * @return string|int|null
     */
    public function parent()
    {
        return $this->properties['parent'] ?? null;
    }

    /**
     * Author ID
     *
     * @return int
     */
    public function author()
    {
        return $this->properties['author'] ?? 0;
    }

    /**
     * @param mixed|null $value
     *
     * @return bool
     */
    public function getIsPublishedProperty($value = null)
    {
        return (bool) ( $value ?? $this->status == 'publish' );
    }

    /**
     * Draft
     *
     * @return $this
     */
    public function draft()
    {
        return $this->where('status', 'draft');
    }

    /**
     * Published
     *
     * @return $this
     */
    public function published()
    {
        return $this->where('status', 'publish');
    }

    /**
     * Editor data
     *
     * @return $this
     */
    public function content()
    {
    	$properties = $this->getProperties();

    	if( isset( $properties['content'] ) ){
    		return $properties['content'];
		}
        return null;
    }

    /**
     * Status
     *
     * @param string $type
     *
     * @return $this
     */
    public function status($type)
    {
        return $this->where('status', $type);
    }

	/**
     * Query parent or revision documents
     *
     * @param string $revisionOrParent Get revision or parent documents only
     *
     * @return $this
     */
    public function parents( $revisionOrParent = 0 )
    {
        return $this->where('parent', $revisionOrParent );
    }

	/**
	 * Get public properties for API
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getApiProperties()
	{
		$properties = $this->getProperties();

		unset( $properties['parent']   );
		unset( $properties['password'] );

		$properties['publishedAt'] = $this->getLastPublishedAt();
		$properties['thumbnail'] = '';

		$uploadDir = wp_upload_dir();
		$thumbnailsBaseDir = $uploadDir['basedir'] . '/depicter/preview-images/';
		$thumbnailsBaseURL = $uploadDir['baseurl'] . '/depicter/preview-images/';

		$properties['previewImage' ] = is_file( $thumbnailsBaseDir . $this->getID() . '.png' ) ? $thumbnailsBaseURL . $this->getID() . '.png' : '';
		$properties['sectionThumbnails'] = [];
		$i = 1;
		while(1){
			$fileName = $this->getID() . '-'. $i . '.png';
		    if( ! is_file( $thumbnailsBaseDir . $fileName ) ){
		        break;
			}
			$properties['sectionThumbnails'][] = $thumbnailsBaseURL . $fileName;
			$i++;
		}

		return $properties;
    }

	/**
	 * Get latest publish date time for document
	 *
	 * @return mixed
	 * @throws \Exception
	 */
    public function getLastPublishedAt(){
		return \Depicter::documentRepository()->getLastPublishedAt( $this->toArray() );
	}

	/**
	 * Joins meta table and reselects default or passed columns
	 *
	 * @return Document
	 */
	public function withMeta() {
		global $wpdb;
		return $this->join(
			$wpdb->prefix . 'depicter_meta',
			$wpdb->prefix . 'depicter_meta.relation_id',
			$wpdb->prefix . $this->resource . '.' . $this->idColumn
		)->where($wpdb->prefix . 'depicter_meta.relation', 'document' );
	}

	/**
	 * Filters meta key and value
	 *
	 * @throws Document
	 */
	public function whereMeta( $metaKey, $metaValue ){
		global $wpdb;
		return $this
			->where($wpdb->prefix . 'depicter_meta.meta_key', $metaKey )
            ->where($wpdb->prefix . 'depicter_meta.meta_value', $metaValue );
	}

	/**
	 * Retrieves related meta field models
	 *
	 * @return Meta|\TypeRocket\Models\Model|null
	 */
	public function meta(){
        return $this->hasMany(Meta::class, 'relation_id', $this->idColumn );
    }
}
