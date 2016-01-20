<?php namespace Milkyway\SS\Shop\Recommended\Methods;

/**
 * Milkyway Multimedia
 * Contract.php
 *
 * @package milkyway-multimedia/ss-shop-recommended
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

interface Contract
{
    public function title($buyable);

    public function getFormFields($buyable);

    public function getList($buyable, $limit = 4);
}
