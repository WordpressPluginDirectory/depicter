<?php
namespace Depicter\Document\Models\Common;


use Depicter\Document\CSS\Breakpoints;
use Depicter\Document\Helper\Helper;
use Depicter\Document\Models\Common\Styles\Transition;
use Depicter\Document\Models\Traits\HasDataSheetTrait;

class Styles
{
	use HasDataSheetTrait;

	/**
	 * @var Styles\Transform
	 */
	public $transform;

	/**
	 * @var Styles\Opacity
	 */
	public $opacity;

	/**
	 * @var Styles\BoxShadow
	 */
	public $boxShadow;

	/**
	 * @var Styles\BackgroundBlur
	 */
	public $backgroundBlur;

	/**
	 * @var Styles\BackgroundColor
	 */
	public $backgroundColor;

	/**
	 * @var Styles\Border
	 */
	public $border;

	/**
	 * @var Styles\Corner
	 */
	public $corner;

	/**
	 * @var Styles\Filter
	 */
	public $filter;

	/**
	 * @var Styles\TextShadow
	 */
	public $textShadow;

	/**
	 * @var Styles\Margin
	 */
	public $margin;

	/**
	 * @var Styles\Padding
	 */
	public $padding;

	/**
	 * @var Styles\BlendingMode
	 */
	public $blendingMode;

	/**
	 * @var Styles\Typography
	 */
	public $typography;

	/**
	 * @var Styles\Transition
	 */
	public $transition;

	/**
	 * @var Styles\Svg
	 */
	public $svg;

	/**
	 * @var Styles\Hover
	 */
	public $hover;

	/**
	 * @var Styles\Flex
	 */
	public $flex;

	/**
	 * @var Styles\Width
	 */
	public $width;

	/**
	 * @var Styles\Height
	 */
	public $height;


	/**
	 * Retrieves list of fonts used in typography options
	 *
	 * @return array
	 */
	public function getFontsList()
	{
		if( !empty( $this->typography ) ){
			return $this->typography->getFontsList();
		}

		return [];
	}

	/**
	 * Retrieves styles for all available modules
	 *
	 * @param array $states The states to generate style for
	 *
	 * @return array
	 */
	public function getGeneralCss( $states = 'all' ) {
		$cssModules = [
			'width',
			'height',
			'filter',
			'textShadow',
			'opacity',
			'padding',
			'typography',
			'boxShadow',
			'transform',
			'backgroundBlur',
			'backgroundColor',
			'border',
			'corner',
			'margin',
			'flex'
		];

		$stylesList = $this->generateCssForModules( $cssModules, $states );
		$stylesList = $this->replaceDynamicTags( $stylesList );

		return $stylesList;
	}

	protected function replaceDynamicTags( $stylesList ){
		if( ! $dataSheet = $this->getDataSheet() ){
			return $stylesList;
		}

		foreach( $stylesList as $key => $value ){
			if( empty( $value) ){
				continue;
			}
			if( is_string( $value ) ){
				$stylesList[ $key ] = \Depicter::dataSource()->tagsManager()->convert( $value, $dataSheet );
			} elseif( is_array( $value ) ) {
				$stylesList[ $key ] = $this->replaceDynamicTags( $value );
			}
		}

		return $stylesList;
	}

	/**
	 * Get styles for SVG
	 *
	 * @return array
	 */
	public function getSvgCss() {
		return $this->generateCssForModules( [ 'svg' ] );
	}

	/**
	 * Get transition CSS
	 *
	 * @return array
	 */
	public function getTransitionCss() {
		if ( empty( $this->hover ) ) {
			return [];
		}

		$css = $this->getInitialCssVariable();
		/**
		 * While transition is defined in 'hover' property, we need to consider an exception to generate
		 * transition style for normal state not :hover
		 **/
		$this->hover->transition = !empty( $this->hover->transition ) ? $this->hover->transition : new Transition();
		$this->hover->transition->setHoverStatus( $this->hover->enable ?? [] );

		return $this->hover->transition->set( $css );
	}

	/**
	 * Collects and retrieves responsive styles from css modules
	 *
	 * @param array|string $cssModules
	 * @param string       $states     The states to generate style for
	 *
	 * @return array
	 */
	protected function generateCssForModules( $cssModules = [], $states = 'all' ){
		$knownStates = ['normal', 'hover'];

		// translate $states if string or null passed
		if( is_string( $states ) ){
			if( $states === 'all' ){
				$states = $knownStates;
			} elseif ( in_array( $states, $knownStates ) ){
				$states = (array) $states;
			}
		}
		if( is_null( $states ) ){
			$states = $knownStates;
		}

		$css = $this->getInitialCssVariable();
		$css['hover'] = $css;

		// Collect styles from modules
		foreach ( $cssModules as $cssModule ) {
			if( in_array( 'normal', $states ) ){
				$css = ! empty( $this->{$cssModule} ) ? $this->{$cssModule}->set( $css ) : $css;
			}
			if( in_array( 'hover', $states ) ){
				if( !empty( $this->hover->{$cssModule} ) ){
					// pass hover enabled breakpoints to the style class. The style class will be aware of normal or hover state
					if( method_exists( $this->hover->{$cssModule}, 'setHoverStatus' ) ){
						$this->hover->{$cssModule}->setHoverStatus( $this->hover->enable ?? [] );
					}
					$css['hover'] = $this->hover->{$cssModule}->set( $css['hover'] );
				}
			}
		}

		// check if hover style is disabled for one breakpoint then remove all the css modules for that breakpoint
		if ( in_array( 'hover', $states ) ) {
			$devices = Breakpoints::names();
			foreach ( $devices as $device ) {
				if ( ! Helper::isHoverStyleEnabled( $this->hover, $device ) ) {
					$css['hover'][ $device ] = [];
				}
			}
		}

		return $css;
	}

	/**
	 * Collects and retrieves responsive styles from css modules for a single state
	 *
	 * @param array|string $cssModules
	 * @param string       $state       The state to generate style for
	 *
	 * @return array
	 */
	public function generateCssForModulesOfState( $cssModules = [], $state = 'normal' ){

		$css = $this->getInitialCssVariable();

		// Collect styles from modules
		foreach ( $cssModules as $cssModule ) {
			if( 'normal' === $state ){
				$css = ! empty( $this->{$cssModule} ) ? $this->{$cssModule}->set( $css ) : $css;

			} elseif( 'hover' === $state ){
				if( ! empty( $this->hover->{$cssModule} ) ){
					// pass hover enabled breakpoints to the style class. The style class will be aware of normal or hover state
					if( method_exists( $this->hover->{$cssModule}, 'setHoverStatus' ) ){
						$this->hover->{$cssModule}->setHoverStatus( $this->hover->enable ?? [] );
					}
					$css = $this->hover->{$cssModule}->set( $css );
				}
			}
		}

		// check if hover style is disabled for one breakpoint then remove all the css modules for that breakpoint
		if ( 'hover' === $state ) {
			$devices = Breakpoints::names();

			foreach ( $devices as $device ) {
				if ( ! Helper::isHoverStyleEnabled( $this->hover, $device ) ) {
					$css[ $device ] = [];
				}
			}
		}

		return $css;
	}

	/**
	 * Retrieves a structured list for normal and hover states and breakpoints
	 *
	 * @return array
	 */
	protected function getInitialCssVariable(){
		$css = [];
		$devices = Breakpoints::names();
		foreach ( $devices as $device ) {
			$css[ $device ] = [];
		}

		return $css;
	}

	/**
	 * Get blending mode styles
	 *
	 * @return array
	 */
	public function getBlendingModeStyle(): array{
		return $this->generateCssForModules(['blendingMode']);
	}

	/**
	 * Get align self flex styles
	 *
	 * @return array
	 */
	public function getFlexAlignStyle(): array{
		$css = $this->generateCssForModules(['flex']);
		$devices = Breakpoints::names();
		// We implement this functionality to prevent additional flex styles defined in component elements from being applied to the frame element when the component is a child of a group element.
		foreach ( $devices as $device ){
			foreach( $css[ $device ] as $cssProperty => $value ){
				if ( 'align-self' != $cssProperty ){
					unset( $css[ $device ][ $cssProperty ] );
				}
			}
		}

		return $css;
	}

}
