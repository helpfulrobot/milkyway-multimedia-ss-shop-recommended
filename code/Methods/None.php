<?php
/**
 * Milkyway Multimedia
 * None.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Recommended\Methods;


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