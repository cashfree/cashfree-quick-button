<?php

require_once __DIR__.'/../templates/cashfree-settings.php';

class Cashfree_Settings
{ 
    public function __construct()
    {
        // Creates a new menu page for cashfree's settings
        add_action('admin_menu', array($this, 'cashfreeAdminSetup'));

        // Initializes display options when admin page is initialized
        add_action('admin_init', array($this, 'displayCfOptions'));

        $this->template = new Cashfree_Setting_Templates();
    }

    /**
     * Creating up the settings page for the plug-in on the menu page
    **/
    function cashfreeAdminSetup()
    {
    	add_menu_page('Cashfree Payment Button', 'Cashfree', 'manage_options', 'cashfree', array($this, 'adminCfOptions'));
    }

    /**
     * Generates admin page options using Settings API
    **/
    function adminCfOptions()
    {
        $this->template->adminCfOptions();
    }

    /**
     * Uses Settings API to create fields
    **/
    function displayCfOptions()
    {
        $this->template->displayCfOptions();
    }

    /**
     * Settings page header
    **/        
    function displayCfHeader()
    {
        $this->template->displayCfHeader();
    }

    /**
     * Title field of settings page
    **/
    function displayCfTitle()
    {	
        $this->template->displayCfTitle();
    }

    /**
     * App ID field of settings page
    **/
    function displayCfAppID()
    {
        $this->template->displayCfAppID();
    }

    /**
     * Secret key field of settings page
    **/
    function displayCfSecretKey()
    {
        $this->template->displayCfSecretKey();
    }

    /**
     * Secret key field of settings page
    **/
    function displayCfSuccessMessage()
    {
        $this->template->displayCfSuccessMessage();
    }

    /**
     * Payment mode field of settings page
    **/
    function displayCfPaymentMode()
    {
        $this->template->displayCfPaymentMode();
    }
}