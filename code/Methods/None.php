<?php namespace Milkyway\SS\Shop\Recommended\Methods;
/**
 * Milkyway Multimedia
 * None.php
 *
 * @package milkyway-multimedia/ss-shop-recommended
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class None implements Contract {
	public function title($buyable) {
		return 'None';
	}

	public function getFormFields($buyable) {
		return \CompositeField::create(
			\LiteralField::create('Recommended_FindBy-None-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-None-Message', 'No recommended products will be displayed for this product') . '</p>')
		);
	}

	public function getList($buyable, $limit = 4) {
		return \ArrayList::create();
	}
} 