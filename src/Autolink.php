<?php
/**
 * Part of php-autolink project. 
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Asika;

use Windwalker\Dom\HtmlElement;

/**
 * The Autolink class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class Autolink
{
	/**
	 * Property options.
	 *
	 * @var  array
	 */
	public $options = array(
		'strip_scheme' => false,
		'text_limit' => false,
		'auto_title' => false
	);

	/**
	 * Property schemes.
	 *
	 * @var  array
	 */
	protected $schemes = array(
		'http',
		'https',
		'ftp',
		'ftps'
	);

	/**
	 * Class init.
	 *
	 * @param array $options Basic options.
	 * @param array $schemes
	 */
	public function __construct($options = array(), $schemes = array())
	{
		$this->options = array_merge($this->options, (array) $options);

		$this->setSchemes(array_merge($this->schemes, $schemes));
	}

	/**
	 * render
	 *
	 * @param string $text
	 * @param array  $attribs
	 *
	 * @return  string
	 */
	public function render($text, $attribs = array())
	{
		$self = $this;

		$regex = "/(([a-zA-Z]*=\")*(" . $this->getSchemes(true) . ")\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?)/";

		return preg_replace_callback(
			$regex,
			function($matches) use ($self, $attribs)
			{
				preg_match('/[a-zA-Z]*\=\"(.*)\"/', $matches[0], $inElements);

				if (!$inElements)
				{
					return $self->convert($matches[0]);
				}

				return $matches[0];
			},
			$text
		);
	}

	/**
	 * renderEmail
	 *
	 * @param string $text
	 * @param array  $attribs
	 *
	 * @return  string
	 */
	public function renderEmail($text, $attribs = array())
	{
		$regex = "/(([a-zA-Z]*=\")*\S+@\S+\.\S+)/";

		return preg_replace_callback(
			$regex,
			function($matches) use ($attribs)
			{
				preg_match('/[a-zA-Z]*\=\"(.*)\"/', $matches[0], $inElements);

				if (!$inElements)
				{
					$attribs['href'] = 'mailto:' . htmlspecialchars($matches[0]);

					return (string) new HtmlElement('a', htmlspecialchars($matches[0]), $attribs);
				}

				return $matches[0];
			},
			$text
		);
	}

	/**
	 * convert
	 *
	 * @param string $url
	 * @param array  $attribs
	 *
	 * @return  string
	 */
	public function convert($url, $attribs = array())
	{
		$content = $url;

		if ($this->stripScheme())
		{
			if (preg_match('!^(' . $this->getSchemes(true) . ')://!i', $content, $m))
			{
				$content = substr($content, strlen($m[1]) + 3);
			}
		}

		if ($limit = $this->textLimit())
		{
			if (is_callable($limit))
			{
				$content = call_user_func($limit, $content);
			}
			else
			{
				$content = $this->shorten($content, $limit);
			}
		}

		$attribs['href'] = htmlspecialchars($url);

		if ($this->autoTitle())
		{
			$attribs['title'] = htmlspecialchars($url);
		}

		return (string) new HtmlElement('a', htmlspecialchars($content), $attribs);
	}

	/**
	 * autolinkLabel
	 *
	 * @param string $text
	 * @param int    $limit
	 *
	 * @return  string
	 */
	public function shorten($text, $limit)
	{
		if (!$limit)
		{
			return $text;
		}

		if (strlen($text) > $limit)
		{
			return substr($text, 0, $limit - 3) . '...';
		}

		return $text;
	}

	/**
	 * stripScheme
	 *
	 * @param mixed $value
	 *
	 * @return  mixed|static
	 */
	public function stripScheme($value = null)
	{
		return $this->optionAccess('strip_scheme', $value);
	}

	/**
	 * textLimit
	 *
	 * @param int|callable $value
	 *
	 * @return  int|callable|static
	 */
	public function textLimit($value = null)
	{
		return $this->optionAccess('text_limit', $value);
	}

	/**
	 * autoTitle
	 *
	 * @param mixed $value
	 *
	 * @return  mixed|static
	 */
	public function autoTitle($value = null)
	{
		return $this->optionAccess('auto_title', $value);
	}

	/**
	 * optionAccess
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return  mixed|static
	 */
	protected function optionAccess($name, $value = null)
	{
		if ($value === null)
		{
			return isset($this->options[$name]) ? $this->options[$name] : null;
		}

		$this->options[$name] = $value;

		return $this;
	}

	/**
	 * addScheme
	 *
	 * @param string $scheme
	 *
	 * @return  static
	 */
	public function addScheme($scheme)
	{
		$scheme = strtolower($scheme);

		if (!in_array($scheme, $this->schemes))
		{
			$this->schemes[] = $scheme;
		}

		return $this;
	}

	/**
	 * removeScheme
	 *
	 * @param string $scheme
	 *
	 * @return  static
	 */
	public function removeScheme($scheme)
	{
		$index = array_search($scheme, $this->schemes);

		if ($index !== false)
		{
			unset($this->schemes[$index]);
		}

		return $this;
	}

	/**
	 * Method to get property Options
	 *
	 * @return  array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * Method to set property options
	 *
	 * @param   array $options
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setOptions($options)
	{
		$this->options = $options;

		return $this;
	}

	/**
	 * Method to get property Schemes
	 *
	 * @param  bool $regex
	 *
	 * @return array|string
	 */
	public function getSchemes($regex = false)
	{
		if ($regex)
		{
			return implode('|', $this->schemes);
		}

		return $this->schemes;
	}

	/**
	 * Method to set property schemes
	 *
	 * @param   array $schemes
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setSchemes($schemes)
	{
		$schemes = array_unique(array_map('strtolower', (array) $schemes));

		$this->schemes = $schemes;

		return $this;
	}
}
