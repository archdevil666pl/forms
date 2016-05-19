<?php
/**
 * This file is part of Vegas package
 *
 * @author Radosław Fąfara <radek@archdevil.pl>
 * @copyright Amsterdam Standard Sp. Z o.o.
 * @homepage http://vegas-cmf.github.io/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vegas\Forms;

use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Text;
use Vegas\Forms\DataProvider\DataProviderInterface;
use Vegas\Forms\DataProvider\Exception\NotFoundException;
use Vegas\Forms\Element\Select;
use Vegas\Validation\Validator\PresenceOf;

/**
 * Subform used to validate settings for each created input element in FormFactory
 */
class InputSettings extends \Vegas\Forms\Form
{
    
    /**
     * Param name used for element type recognition
     */
    const TYPE_PARAM = 'type';
    
    /**
     * Param name used for field identification - has to be unique
     */
    const IDENTIFIER_PARAM = 'name';
    
    /**
     * Param name used if a field should be required
     */
    const REQUIRED_PARAM = 'required';
    
    /**
     * Param name used for element label
     */
    const LABEL_PARAM = 'label';
    
    /**
     * Param name used for default value
     */
    const DEFAULTS_PARAM = 'defaults';
    
    /**
     * Param name used for placeholder value
     */
    const PLACEHOLDER_PARAM = 'placeholder';
    
    /**
     * Param name used for fully qualified classnames implementing DataProviderInterface
     */
    const DATA_PARAM = 'data';
    
    public function initialize()
    {

        $type = new Hidden(self::TYPE_PARAM);
        $this->add($type);
        
        $identifier = (new Text(self::IDENTIFIER_PARAM))
                ->addValidator(new PresenceOf)
                ->setLabel('Unique ID');
        $this->add($identifier);
        
        $required = (new Check(self::REQUIRED_PARAM))
                ->setAttribute('value', true)
                ->setLabel('Required field');
        $this->add($required);
        
        $label = (new Text(self::LABEL_PARAM))
                ->setLabel('Label');
        $this->add($label);
        
        $defaults = (new Text(self::DEFAULTS_PARAM))
                ->setLabel('Default value');
        $this->add($defaults);

        $placeholder = (new Text(self::PLACEHOLDER_PARAM))
                ->setLabel('Placeholder text');
        $this->add($placeholder);
        
        $this->addDataProviderInput();
    }
    
    /**
     * Proxy method to retrieve data to populate select lists.
     * @return array
     * @throws \Vegas\Forms\DataProvider\Exception\NotFoundException When DI is not configured properly or a wrong value is provided.
     */
    public function getDataFromProvider()
    {
        $select = $this->get(self::DATA_PARAM);
        if(is_null($select->getValue()) || $select->getValue() === '') {
            return [];
        }
        $className = $select->getValue();
        if (!class_exists($className) || !array_key_exists($className, $select->getOptions())) {
            throw new NotFoundException;
        }
        $provider = new $className;
        return $provider->getData();

    }
    
    /**
     * Adds selectable list of data providers.
     * Usable only for selectable input types.
     */
    public function addDataProviderInput()
    {
        $input = (new Select(self::DATA_PARAM))
                ->setOptions(array(null => '---'));
        $dataProviderClasses = array();
        foreach ($this->di->get('config')->formFactory->dataProviders as $className) {
            $provider = new $className;
            if ($provider instanceof DataProviderInterface) {
                $dataProviderClasses[$className] = $provider->getName();
            }
        }
        $input->addOptions($dataProviderClasses)->setLabel('Data provider');
        $this->add($input);
    }
}
