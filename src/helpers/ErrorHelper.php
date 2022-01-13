<?php
declare(strict_types = 1);

namespace cusodede\web\default_controller\helpers;

/**
 * Class ErrorHelper
 */
class ErrorHelper {

	/**
	 * @param array $errors
	 * @param array|string $separator
	 * @return string
	 */
	public static function Errors2String(array $errors, array|string $separator = "\n"):string {
		$output = [];
		foreach ($errors as $attribute => $attributeErrors) {
			$error = is_array($attributeErrors)?implode($separator, $attributeErrors):$attributeErrors;
			$output[] = "{$attribute}: {$error}";
		}

		return implode($separator, $output);
	}
}