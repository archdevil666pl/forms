<?php
/**
 * This file is part of Vegas package
 *
 * @author Arkadiusz Ostrycharz <aostrycharz@amsterdam-standard.pl>
 * @copyright Amsterdam Standard Sp. Z o.o.
 * @homepage http://vegas-cmf.github.io/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vegas\Forms;

use Phalcon\DiInterface;
use Phalcon\Forms\ElementInterface;
use Vegas\Forms\Decorator\Exception\DiNotSetException;
use Vegas\Forms\Decorator\Exception\InvalidAssetsManagerException;
use Vegas\Forms\Decorator\Exception\ViewNotSetException;
use Phalcon\Mvc\View;

class Decorator implements DecoratorInterface
{
    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var string|null
     */
    protected $templateName;

    /**
     * @var string|null
     */
    protected $templatePath;

    /**
     * @var \Phalcon\DiInterface
     */
    protected $di;

    /**
     * Create object with default $path.
     *
     * @param null $path
     */
    public function __construct($templatePath = null, $templateName = null)
    {
        if ($templatePath) {
            $this->templatePath = $templatePath;
        }

        if ($templateName) {
            $this->templateName = $templateName;
        }
    }

    /**
     * Render form element with current templatePath and templateName.
     * If templatePath or templateName are not set, render default.
     *
     * @param ElementInterface $formElement
     * @param string $value
     * @param array $attributes
     * @return string
     * @throws Decorator\Exception\DiNotSetException
     * @throws Decorator\Exception\InvalidAssetsManagerException
     * @throws Decorator\Exception\ViewNotSetException
     */
    public function render(ElementInterface $formElement, $value = '', $attributes = array())
    {
        $this->checkDependencies();

        $config = $this->di->get('config');

        if (empty($this->templateName) && !empty($config->forms->templates->default_name)) {
            $this->templateName = $config->forms->templates->default_name;
        }

        $this->variables['element'] = $formElement;
        $this->variables['value'] = $value;
        $this->variables['attributes'] = $attributes;

        $partial = $this->generatePartial();

        return $partial;
    }

    /**
     * Check for required DI services.
     *
     * @throws Decorator\Exception\DiNotSetException
     * @throws Decorator\Exception\InvalidAssetsManagerException
     * @throws Decorator\Exception\ViewNotSetException
     */
    private function checkDependencies()
    {
        if (!($this->di instanceof DiInterface)) {
            throw new DiNotSetException();
        }

        if (!$this->di->has('view')) {
            throw new ViewNotSetException();
        }

        if (!$this->di->has('assets')) {
            throw new InvalidAssetsManagerException();
        }
    }

    /**
     * @return string
     */
    private function generatePartial()
    {
        /** @var View $view */
        $view = $this->di->get('view');

        $viewsDir = $view->getViewsDir();

        try {

            if (!is_null($this->templatePath)) {
                $view->setViewsDir($this->templatePath);
            }
            $content = $view->getPartial($this->templatePath . $this->templateName, $this->variables);

        } catch (\Phalcon\Mvc\View\Exception $e) {
            ob_end_clean();

            $errorMessage = sprintf("%s, template path used: '%s'", $e->getMessage(), $this->templatePath);
            if ($this->di->has('logger')) {
                $this->di->get('logger')->notice($errorMessage);
            } else {
                error_log($errorMessage);
            }
            $content = '';
        }

        $view->setViewsDir(is_null($viewsDir) ? [] : $viewsDir);

        return $content;
    }

    /**
     * Set template name for decorator.
     *
     * @param string $name
     * @return $this
     */
    public function setTemplateName($name)
    {
        $this->templateName = (string)$name;
        return $this;
    }

    /**
     * Set template path for decorator.
     *
     * @param string $path
     * @return $this
     */
    public function setTemplatePath($path)
    {
        $this->templatePath = (string)$path;
        return $this;
    }

    /**
     * @param \Phalcon\DiInterface $di
     * @return $this
     */
    public function setDI(\Phalcon\DiInterface $di)
    {
        $this->di = $di;
        return $this;
    }

    /**
     * @return DiInterface
     */
    public function getDI()
    {
        return $this->di;
    }

    /**
     * @param array $variables
     * @return $this
     */
    public function setVariables(array $variables)
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function addVariable($name, $value)
    {
        $this->variables[$name] = $value;
        return $this;
    }
}
