<?php
namespace app\services;

class ItemParser
{
	protected $content;

	public function __construct($content)
	{
		$this->content = preg_replace(
			'/<head>(.*)<\/head>/siU',
			'<head><meta http-equiv="content-type" content="text/html; charset=utf-8" /></head>',
			$content
		);
	}

	public function getData()
	{
        $doc = new \DOMDocument();
        @$doc->loadHTML($this->content);
        $xpath = new \DOMXpath($doc);

        $steps = $xpath->query('//div[contains(@class, "step-content")]');

        $stepElements = [];

        $types = [
            'Label' => 'type-label',
            'Group' => 'type-group_element',
            'Object' => 'type-object',
            'Attribute' => 'type-attribute',
        ];

        $table = [];
        $currentLabel = null;

        foreach ($steps as $i => $step) {
            $stepElements = $xpath->query('.//div[contains(@class, "fsa-element")]', $step);

            foreach ($stepElements as $el) {
                $class = $el->attributes->getNamedItem('class')->value;
                
                foreach ($types as $typeName => $typeClass) {
                    if (strpos($class, $typeClass) !== FALSE) {
                        $type = $typeName;
                        continue;
                    }           
                }

                if ($type == 'Label') {
                    $currentLabel = trim($el->textContent);
                    continue;
                }

                if ($currentLabel == null) {
                    continue;
                }

                if ($type == 'Group') {
                    $table[$currentLabel][] = trim($el->textContent);
                }

                if ($type == 'Object') {
                    $type = 'Attribute';
                }

                if ($type == 'Attribute') {
                    $names = $xpath->query('.//div[contains(@class, "form-left-col")]', $el);
                    $values = $xpath->query('.//div[contains(@class, "form-right-col")]', $el);

                    $attributes = [];

                    foreach ($names as $i => $name) {
                        $n = trim($name->textContent);

                        if (!$values->item($i)) {
                            continue;
                        }
                        
                        $v = $values->item($i)->textContent;

                        if (empty($n) || empty($v)) {
                            continue;
                        }

                        $attributes[$n] = $v;
                    }

                    $table[$currentLabel][] = $attributes;
                }
            }
        }

        return $table;
	}
}