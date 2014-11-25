<?php namespace Milkyway\SS\Shop\Recommended\Extensions;

/**
 * Milkyway Multimedia
 * HasRecommendedProducts.php
 *
 * @package milkyway-multimedia/ss-shop-recommended
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class HasRecommendedProducts extends \DataExtension
{
	private static $db = [
		'Recommended_Title'  => 'Varchar',
		'Recommended_FindBy' => "Enum('None,MainCategory,OtherCategory,OtherProducts','None')",
		'Recommended_Random' => 'Boolean',
		'Recommended_AlsoBought' => 'Boolean',
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

	private static $recommended_limit = 4;

	public function updateCMSFields(\FieldList $fields)
	{
		$fields->addFieldsToTab('Root.Recommended', [
			\TextField::create('Recommended_Title', _t('Product.Recommended_Title', 'Title'))
				->setAttribute('placeholder', $this->owner->config()->recommended_title ?: _t('Product.Default-Recommended_Title', 'Recommended Products')),
			\CheckboxField::create('Recommended_AlsoBought', _t('Product.Recommended_AlsoBought', 'Prioritise products that were bought with this product?'))
				->setDescription(_t('Product.Desc-Recommended_AlsoBought', 'This will used products that previous customers have bought with this product, otherwise it will select from your choice below.')),
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
					_t('Product.Recommended_FindBy-MainCategory', 'Main Category(ies)')
				),
				\SelectionGroup_Item::create(
					'OtherCategory',
					\CompositeField::create(
						\LiteralField::create('Recommended_FindBy-OtherCategory-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-OtherCategory-Message', 'Recommended products will be pulled from the selected categories') . '</p>'),
						\TreeMultiselectField::create('Recommended_Categories', _t('Product.Categories', 'Categories'), 'ProductCategory', 'ID', 'MenuTitle')
					),
					_t('Product.Recommended_FindBy-OtherCategory', 'Selected Category(ies)')
				),
				\SelectionGroup_Item::create(
					'OtherProducts',
					\CompositeField::create(
						\LiteralField::create('Recommended_FindBy-OtherCategory-Message', '<p class="message field desc selectionGroup-desc">' . _t('Product.Recommended_FindBy-OtherProducts-Message', 'Recommended products will be pulled from the selected products') . '</p>'),
						\CheckboxField::create('Recommended_Random', _t('Product.Recommended_Random', 'Display products in random order?')),
						\GridField::create('Recommended_Products', _t('Product.Products', 'Products'), $this->owner->Recommended_Products()->sort('SortOrder', 'ASC'), $gfc = \GridFieldConfig_Base::create($this->owner->config()->recommended_limit)
								->addComponent(new \GridFieldButtonRow('before'), 'GridFieldToolbarHeader')
								->addComponent(new \GridFieldAddExistingAutocompleter('buttons-before-left', ['Title:PartialMatch', 'InternalItemID', 'Model:PartialMatch']), 'GridFieldToolbarHeader')
								->addComponent(new \GridFieldDeleteAction(true))
						)
					),
					_t('Product.Recommended_FindBy-OtherProducts', 'Selected Product(s)')
				),
			]),
		]);

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
	}

	public function getRecommended() {
		if($this->owner->Recommended_FindBy == 'None') {
			return $this->owner->Recommended_AlsoBought ? $this->owner->CustomersAlsoBought() : \ArrayList::create();
		}

		$list = null;
		$limit = $this->owner->config()->recommended_limit;

		if($this->owner->Recommended_FindBy == 'OtherCategory' && $this->owner->get()->filter('ParentID', $this->owner->Recommended_Categories()->column('ID'))->exclude('ID', $this->owner->ID)->exists())
			$list = $this->owner->get()->filter('ParentID', $this->owner->Recommended_Categories()->column('ID'))->exclude('ID', $this->owner->ID);
		elseif($this->owner->Recommended_FindBy == 'OtherProducts' && $this->owner->Recommended_Products()->exclude('ID', $this->owner->ID)->exists()) {
			$list = $this->owner->Recommended_Products()->exclude('ID', $this->owner->ID)->sort('SortOrder', 'ASC');

			if($this->owner->Recommended_AlsoBought) {
				// Too complex to form this into one query at the moment due to versioned...
				$list = $this->owner->get()->filter('ID', $list->column('ID'));
			}
		}
		elseif(($categories = $this->owner->CategoryIDs) && $this->owner->get()->filter('ParentID', $categories)->exclude('ID', $this->owner->ID)->exists()) {
			$list = $this->owner->get()->exclude('ID', $this->owner->ID);
			$any = [
				'ParentID' => $categories,
			];

			// Go through the Product Categories attached via many many to buyable and add them as well
			if($component = $this->owner->many_many('ProductCategories')) {
				list($parentClass, $componentClass, $parentField, $componentField, $relationTable) = $component;
				$baseClass = \ClassInfo::baseDataClass($this->owner);

				// Join ProductCategories_Products table to find any products that also belong to these categories
				$list = $list->leftJoin($relationTable, "\"$relationTable\".\"$parentField\" = \"$baseClass\".\"ID\"");
				$any["$relationTable.$componentField"] = $categories;
			}

			$list = $list->filterAny($any);
		}

		// This will use the also bought, and set it as the priority items if set via CMS
		if($this->owner->Recommended_AlsoBought && ($alsoBought = $this->owner->CustomersAlsoBought()) && $alsoBought instanceof \DataList) {
			$alsoBought = $alsoBought->sort('RAND()');
			$list = $list->sort('RAND()');

			if($limit) {
				$alsoBought = $alsoBought->limit($limit);
				$list = $list->limit($limit);
			}

			$queries[] = $this->removeOrderByFromQuery($alsoBought)->sql();
			$queries[] = $this->removeOrderByFromQuery($list)->sql();

			$results = \DB::query(implode(' UNION ', $queries));
			$records = [];

			foreach($results as $record) {
				$records[] = \Object::create($list->dataClass(), $record);
			}

			$list = \ArrayList::create($records);
		}

		if($list && $list->exists()) {
			if(!$this->owner->Recommended_AlsoBought) {
				$list = $this->randomiseList($list);
			}

			if($limit)
				$list = $list->limit($limit);

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

	/**
	 * Get the products customers also bought
	 * (Haha so many sub queries...)
	 *
	 * @return \SS_List
	 */
	public function CustomersAlsoBought() {
		if(($orderItemClass = $this->owner->config()->order_item) && ($orderItem = singleton($orderItemClass)) && ($buyableRel = $orderItem->owner->config()->buyable_relationship)) {
			$buyableRel = $buyableRel . 'ID';
			$baseClass = \ClassInfo::baseDataClass($this->owner);

			// Had to use EXISTS because IN () not compatible with SS DataModel
			return $this->owner->get()->where(
				'EXISTS(' .
				\DataList::create($orderItemClass)
					->where(
					'EXISTS(' .
					str_replace(['FROM "OrderAttribute"', '"OrderAttribute".', 'OrderAttribut||e'], ['FROM "OrderAttribute" AS "OrderAttribute1"', '"OrderAttribute1".', 'OrderAttribute'],
						\DataList::create($orderItemClass)
							->leftJoin('Order', "\"OrderAttribute\".\"OrderID\" = \"Order\".\"ID\"")
							->where("\"$buyableRel\" = {$this->owner->ID}")
							->where('"Order"."Status" != \'Cart\'')
							->where('"OrderAttribute1"."OrderID" = "OrderAttribut||e"."OrderID"')
							->dataQuery()->getFinalisedQuery(['"OrderAttribute"."OrderID"'])->sql())
					. ')')
					->where("\"$buyableRel\" != " . $this->owner->ID)
					->where("\"$baseClass\".\"ID\" = \"$orderItemClass\".\"$buyableRel\"")
					->dataQuery()->getFinalisedQuery([$buyableRel])->sql()
				. ')'
			);
		}

		return \ArrayList::create();
	}

	protected function randomiseList($list) {
		if ($this->owner->Recommended_FindBy == 'OtherProducts') {
			if ($this->owner->Recommended_Random)
				$list = $list->sort('RAND()');
		} else
			$list = $list->sort('RAND()');

		return $list;
	}

	protected function removeOrderByFromQuery($list) {
		$query = $list->dataQuery()->query();
		$query->setOrderBy([]);
		return $query;
	}
} 