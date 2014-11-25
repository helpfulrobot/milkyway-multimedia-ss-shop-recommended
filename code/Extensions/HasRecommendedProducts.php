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
		'Recommended_FindBy' => 'Varchar',
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

	private static $recommended_findBy_methods =  [
		'Milkyway\SS\Shop\Recommended\Methods\None' => 'None',
		'Milkyway\SS\Shop\Recommended\Methods\MainCategories' => 'MainCategory',
		'Milkyway\SS\Shop\Recommended\Methods\OtherCategories' => 'OtherCategory',
		'Milkyway\SS\Shop\Recommended\Methods\OtherProducts' => 'OtherProducts',
	];

	private static $recommended_findBy_methods_default = 'Milkyway\SS\Shop\Recommended\Methods\None';

	private static $recommended_limit = 4;

	public function updateCMSFields(\FieldList $fields)
	{
		$fields->addFieldsToTab('Root.Recommended', [
			\TextField::create('Recommended_Title', _t('Product.Recommended_Title', 'Title'))
				->setAttribute('placeholder', $this->owner->config()->recommended_title ?: _t('Product.Default-Recommended_Title', 'Recommended Products')),
			\CheckboxField::create('Recommended_AlsoBought', _t('Product.Recommended_AlsoBought', 'Prioritise products that were bought with this product?'))
				->setDescription(_t('Product.Desc-Recommended_AlsoBought', 'This will use products that previous customers have bought with this product, otherwise it will select from your choice below.')),
			\SelectionGroup::create('Recommended_FindBy', $this->getMethodFormFields()),
		]);
	}

	public function getRecommended() {
		$method = array_search($this->owner->Recommended_FindBy, $this->owner->config()->recommended_findBy_methods);
		$limit = $this->owner->config()->recommended_limit;

		if(!$method)
			$method = $this->owner->config()->recommended_findBy_methods_default;

		$list = \Injector::inst()->get($method)->getList($this->owner, $limit);

		// This will use the also bought, and set it as the priority items if set via CMS
		if($this->owner->Recommended_AlsoBought && ($alsoBought = $this->owner->CustomersAlsoBought()) && $alsoBought instanceof \DataList) {
			if($list instanceof \DataList) {
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
			elseif(($list instanceof \Countable) && !$list->count())
				$list = $alsoBought;
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

		$this->owner->extend('updateRecommended', $list);

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

	/**
	 * @return array
	 */
	protected function getMethodFormFields()
	{
		$default = (string)$this->owner->config()->recommended_findBy_methods_default;
		$methods = $this->owner->config()->recommended_findBy_methods;
		$items = [];

		if(isset($methods[$default])) {
			$items[] =
				\SelectionGroup_Item::create(
					$methods[$default],
					\Injector::inst()->get($default)->getFormFields($this->owner),
					_t('Product.Recommended_FindBy-' . str_replace(' ', '', $methods[$default]), \Injector::inst()->get($default)->title($this->owner))
				);

			unset($methods[$default]);
		}

		foreach($methods as $class => $name) {
			$method = \Injector::inst()->get($class);

			$items[] = \SelectionGroup_Item::create(
				$name,
				$method->getFormFields($this->owner),
				_t('Product.Recommended_FindBy-' . str_replace(' ', '', $name), $method->title($this->owner))
			);
		}

		return $items;
	}
} 