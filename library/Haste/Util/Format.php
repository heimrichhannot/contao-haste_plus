<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2016 Heimrich & Hannot GmbH
 *
 * @package haste_plus
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

namespace HeimrichHannot\Haste\Util;


class Format
{
	public static function getFormatedValueByDca($value, $arrData, $dc)
	{
		$value = deserialize($value);
		$opts = $arrData['options'];
		$rfrc = $arrData['reference'];
		$rgxp = $arrData['eval']['rgxp'];

		// Call the options_callback to get the formated value
		if ((is_array($arrData['options_callback']) || is_callable($arrData['options_callback']))
			&& !$arrData['reference']
		) {
			if (is_array($arrData['options_callback'])) {
				$strClass = $arrData['options_callback'][0];
				$strMethod = $arrData['options_callback'][1];

				$objInstance = \Controller::importStatic($strClass);

				$options_callback = $objInstance->$strMethod($dc);
			} elseif (is_callable($arrData['options_callback'])) {
				$options_callback = $arrData['options_callback']($dc);
			}

			$arrOptions = !is_array($value) ? array($value) : $value;

			if ($value !== null) {
				$value = array_intersect_key($options_callback, array_flip($arrOptions));
			}
		}

		if ($rgxp == 'date') {
			$value = \Date::parse(\Config::get('dateFormat'), $value);
		} elseif ($rgxp == 'time') {
			$value = \Date::parse(\Config::get('timeFormat'), $value);
		} elseif ($rgxp == 'datim') {
			$value = \Date::parse(\Config::get('datimFormat'), $value);
		} elseif (is_array($value)) {
			$value = static::flattenArray($value);

			$value = array_filter($value); // remove empty elements

			$value = implode(
				', ',
				array_map(
					function ($value) use ($rfrc) {
						if (is_array($rfrc)) {
							return isset($rfrc[$value]) ? ((is_array($rfrc[$value])) ? $rfrc[$value][0] : $rfrc[$value])
								: $value;
						} else {
							return $value;
						}
					},
					$value
				)
			);
		} elseif (is_array($opts) && array_is_assoc($opts)) {
			$value = isset($opts[$value]) ? $opts[$value] : $value;
		} elseif (is_array($rfrc)) {
			$value = isset($rfrc[$value]) ? ((is_array($rfrc[$value])) ? $rfrc[$value][0] : $rfrc[$value]) : $value;
		} elseif ($arrData['inputType'] == 'fileTree') {
			if ($arrData['eval']['multiple'] && is_array($value)) {
				$value = array_map(
					function ($val) {
						$strPath = Files::getPathFromUuid($val);

						return $strPath ?: $val;
					},
					$value
				);
			} else {
				$strPath = Files::getPathFromUuid($value);
				$value = $strPath ?: $value;
			}
		} elseif (\Validator::isBinaryUuid($value)) {
			$value = \StringUtil::binToUuid($value);
		}

		// Convert special characters (see #1890)
		return specialchars($value);
	}

	public static function flattenArray(array $array)
	{
		$return = array();
		array_walk_recursive(
			$array,
			function ($a) use (&$return) {
				$return[] = $a;
			}
		);

		return $return;
	}
}