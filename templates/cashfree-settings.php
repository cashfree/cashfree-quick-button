<?php

class CF_Templates
{
	/**
     * Generates admin page options using Settings API
    **/
	function adminCfOptions()
    {
        echo
            '<div class="wrap">
                <h2>Cashfree Wordpress Button</h2>
                <form action="options.php" method="POST">';

                    settings_fields('cashfree_fields');
                    do_settings_sections('cashfree_sections');
                    submit_button();

        echo
                '</form>
            </div>';
    }

    /**
     * Uses Settings API to create fields
    **/
    function displayCfOptions()
    {
    	add_settings_section('cashfree_fields', 'Edit Settings', array($this, 'displayCfHeader'), 'cashfree_sections');

        $settings = $this->getSettings();

        foreach ($settings as $settingField => $settingName)
        {
            $displayMethod = $this->getDisplaySettingMethod($settingField);

            add_settings_field(
                $settingField,
                $settingName,
                array(
                    $this,
                    $displayMethod
                ),
                'cashfree_sections',
                'cashfree_fields'
            );

            register_setting('cashfree_fields', $settingField);
        }
    }

    /**
     * Settings page header
    **/
    function displayCfHeader()
    {
        $header = '<p>Cashfree payment button accept payment using single click.</p>';

        echo $header;
    }

    /**
     * Title field of settings page
    **/
    function displayCfTitle()
    {
        $default = get_option('cf_title', "Pay with Cashfree");

        $title = <<<EOT
<input type="text" name="cf_title" id="title" size="35" value="{$default}" /><br>
<label for ="title">This controls the title which the user sees during checkout.</label>
EOT;

        echo $title;
    }

    /**
     * App ID field of settings page
    **/
    function displayCfAppID()
    {
        $default = get_option('cf_app_id');

        $appID = <<<EOT
<input type="text" name="cf_app_id" id="app_id" size="35" value="{$default}" /><br>
<label for ="app_id">The app Id and secret key can be generated from "Credentials" section of Cashfree Dashboard. Use test or live for test or live mode.</label>
EOT;

        echo $appID;
    }

    /**
     * Secret key field of settings page
    **/
    function displayCfSecretKey()
    {
        $default = get_option('cf_secret_key');

        $secretKey = <<<EOT
<input type="text" name="cf_secret_key" id="key_secret" size="35" value="{$default}" /><br>
<label for ="secret_key">The app Id and secret key can be generated from "Credentials" section of Cashfree Dashboard. Use test or live for test or live mode.</label>
EOT;

        echo $secretKey;
    }

    /**
     * Success Message field of settings page
    **/
    function displayCfSuccessMessage()
    {
        $default = get_option('cf_success_message', "Thank you for being with us. Your account has been charged and your transaction is successful. We will be processing your order soon.");

        $successMessage = <<<EOT
<textarea name="cf_success_message" id="success_message" cols="60">{$default}</textarea><br>
<label for ="success_message">This controls the display which the user success message after payment success.</label>
EOT;

        echo $successMessage;
    }

    /**
     * Payment mode field of settings page
    **/
    function displayCfPaymentMode()
    {
        $default = get_option('cf_payment_mode');

        $selected_test_mode = ($default == 'test') ? 'selected' : '' ;
        $selected_prod_mode = ($default == 'prod') ? 'selected' : '' ;

        $paymentMode = <<<EOT
<select name="cf_payment_mode" id="payment_mode" value="{$default}" />
    <option value="test" {$selected_test_mode}>Test</option>
    <option value="prod" {$selected_prod_mode}>Prod</option>
</select>
<br>
<label for ="payment_mode">Payment mode for order.</label>
EOT;

        echo $paymentMode;
    }

    protected function getSettings()
    {
        $settings = array(
            'cf_title'          => 'Title',
            'cf_app_id'         => 'App ID',
            'cf_secret_key'     => 'Secret Key',
            'cf_payment_mode'   => 'Payment Mode',
            'cf_success_message'=> 'Success Message'
        );

        return $settings;
    }

    protected function getDisplaySettingMethod($settingsField)
    {
        $settingsField = ucwords($settingsField);

        $fieldWords = explode('_', $settingsField);

        return 'display' . implode('', $fieldWords);
    }
}
