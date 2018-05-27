<?php
/*
Plugin Name: Gravity Forms Loan Calculator
Plugin URI: http://www.rmweblab.com
Description: Gravity Forms Adon for Loan Calculator with custom calculation rules.
Version: 1.0.0
Author: RM Web Lab
Author URI: http://www.rmweblab.com
Copyright: © 2018 RM Web Lab.
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: gravityformsrmloancalculator
Domain Path: /languages
*/

define( 'RMGF_LOAN_CALCUALTOR_VERSION', '1.0.0' );

add_action( 'gform_loaded', array( 'RMGF_Loan_Calculator', 'load' ), 5 );

class RMGF_Loan_Calculator {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once( 'class-rmgf-loan-calcualtor.php' );

        GFAddOn::register( 'RMGFLoanCalculator' );
    }

}

function rmgf_loan_calculator() {
    return RMGFLoanCalculator::get_instance();
}
