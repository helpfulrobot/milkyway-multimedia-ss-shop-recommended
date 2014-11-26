<?php namespace Milkyway\SS\Shop\Recommended\Methods;
/**
 * Milkyway Multimedia
 * MainCategories.php
 *
 * @package milkyway-multimedia/ss-shop-recommended
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class MainCategories implements Contract {
	public function title($buyable) {
		return 'Main Category';
	}

	public function getFormFields($buyable) {
		return \CompositeField::create(
			\LiteralField::create('Recommended_FindBy-MainCategory-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-MainCategory-Message', 'Recommended products will be pulled from this product\'s main category(ies)') . '</p>')
		);
	}

	public function getList($buyable, $limit = 4) {
		if(($categories = $buyable->CategoryIDs) && $buyable->get()->filter('ParentID', $categories)->exclude('ID', $buyable->ID)->exists()) {
			$list = $buyable->get()->exclude('ID', $buyable->ID);
			$any = [
				'ParentID' => $categories,
			];

			// Go through the Product Categories attached via many many to buyable and add them as well
			if($component = $buyable->many_many('ProductCategories')) {
				list($parentClass, $componentClass, $parentField, $componentField, $relationTable) = $component;
				$baseClass = \ClassInfo::baseDataClass($buyable);

				// Join ProductCategories_Products table to find any products that also belong to these categories
				$list = $list->leftJoin($relationTable, "\"$relationTable\".\"$parentField\" = \"$baseClass\".\"ID\"");
				$any["$relationTable.$componentField"] = $categories;
			}

			return $list->filterAny($any);
		}

		return null;
	}
} 