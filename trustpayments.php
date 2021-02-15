<?php
/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2021 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if (! defined('_PS_VERSION_')) {
    exit();
}

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'trustpayments_autoloader.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'trustpayments-sdk' . DIRECTORY_SEPARATOR .
    'autoload.php');
class TrustPayments extends PaymentModule
{
    const CK_SHOW_CART = 'TRU_SHOW_CART';

    const CK_SHOW_TOS = 'TRU_SHOW_TOS';

    const CK_REMOVE_TOS = 'TRU_REMOVE_TOS';

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->name = 'trustpayments';
        $this->tab = 'payments_gateways';
        $this->author = 'Customweb GmbH';
        $this->bootstrap = true;
        $this->need_instance = 0;
        $this->version = '1.2.3';
        $this->displayName = 'Trust Payments';
        $this->description = $this->l('This PrestaShop module enables to process payments with %s.');
        $this->description = sprintf($this->description, 'Trust Payments');
        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => '1.6.1.24'
        );
        $this->module_key = '';
        parent::__construct();
        $this->confirmUninstall = sprintf(
            $this->l('Are you sure you want to uninstall the %s module?', 'abstractmodule'),
            'Trust Payments'
        );
        
        // Remove Fee Item
        if (isset($this->context->cart) && Validate::isLoadedObject($this->context->cart)) {
            TrustPaymentsFeehelper::removeFeeSurchargeProductsFromCart($this->context->cart);
        }
        if (! empty($this->context->cookie->tru_error)) {
            $errors = $this->context->cookie->tru_error;
            if (is_string($errors)) {
                $this->context->controller->errors[] = $errors;
            } elseif (is_array($errors)) {
                foreach ($errors as $error) {
                    $this->context->controller->errors[] = $error;
                }
            }
            unset($_SERVER['HTTP_REFERER']); // To disable the back button in the error message
            $this->context->cookie->tru_error = null;
        }
    }
    
    public function addError($error)
    {
        $this->_errors[] = $error;
    }
    
    public function getContext()
    {
        return $this->context;
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    public function install()
    {
        if (! TrustPaymentsBasemodule::checkRequirements($this)) {
            return false;
        }
        if (! parent::install()) {
            return false;
        }
        return TrustPaymentsBasemodule::install($this);
    }
    
    public function uninstall()
    {
        return parent::uninstall() && TrustPaymentsBasemodule::uninstall($this);
    }
    

    public function installHooks()
    {
        return TrustPaymentsBasemodule::installHooks($this) && $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayHeader') && $this->registerHook('displayMobileHeader') &&
            $this->registerHook('displayPaymentEU') && $this->registerHook('displayTop') &&
            $this->registerHook('payment') && $this->registerHook('paymentReturn') &&
            $this->registerHook('trustPaymentsCron');
    }

    public function getBackendControllers()
    {
        return array(
            'AdminTrustPaymentsMethodSettings' => array(
                'parentId' => Tab::getIdFromClassName('AdminParentModules'),
                'name' => 'Trust Payments ' . $this->l('Payment Methods')
            ),
            'AdminTrustPaymentsDocuments' => array(
                'parentId' => - 1, // No Tab in navigation
                'name' => 'Trust Payments ' . $this->l('Documents')
            ),
            'AdminTrustPaymentsOrder' => array(
                'parentId' => - 1, // No Tab in navigation
                'name' => 'Trust Payments ' . $this->l('Order Management')
            ),
            'AdminTrustPaymentsCronJobs' => array(
                'parentId' => Tab::getIdFromClassName('AdminTools'),
                'name' => 'Trust Payments ' . $this->l('CronJobs')
            )
        );
    }

    public function installConfigurationValues()
    {
        return Configuration::updateValue(self::CK_SHOW_CART, true) &&
            Configuration::updateValue(self::CK_SHOW_TOS, false) &&
            Configuration::updateValue(self::CK_REMOVE_TOS, false) &&
            TrustPaymentsBasemodule::installConfigurationValues();
    }

    public function uninstallConfigurationValues()
    {
        return Configuration::deleteByName(self::CK_SHOW_CART) &&
            Configuration::deleteByName(self::CK_SHOW_TOS) && Configuration::deleteByName(self::CK_REMOVE_TOS) &&
            TrustPaymentsBasemodule::uninstallConfigurationValues();
    }

    public function getContent()
    {
        $output = TrustPaymentsBasemodule::getMailHookActiveWarning($this);
        $output .= TrustPaymentsBasemodule::handleSaveAll($this);
        $output .= TrustPaymentsBasemodule::handleSaveApplication($this);
        $output .= $this->handleSaveCheckout();
        $output .= TrustPaymentsBasemodule::handleSaveEmail($this);
        $output .= TrustPaymentsBasemodule::handleSaveFeeItem($this);
        $output .= TrustPaymentsBasemodule::handleSaveDownload($this);
        $output .= TrustPaymentsBasemodule::handleSaveSpaceViewId($this);
        $output .= TrustPaymentsBasemodule::handleSaveOrderStatus($this);
        $output .= TrustPaymentsBasemodule::displayHelpButtons($this);
        return $output . TrustPaymentsBasemodule::displayForm($this);
    }

    private function handleSaveCheckout()
    {
        $output = "";
        if (Tools::isSubmit('submit' . $this->name . '_checkout')) {
            if (! $this->context->shop->isFeatureActive() || $this->context->shop->getContext() == Shop::CONTEXT_SHOP) {
                Configuration::updateValue(self::CK_SHOW_CART, Tools::getValue(self::CK_SHOW_CART));
                Configuration::updateValue(self::CK_SHOW_TOS, Tools::getValue(self::CK_SHOW_TOS));
                Configuration::updateValue(self::CK_REMOVE_TOS, Tools::getValue(self::CK_REMOVE_TOS));
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $output .= $this->displayError(
                    $this->l('You can not store the configuration for all Shops or a Shop Group.')
                );
            }
        }
        return $output;
    }

    public function getConfigurationForms()
    {
        return array(
            $this->getCheckoutForm(),
            TrustPaymentsBasemodule::getEmailForm($this),
            TrustPaymentsBasemodule::getFeeForm($this),
            TrustPaymentsBasemodule::getDocumentForm($this),
            TrustPaymentsBasemodule::getSpaceViewIdForm($this),
            TrustPaymentsBasemodule::getOrderStatusForm($this)
        );
    }

    public function getConfigurationValues()
    {
        return array_merge(
            TrustPaymentsBasemodule::getApplicationConfigValues($this),
            $this->getCheckoutConfigValues(),
            TrustPaymentsBasemodule::getEmailConfigValues($this),
            TrustPaymentsBasemodule::getFeeItemConfigValues($this),
            TrustPaymentsBasemodule::getDownloadConfigValues($this),
            TrustPaymentsBasemodule::getSpaceViewIdConfigValues($this),
            TrustPaymentsBasemodule::getOrderStatusConfigValues($this)
        );
    }

    public function getConfigurationKeys()
    {
        $base = TrustPaymentsBasemodule::getConfigurationKeys();
        $base[] = self::CK_SHOW_CART;
        $base[] = self::CK_SHOW_TOS;
        $base[] = self::CK_REMOVE_TOS;
        return $base;
    }

    private function getCheckoutForm()
    {
        $checkoutConfig = array(
            array(
                'type' => 'switch',
                'label' => $this->l('Show Cart Summary'),
                'name' => self::CK_SHOW_CART,
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Show')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Hide')
                    )
                ),
                'desc' => $this->l('Should a cart summary be shown on the payment details input page.'),
                'lang' => false
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Show Terms of Service'),
                'name' => self::CK_SHOW_TOS,
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Show')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Hide')
                    )
                ),
                'desc' => $this->l(
                    'Should the Terms of Service be shown and checked on the payment details input page.'
                ),
                'lang' => false
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Remove default Terms of Service'),
                'name' => self::CK_REMOVE_TOS,
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Keep')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Remove')
                    )
                ),
                'desc' => $this->l(
                    'Should the default Terms of Service be removed during the checkout. CAUTION: This option will remove the ToS for all payment methods.'
                ),
                'lang' => false
            )
        );

        return array(
            'legend' => array(
                'title' => $this->l('Checkout Settings')
            ),
            'input' => $checkoutConfig,
            'buttons' => array(
                array(
                    'title' => $this->l('Save All'),
                    'class' => 'pull-right',
                    'type' => 'input',
                    'icon' => 'process-icon-save',
                    'name' => 'submit' . $this->name . '_all'
                ),
                array(
                    'title' => $this->l('Save'),
                    'class' => 'pull-right',
                    'type' => 'input',
                    'icon' => 'process-icon-save',
                    'name' => 'submit' . $this->name . '_checkout'
                )
            )
        );
    }

    private function getCheckoutConfigValues()
    {
        $values = array();
        if (! $this->context->shop->isFeatureActive() || $this->context->shop->getContext() == Shop::CONTEXT_SHOP) {
            $values[self::CK_SHOW_CART] = (bool) Configuration::get(self::CK_SHOW_CART);
            $values[self::CK_SHOW_TOS] = (bool) Configuration::get(self::CK_SHOW_TOS);
            $values[self::CK_REMOVE_TOS] = (bool) Configuration::get(self::CK_REMOVE_TOS);
        }
        return $values;
    }

    public function hookTrustPaymentsCron($params)
    {
        return TrustPaymentsBasemodule::hookTrustPaymentsCron($params);
    }

    public function hookDisplayHeader($params)
    {
        if ($this->context->controller instanceof ParentOrderControllerCore) {
            return $this->getDeviceIdentifierScript();
        }
    }

    public function hookDisplayMobileHeader($params)
    {
        if ($this->context->controller instanceof ParentOrderControllerCore) {
            return $this->getDeviceIdentifierScript();
        }
    }

    public function hookDisplayTop($params)
    {
        return  TrustPaymentsBasemodule::hookDisplayTop($this, $params);
    }

    /**
     * hookPayment replacement for compatibility with module eu_legal
     *
     * @param array $params
     * @return string Generated html
     */
    public function hookDisplayPaymentEU($params)
    {
        if (! $this->active) {
            return;
        }
        if (! isset($params['cart']) || ! ($params['cart'] instanceof Cart)) {
            return;
        }
        $cart = $params['cart'];
        try {
            $possiblePaymentMethods = TrustPaymentsServiceTransaction::instance()->getPossiblePaymentMethods(
                $cart
            );
        } catch (TrustPaymentsExceptionInvalidtransactionamount $e) {
            PrestaShopLogger::addLog($e->getMessage() . " CartId: " . $cart->id, 2, null, 'TrustPayments');
            return array(
                array(
                    'cta_text' => $this->display(dirname(__FILE__), 'hook/amount_error_eu.tpl'),
                    'form' => ""
                )
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage() . " CartId: " . $cart->id, 1, null, 'TrustPayments');
            return;
        }
        $shopId = $cart->id_shop;
        $language = Context::getContext()->language->language_code;
        $methods = array();
        foreach ($possiblePaymentMethods as $possible) {
            $methodConfiguration = TrustPaymentsModelMethodconfiguration::loadByConfigurationAndShop(
                $possible->getSpaceId(),
                $possible->getId(),
                $shopId
            );
            if (! $methodConfiguration->isActive()) {
                continue;
            }
            $methods[] = $methodConfiguration;
        }
        $result = array();
        
        $this->context->smarty->registerPlugin(
            'function',
            'trustpayments_clean_html',
            array(
                'TrustPaymentsSmartyfunctions',
                'cleanHtml'
            )
        );
        
        foreach (TrustPaymentsHelper::sortMethodConfiguration($methods) as $methodConfiguration) {
            $parameters = TrustPaymentsBasemodule::getParametersFromMethodConfiguration($this, $methodConfiguration, $cart, $shopId, $language);
            $this->smarty->assign($parameters);

            $result[] = array(
                'cta_text' => $this->display(dirname(__FILE__), 'hook/payment_eu_text.tpl'),
                'logo' => $parameters['image'],
                'form' => $this->display(dirname(__FILE__), 'hook/payment_eu_form.tpl')
            );
        }
        return $result;
    }

    public function hookDisplayPaymentReturn($params)
    {
        if ($this->active == false) {
            return false;
        }
        $order = $params['objOrder'];
        if ($order->module != $this->name) {
            return false;
        }
        $this->smarty->assign(
            array(
                'reference' => $order->reference,
                'params' => $params,
                'total' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false)
            )
        );
        return $this->display(dirname(__FILE__), 'hook/payment_return.tpl');
    }

    public function hookPayment($params)
    {
        if (! $this->active) {
            return;
        }
        if (! isset($params['cart']) || ! ($params['cart'] instanceof Cart)) {
            return;
        }
        $cart = $params['cart'];
        try {
            $possiblePaymentMethods = TrustPaymentsServiceTransaction::instance()->getPossiblePaymentMethods(
                $cart
            );
        } catch (TrustPaymentsExceptionInvalidtransactionamount $e) {
            PrestaShopLogger::addLog($e->getMessage() . " CartId: " . $cart->id, 2, null, 'TrustPayments');
            return $this->display(dirname(__FILE__), 'hook/amount_error.tpl');
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage() . " CartId: " . $cart->id, 1, null, 'TrustPayments');
            return;
        }
        $shopId = $cart->id_shop;
        $language = Context::getContext()->language->language_code;
        $methods = array();
        foreach ($possiblePaymentMethods as $possible) {
            $methodConfiguration = TrustPaymentsModelMethodconfiguration::loadByConfigurationAndShop(
                $possible->getSpaceId(),
                $possible->getId(),
                $shopId
            );
            if (! $methodConfiguration->isActive()) {
                continue;
            }
            $methods[] = $methodConfiguration;
        }
        $result = "";
        $this->context->smarty->registerPlugin(
            'function',
            'trustpayments_clean_html',
            array(
                'TrustPaymentsSmartyfunctions',
                'cleanHtml'
            )
        );
        foreach (TrustPaymentsHelper::sortMethodConfiguration($methods) as $methodConfiguration) {
            $templateVars = TrustPaymentsBasemodule::getParametersFromMethodConfiguration($this, $methodConfiguration, $cart, $shopId, $language);
            $this->smarty->assign($templateVars);
            $result .= $this->display(dirname(__FILE__), 'hook/payment.tpl');
        }
        return $result;
    }

    private function getDeviceIdentifierScript()
    {
        $uniqueId = $this->context->cookie->tru_device_id;
        if ($uniqueId == false) {
            $uniqueId = TrustPaymentsHelper::generateUUID();
            $this->context->cookie->tru_device_id = $uniqueId;
        }
        $scriptUrl = TrustPaymentsHelper::getBaseGatewayUrl() . '/s/' . Configuration::get(TrustPaymentsBasemodule::CK_SPACE_ID) .
            '/payment/device.js?sessionIdentifier=' . $uniqueId;
        return '<script src="' . $scriptUrl . '" async="async"></script>';
    }

    
    public function hookActionFrontControllerSetMedia($arr)
    {
        if ($this->context->controller instanceof ParentOrderControllerCore) {
            $this->context->controller->addCSS(
                __PS_BASE_URI__ . 'modules/' . $this->name . '/views/css/frontend/checkout.css'
            );
            $this->context->controller->addJS(
                __PS_BASE_URI__ . 'modules/' . $this->name . '/views/js/frontend/selection.js'
            );
            $cart = $this->context->cart;
            if (Configuration::get(self::CK_REMOVE_TOS, null, null, $cart->id_shop)) {
                $this->context->cookie->checkedTOS = 1;
                $this->context->controller->addJS(
                    __PS_BASE_URI__ . 'modules/' . $this->name . '/views/js/frontend/tos-handling.js'
                );
            }
        }
    }

    /**
     * Show the manual task in the admin bar.
     * The output is moved with javascript to the correct place as better hook is missing.
     *
     * @return string
     */
    public function hookDisplayAdminAfterHeader()
    {
        $result = TrustPaymentsBasemodule::hookDisplayAdminAfterHeader($this);
        $result .= TrustPaymentsBasemodule::getCronJobItem($this);
        return $result;
    }

    public function hasBackendControllerDeleteAccess(AdminController $backendController)
    {
        return $backendController->tabAccess['delete'] === '1';
    }

    public function hasBackendControllerEditAccess(AdminController $backendController)
    {
        return $backendController->tabAccess['edit'] === '1';
    }
    
       
    public function hookTrustPaymentsSettingsChanged($params)
    {
        return TrustPaymentsBasemodule::hookTrustPaymentsSettingsChanged($this, $params);
    }
    
    public function hookActionMailSend($data)
    {
        return TrustPaymentsBasemodule::hookActionMailSend($this, $data);
    }
    
    public function validateOrder(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $extra_vars = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null
    ) {
        TrustPaymentsBasemodule::validateOrder($this, $id_cart, $id_order_state, $amount_paid, $payment_method, $message, $extra_vars, $currency_special, $dont_touch_amount, $secure_key, $shop);
    }
    
    public function validateOrderParent(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $extra_vars = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null
    ) {
        parent::validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method, $message, $extra_vars, $currency_special, $dont_touch_amount, $secure_key, $shop);
    }
    
    public function hookDisplayOrderDetail($params)
    {
        return TrustPaymentsBasemodule::hookDisplayOrderDetail($this, $params);
    }
    
    public function hookActionAdminControllerSetMedia($arr)
    {
        TrustPaymentsBasemodule::hookActionAdminControllerSetMedia($this, $arr);
    }
    
    public function hookDisplayBackOfficeHeader($params)
    {
        TrustPaymentsBasemodule::hookDisplayBackOfficeHeader($this, $params);
    }
    
    public function hookDisplayAdminOrderLeft($params)
    {
        return TrustPaymentsBasemodule::hookDisplayAdminOrderLeft($this, $params);
    }
    
    public function hookDisplayAdminOrderTabOrder($params)
    {
        return TrustPaymentsBasemodule::hookDisplayAdminOrderTabOrder($this, $params);
    }
    
    public function hookDisplayAdminOrderContentOrder($params)
    {
        return TrustPaymentsBasemodule::hookDisplayAdminOrderContentOrder($this, $params);
    }
    
    public function hookDisplayAdminOrder($params)
    {
        return TrustPaymentsBasemodule::hookDisplayAdminOrder($this, $params);
    }
    
    public function hookActionAdminOrdersControllerBefore($params)
    {
        return TrustPaymentsBasemodule::hookActionAdminOrdersControllerBefore($this, $params);
    }
    
    public function hookActionObjectOrderPaymentAddBefore($params)
    {
        TrustPaymentsBasemodule::hookActionObjectOrderPaymentAddBefore($this, $params);
    }
    
    public function hookActionOrderEdited($params)
    {
        TrustPaymentsBasemodule::hookActionOrderEdited($this, $params);
    }
}
