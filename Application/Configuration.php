<?php
/*
 * This file is part of myTinyLocalHost.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application;

class Configuration
{
	protected $app;

	private $dist;

	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	public function get()
	{
		$config = [];

		if (file_exists($this->getConfigFilename())) {
			$config = require $this->getConfigFilename();
		}

		return $config + $this->getDist();
	}

	public function validate($newConfig)
	{
		$toValidate = $this->getToValidate($newConfig);

		$validated = $this->performValidation($toValidate);

		return $validated;
	}

	public function save($newConfig)
	{
		$content =
		'<?php' .  "\n\n" .
		'return ' .
		var_export($newConfig, true) .  ";\n\n";

		$this->app['filesystem']->dumpFile($this->getConfigFilename(), $content);
	}

	/**
	 * Return an array of config value to validate.
	 *
	 * In fact, return values ​​that are different
	 * from  the distribution values.
	 *
	 * @param array $newConfig
	 * @return multitype:unknown
	 */
	private function getToValidate(array $newConfig = [])
	{
		$distConfig = $this->getDist();

		$toValidate = [];
		foreach ($newConfig as $k => $v)
		{
			if ($v !== $distConfig[$k]) {
				$toValidate[$k] = $v;
			}
		}

		return $toValidate;
	}

	/**
	 * Perform the validation of new configuration values.
	 *
	 * @param array $toValidate
	 * @return array
	 */
	private function performValidation(array $toValidate = [])
	{
		$validated = [];

		if (!empty($toValidate['app_name'])) {
			$validated['app_name'] = strip_tags($toValidate['app_name']);
		}

		if (!empty($toValidate['wampserver_www_dir']))
		{
			if (!is_dir($toValidate['wampserver_www_dir']))
			{
				$this->app['instantMessages']->error(
					sprintf($this->app['translator']->trans('error.missing.www'), $toValidate['wampserver_www_dir'])
				);
			}
			elseif (!is_writable($toValidate['wampserver_www_dir']))
			{
				$this->app['instantMessages']->error(
					sprintf($this->app['translator']->trans('error_unwritable_www'), $toValidate['wampserver_www_dir'])
				);
			}
			else {
				$validated['wampserver_www_dir'] = $toValidate['wampserver_www_dir'];
			}
		}

		if (!empty($toValidate['debug'])) {
			$validated['debug'] = (boolean)$toValidate['debug'];
		}

		return $validated;
	}

	/**
	 * Return configuration filename.
	 *
	 * @return string
	 */
	private function getConfigFilename()
	{
		return __DIR__ . '/Config/config.php';
	}

	/**
	 * Return the array of the distribution configuration.
	 *
	 * @return array
	 */
	private function getDist()
	{
		if (null === $this->dist) {
			$this->dist = require __DIR__ . '/Config/dist.php';
		}

		return $this->dist;
	}
}
