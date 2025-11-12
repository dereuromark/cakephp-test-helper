<?php

namespace TestHelper\View\Helper;

use Cake\View\Helper;

/**
 * Icon Helper
 *
 * Provides icon rendering functionality using Font Awesome 6.
 * This serves as a fallback when Templating.Icon helper is not available.
 */
class IconHelper extends Helper {

	/**
	 * Renders an icon using Font Awesome 6
	 *
	 * @param string $icon Icon name (e.g., 'plus', 'play', 'warning')
	 * @param array<string, mixed> $attributes HTML attributes for the icon (first parameter for compatibility)
	 * @param array<string, mixed> $options Additional options (second parameter for compatibility)
	 * @return string HTML icon tag
	 */
	public function render(string $icon, array $attributes = [], array $options = []): string {
		// Map common icon names to Font Awesome classes
		$iconMap = [
			'plus' => 'fa-plus',
			'play' => 'fa-play',
			'warning' => 'fa-triangle-exclamation',
			'chart-bar' => 'fa-chart-bar',
			'check' => 'fa-check',
			'times' => 'fa-times',
			'circle' => 'fa-circle',
			'square' => 'fa-square',
			'edit' => 'fa-edit',
			'delete' => 'fa-trash',
			'save' => 'fa-save',
			'search' => 'fa-search',
			'info' => 'fa-info-circle',
		];

		$faClass = $iconMap[$icon] ?? 'fa-' . $icon;
		$class = 'fas ' . $faClass;

		if (isset($attributes['class'])) {
			$class .= ' ' . $attributes['class'];
			unset($attributes['class']);
		}

		$htmlAttributes = ['class' => $class] + $attributes;
		$attributeString = [];
		foreach ($htmlAttributes as $key => $value) {
			if ($value === true) {
				$attributeString[] = h($key);
			} elseif ($value !== false && $value !== null) {
				$attributeString[] = h($key) . '="' . h($value) . '"';
			}
		}

		return '<i ' . implode(' ', $attributeString) . '></i>';
	}

}
