<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class MiModulo extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'miModulo';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Alex';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Mi modulo');
        $this->description = $this->l('Modulo de hecho para hacer pruebas');

        $this->confirmUninstall = $this->l('Seguro que lo quieres desinstalar?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('MIMODULO_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayBanner');
    }

    public function uninstall()
    {
        Configuration::deleteByName('MIMODULO_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitMiModuloModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMiModuloModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {

        $root = Category::getRootCategory();

    //Generating the tree
    $tree = new HelperTreeCategories('categories_1'); //The string in param is the ID used by the generated tree
    $tree->setUseCheckBox(true)
        ->setAttribute('is_category_filter', $root->id)
        ->setRootCategory($root->id)
        ->setSelectedCategories(array((int)Configuration::get('CATEGORY_1'))) //if you wanted to be pre-carged
        ->setInputName('CATEGORY_1'); //Set the name of input. The option "name" of $fields_form doesn't seem to work with "categories_select" type
    $categoryTree = $tree->render();
        
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'MIMODULO_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Habilitado')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Deshabilitado')
                            )
                        ),
                    ),
                    array(
                        'col' =>2,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'MIMODULO_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'MIMODULO_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                    array(
                        'type' => 'select',                              // This is a <select> tag.
                        'label' => $this->l('Opciones select:'),         // The <label> for this <select> tag.
                        'desc' => $this->l('Prueba para ver como funciona la select'),  // A help text, displayed right next to the <select> tag.
                        'name' => 'shipping_method',                     // The content of the 'id' attribute of the <select> tag.
                        'required' => true,                              // If set to true, this option must be set.
                        'options' => array(
                          'query' => $this->getSelectOptions(),                // $options contains the data itself.
                          'id' => 'id_option',                           // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                          'name' => 'name'                               // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                        )
                      ),
                    array(
                    'type'      => 'radio',                               // This is an <input type="checkbox"> tag.
                    'label'     => $this->l('Enable this option'),        // The <label> for this <input> tag.
                    'desc'      => $this->l('Are you a customer too?'),   // A help text, displayed right next to the <input> tag.
                    'name'      => 'active',                              // The content of the 'id' attribute of the <input> tag.
                    'required'  => true,                                  // If set to true, this option must be set.
                    'class'     => 't',                                   // The content of the 'class' attribute of the <label> tag for the <input> tag.
                    'is_bool'   => true,                                  // If set to true, this means you want to display a yes/no or true/false option.
                                                                            // The CSS styling will therefore use green mark for the option value '1', and a red mark for value '2'.
                                                                            // If set to false, this means there can be more than two radio buttons,
                                                                            // and the option label text will be displayed instead of marks.
                    'values'    => array(                                 // $values contains the data itself.
                        array(
                        'id'    => 'active_on',                           // The content of the 'id' attribute of the <input> tag, and of the 'for' attribute for the <label> tag.
                        'value' => 1,                                     // The content of the 'value' attribute of the <input> tag.   
                        'label' => $this->l('Enabled')                    // The <label> for this radio button.
                        ),
                        array(
                        'id'    => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Disabled')
                        )
                    ),
                    ),
                    array(
                        'type'  => 'categories_select',
                        'label' => $this->l('Category'),
                        'desc' => $this->l('Select Category '),
                        'name'  => 'CATEGORY_1', //No ho podem treure si no, no passa la variable al configuration
                        'category_tree'  => $categoryTree, //This is the category_tree called in form.tpl
                        'required' => true
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Color'),
                        'name' => 'colorSel',
                        'desc' => $this->l('Selecciona un color'),
                        
                    ),
                  
                    array(
                        'col' => 7,
                        'type' => 'file',
                        'name' => 'subir_archivo',
                        'label' => $this->l('Subir archivo')
                    )
                      
                      
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getSelectOptions(){
        $options = array(
            array(
              'id_option' => 2,       // The value of the 'value' attribute of the <option> tag.
              'name' => 'Hola'    // The value of the text content of the  <option> tag.
            ),
            array(
              'id_option' => 1,
              'name' => 'Adios'
            )
          );

          return $options;
    }
    protected function getCheboxOptions(){

        $cont=1;
        $options = [];        
        $subCats = $this->getSubCat();

        foreach ($subCats as $nombre) {
            array_push($options, array(
                'id_option' => 1,
                'name' => $nombre
            )
        );
        $cont++;
        }
          return $options;
    }
    protected function getSubCat(){

        $subcat = [];

        $root_cat = Category::getRootCategory($this->context->cookie->id_lang);
        $sub_children = $root_cat->getSubCategories($this->context->cookie->id_lang);
        $this->context->smarty->assign(
            array(
                'categories' => $sub_children,
            )
        );
        foreach($sub_children as $sub){
            array_push($subcat, $sub['name']);    
        }
        
        return $subcat;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'MIMODULO_LIVE_MODE' => Configuration::get('MIMODULO_LIVE_MODE', true),
            'MIMODULO_ACCOUNT_EMAIL' => Configuration::get('MIMODULO_ACCOUNT_EMAIL', null),
            'MIMODULO_ACCOUNT_PASSWORD' => Configuration::get('MIMODULO_ACCOUNT_PASSWORD', null),
            'shipping_method' => Configuration::get('shipping_method', null),
            'active' => Configuration::get('active', null),            
            'colorSel' => Configuration::get('colorSel', null),            
            'subir_archivo' => Configuration::get('subir_archivo', null),
            'CATEGORY_1' => Configuration::get('CATEGORY_1', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayBanner()
    {

        print_r(Configuration::get('colorSel'));
      
        $form_values = $this->getConfigFormValues();
        $texto = $this->l($form_values['colorSel']);
        
        $this->context->smarty->assign([
            "valor" => $texto
        ]);
        return $this->display(__FILE__, 'plantillaTest.tpl');
    }
}
