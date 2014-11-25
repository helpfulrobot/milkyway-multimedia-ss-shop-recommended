<?php
/**
 * Milkyway Multimedia
 * OtherProducts.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Recommended\Methods;


class OtherProducts implements Contract {
	public function title($buyable) {
		return 'Selected Products';
	}

	public function getFormFields($buyable) {
		$fields = \CompositeField::create(
			\LiteralField::create('Recommended_FindBy-OtherCategory-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-OtherProducts-Message', 'Recommended products will be pulled from the selected products') . '</p>'),
			\CheckboxField::create('Recommended_Random', _t('Product.Recommended_Random', 'Display products in random order?')),
			\GridField::create('Recommended_Products', _t('Product.Products', 'Products'), $buyable->Recommended_Products()->sort('SortOrder', 'ASC'), $gfc = \GridFieldConfig_Base::create($buyable->config()->recommended_limit)
					->addComponent(new \GridFieldButtonRow('before'), 'GridFieldToolbarHeader')
					->addComponent(new \GridFieldAddExistingAutocompleter('buttons-before-left', ['Title:PartialMatch', 'InternalItemID', 'Model:PartialMatch']), 'GridFieldToolbarHeader')
					->addComponent(new \GridFieldDeleteAction(true))
			)
		);

		if(\ClassInfo::exists('GridFieldExtensions')) {
			$gfc->removeComponentsByType('GridFieldDataColumns');
			$gfc->removeComponentsByType('GridFieldAddExistingAutocompleter');

			$gfc->addComponent(new \GridFieldOrderableRows('SortOrder'));
			$gfc->addComponent(new \GridFieldAddExistingSearchButton('buttons-before-left'), 'GridFieldToolbarHeader');
			$gfc->addComponent((new \GridFieldEditableColumns())->setDisplayFields([
				'AltTitle' => [
					'title' => _t('Product.TITLE', 'Title'),
					'callback' => function($record, $col, $gf) {
							return \TextField::create($col, _t('Product.TITLE', 'Title'), $record->$col)->setAttribute('placeholder', $record->Title);
						}
				],
			]), 'GridFieldDeleteAction');
		}

		return $fields;
	}

	public function getList($buyable, $limit = 4) {
		if(!$buyable->Recommended_Products()->exclude('ID', $buyable->ID)->exists())
			return null;

		$list = $buyable->Recommended_Products()->exclude('ID', $buyable->ID)->sort('SortOrder', 'ASC');

		if($buyable->Recommended_AlsoBought) {
			// Too complex to form this into one query at the moment due to versioned...
			$list = $buyable->get()->filter('ID', $list->column('ID'));
		}

		return $list;
	}
} 