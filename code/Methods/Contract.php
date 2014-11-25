<?php
/**
 * Milkyway Multimedia
 * Contract.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Recommended\Methods;


interface Contract {
	public function title($buyable);

	public function getFormFields($buyable);

	public function getList($buyable, $limit = 4);
} 