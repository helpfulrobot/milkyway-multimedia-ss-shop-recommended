<?php namespace Milkyway\SS\Shop\Recommended\Extensions;

/**
 * Milkyway Multimedia
 * HasRecommendedProducts.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class HasRecommendedProducts extends \DataExtension
{
	private static $db = [
		'Recommended_Title'  => 'Varchar',
		'Recommended_FindBy' => "Enum('None,MainCategory,OtherCategory,OtherProducts','None')",
		'Recommended_Random' => 'Boolean',
	];

	private static $many_many = [
		'Recommended_Categories' => 'ProductCategory',
		'Recommended_Products'   => 'Product',
	];

	private static $many_many_extraFields = [
		'Recommended_Products' => [
			'SortOrder' => 'Int',
			'AltTitle' => 'Varchar',
		],
	];

	private static $defaults = [
		'Recommended_Random'   => true,
	];

	public function updateCMSFields(\FieldList $fields)
	{
		$fields->addFieldsToTab('Root.Recommended', [
			\TextField::create('Recommended_Title', _t('Product.Recommended_Title', 'Title'))->setAttribute('placeholder', $this->owner->config()->recommended_title ?: _t('Product.Default-Recommended_Title', 'Recommended Products')),
			\SelectionGroup::create('Recommended_FindBy', [
				\SelectionGroup_Item::create(
					'None',
					\CompositeField::create(
						\LiteralField::create('Recommended_FindBy-None-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-None-Message', 'No recommended products will be displayed for this product') . '</p>')
					),
					_t('Product.Recommended_FindBy-None', 'None')
				),
				\SelectionGroup_Item::create(
					'MainCategory',
					\CompositeField::create(
						\LiteralField::create('Recommended_FindBy-MainCategory-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-MainCategory-Message', 'Recommended products will be pulled from this product\'s main category(ies)') . '</p>')
					),
					_t('Product.Recommended_FindBy-MainCategory', 'Main Category')
				),
				\SelectionGroup_Item::create(
					'OtherCategory',
					\CompositeField::create(
						\LiteralField::create('Recommended_FindBy-OtherCategory-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-OtherCategory-Message', 'Recommended products will be pulled from the selected categories') . '</p>'),
						\TreeMultiselectField::create('Recommended_Categories', _t('Product.Categories', 'Categories'), 'ProductCategory', 'ID', 'MenuTitle')
					),
					_t('Product.Recommended_FindBy-OtherCategory', 'Other Categories')
				),
				\SelectionGroup_Item::create(
					'OtherProducts',
					\CompositeField::create(
						\LiteralField::create('Recommended_FindBy-OtherCategory-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-OtherProducts-Message', 'Recommended products will be pulled from the selected products') . '</p>'),
						\CheckboxField::create('Recommended_Random', _t('Product.Recommended_Random', 'Display products in random order?')),
						\GridField::create('Recommended_Products', _t('Product.Products', 'Products'), $this->owner->Recommended_Products()->sort('SortOrder', 'ASC'), $gfc = \GridFieldConfig_Base::create(6)
								->addComponent(new \GridFieldButtonRow('before'), 'GridFieldToolbarHeader')
								->addComponent(new \GridFieldAddExistingSearchButton('buttons-before-left'), 'GridFieldToolbarHeader')
								->addComponent(new \GridFieldDeleteAction(true))
						)
					),
					_t('Product.Recommended_FindBy-OtherProducts', 'Other Products')
				),
			]),
		]);

		if(\ClassInfo::exists('GridFieldExtensions')) {
			$gfc->addComponent(new \GridFieldOrderableRows('SortOrder'));
			$gfc->removeComponentsByType('GridFieldDataColumns');
			$gfc->addComponent((new \GridFieldEditableColumns())->setDisplayFields([
				'AltTitle' => [
					'title' => _t('Product.TITLE', 'Title'),
					'callback' => function($record, $col, $gf) {
						return \TextField::create($col, _t('Product.TITLE', 'Title'), $record->$col)->setAttribute('placeholder', $record->Title);
					}
				],
			]));
		}
	}

	public function getRecommended() {
		$list = \ArrayList::create();

		if($this->owner->Recommended_FindBy == 'None')
			return $list;

		if($this->owner->Recommended_FindBy == 'OtherCategory' && $this->owner->Recommended_Categories()->Relation('Products')->exclude('ID', $this->owner->ID)->exists())
			$list = $this->owner->Recommended_Categories()->Relation('Products')->exclude('ID', $this->owner->ID);
		elseif($this->owner->Recommended_FindBy == 'OtherProducts' && $this->owner->Recommended_Products()->exclude('ID', $this->owner->ID)->exists())
			$list = $this->owner->Recommended_Products()->exclude('ID', $this->owner->ID)->sort('SortOrder', 'ASC');
		elseif(($categories = $this->owner->CategoryIDs) && \ProductCategory::get()->filter('ID', $this->owner->CategoryIDs)->Relation('Products')->exclude('ID', $this->owner->ID)->exists())
			$list = \ProductCategory::get()->filter('ID', $this->owner->CategoryIDs)->Relation('Products')->exclude('ID', $this->owner->ID);

		if($list && $list->exists()) {
			if($this->owner->Recommended_FindBy == 'OtherProducts' && $this->owner->Recommended_Random)
				$list = $list->sort('RAND()');
			else
				$list = $list->sort('RAND()');

			if($this->owner->Recommended_Limit)
				$list = $list->limit($this->owner->Recommended_Limit);

			foreach($list as $item) {
				if($item->AltTitle)
					$item->Title = $item->AltTitle;
			}
		}

		return $list;
	}

	public function getRecommendedTitle() {
		return $this->owner->Recommended_Title ?: $this->owner->config()->recommended_title ?: _t('Product.Default-Recommended_Title', 'Recommended Products');
	}
} 