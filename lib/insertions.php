<?php

namespace TAO;

class Insertions
{
	private $insertions = array();
	private $insertions_with_parameter = array();
	private static $instance = null;

    private static $start_params_separator = '{';
    private static $end_params_separator = '}';

	public static function instance()
	{
		return self::$instance ?: self::$instance = new self();
	}

    public static function setParamsSeparators(array $separators)
    {
        if ($separators['start'] && is_string($separators['start'])) {
            self::$start_params_separator = $separators['start'];
        }

        if ($separators['end'] && is_string($separators['end'])) {
            self::$end_params_separator = $separators['end'];
        }
    }

    public static function getParamsSeparators()
    {
        return [
            'start' => self::$start_params_separator,
            'end' => self::$end_params_separator,
        ];
    }

	public function renderInsertion($name)
	{
		return '#TECHART_INSERTION_' . strtoupper($name) . '#';
	}

	public function addInsertionContent($name, $content)
	{
		$this->insertions[$name] = $content;
	}

	public function addInsertionContentWithParameter($name, $content)
	{
		$this->insertions_with_parameter[$name] = $content;
	}

	public function startInsertionBlock($name)
	{
		ob_start();
	}

	public function endInsertionBlock($name)
	{
		$this->addInsertionContent($name, ob_get_clean());
	}

	public function processInsertion($name, $content)
	{
		$replacement = is_callable($this->insertions[$name]) ? $this->insertions[$name]() : $this->insertions[$name];
		return str_replace($this->insertionCode($name), $replacement, $content);
	}

	public function processInsertionWithParameter($content)
	{
		if (count($this->insertions_with_parameter) == 0) {
			return $content;
		}
		$search = [];
		$replace = [];
		$i = 0;
		$start_name = strpos($content, '#TECHART_INSERTION_');
        $separator_len = strlen(self::$start_params_separator) + 1;

        while ($start_name) {
            $stop_name = strpos($content, self::$start_params_separator, $start_name + 19) - 1;
            $name = substr($content, $start_name + 19, $stop_name - ($start_name + 18));
            $next = $stop_name;
            if (array_key_exists($name, $this->insertions_with_parameter)) {
                $stop_parms = strpos($content, self::$end_params_separator, $next);
                $params = substr($content, $stop_name + $separator_len, $stop_parms - ($stop_name) - $separator_len);
                $search[$i] = '#TECHART_INSERTION_' . $name . self::$start_params_separator . $params . self::$end_params_separator. "#";
                $replace[$i] = $this->insertions_with_parameter[$name]($params);
                $i++;
                $next = $stop_parms;
            }
            $start_name = strpos($content, '#TECHART_INSERTION_', $next);
        }

		return str_replace($search, $replace, $content);
	}

	public function processInsertions($content)
	{
		foreach (array_keys($this->insertions) as $name) {
			$content = $this->processInsertion($name, $content);
		}

		$content = $this->processInsertionWithParameter($content);

		return $content;
	}

	private function __construct()
	{
		if (\TAO\Urls::isCurrentStartsWith('/bitrix/admin/')) {
			return;
		}
		\AddEventHandler("main", "OnEndBufferContent", function (&$content) {
			$content = \TAO::insertions()->processInsertions($content);
		});
	}

	private function __clone()
	{
		// close it
	}

	private function insertionCode($name)
	{
		return '#TECHART_INSERTION_' . strtoupper($name) . '#';
	}
}
