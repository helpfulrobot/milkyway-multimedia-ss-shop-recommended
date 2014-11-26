<?php namespace Milkyway\SS\Shop\Recommended\Methods;

/**
 * Milkyway Multimedia
 * OtherCategories.php
 *
 * @package milkyway-multimedia/ss-shop-recommended
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class OtherCategories implements Contract {
	public function title($buyable) {
		return 'Other Categories';
	}

	public function getFormFields($buyable) {
		return \CompositeField::create(
			\LiteralField::create('Recommended_FindBy-OtherCategory-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-OtherCategory-Message', 'Recommended products will be pulled from the selected categories') . '</p>'),
			\TreeMultiselectField::create('Recommended_Categories', _t('Product.Categories', 'Categories'), 'ProductCategory', 'ID', 'MenuTitle')
		);
	}

	public function getList($buyable, $limit = 4) {
		if(!$buyable->get()->filter('ParentID', $buyable->Recommended_Categories()->column('ID'))->exclude('ID', $buyable->ID)->exists())
			return null;

		return $buyable->get()->filter('ParentID', $buyable->Recommended_Categories()->column('ID'))->exclude('ID', $buyable->ID);
	}
} 