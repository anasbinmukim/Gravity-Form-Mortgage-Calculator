<?php

GFForms::include_addon_framework();

class RMGFLoanCalculator extends GFAddOn {

	protected $_version = GF_SIMPLE_ADDON_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'gravityformsrmloancalculator';
	protected $_path = 'gravityformsrmloancalculator/gravityformsrmloancalculator.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Loan Calculator';
	protected $_short_title = 'Loan Calculator';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFSimpleAddOn
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new RMGFLoanCalculator();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		//gform_pre_submission
		//gform_after_submission
		//gform_pre_submission_filter
		add_action( 'gform_after_submission', array( $this, 'add_submitted_form' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'gf_mortgage_calculator_script' ));
		add_shortcode( 'gf_mortgage_calculator', array( $this, 'gf_mortgage_calculator_shortcode_func' ) );
		add_shortcode( 'gf_mort_savings', array( $this, 'gf_mortgage_saving_field_shortcode_func' ) );
		add_shortcode( 'gf_mort_savings', array( $this, 'gf_mortgage_saving_field_shortcode_func' ) );
		add_shortcode( 'gf_mortgage_result', array( $this, 'gf_mortgage_calculator_result_shortcode_func' ) );
		add_shortcode( 'gf_mortgage_confirmation', array( $this, 'gf_mortgage_calculator_confirmation_redirect_shortcode_init' ) );
		add_filter( 'gform_confirmation', array( $this, 'gf_mortgage_calculator_confirmation_redirect' ), 10, 4);
	}

	public function gf_mortgage_calculator_confirmation_redirect_shortcode_init(){
			if(!isset($_GET['cal_result'])){
				$url          = esc_url( get_permalink() );
				$url          = esc_url( add_query_arg( 'cal_result', 'yes', $url ) );
				$confirmation = '<script type="text/javascript">window.location = "'.$url.'"</script>';
				return $confirmation;
			}

	}

	public function gf_mortgage_calculator_confirmation_redirect($confirmation, $form, $entry, $ajax){
			if(!isset($_GET['cal_result'])){
				$form_id = $form['id'];
				$form_meta = RGFormsModel::get_form_meta( $form_id );
				$form_settings = array();
				if(isset($form_meta['gravityformsrmloancalculator'])){
				  $form_settings = $form_meta['gravityformsrmloancalculator'];
					if(isset($form_settings['buy_or_refi'])){
						$url          = esc_url( get_permalink() );
						$url          = esc_url( add_query_arg( 'cal_result', 'yes', $url ) );
						$confirmation = array( 'redirect' => $url );
						return $confirmation;
					}else{
						return $confirmation;
					}
				}else{
					return $confirmation;
				}
			}else{
				return $confirmation;
			}
	}



	function gf_mortgage_calculator_script() {
		if(!isset($_GET['cal_result'])){
			return;
		}
		//gform_confirmation_loaded
		//gform_post_render
		?>
		<script type="text/javascript">
		// jQuery(document).bind('gform_page_loaded', function(event, form_id, current_page){
		//     //GFMortgageUpdateCalc();
		// });

		//jQuery(document).bind('gform_confirmation_loaded', function(){
		jQuery( document ).ready(function() {

				jQuery('.input-number-comma-type').keyup(function(event) {
						// skip for arrow keys
					  if(event.which >= 37 && event.which <= 40){
					   event.preventDefault();
					  }

					  jQuery(this).val(function(index, value) {
					      value = value.replace(/,/g,''); // remove commas from existing input
					      return numberWithCommas(value); // add commas back in
					  });
				});

				GFMortgageUpdateCalc();

				//update using mini calculator on the last step
				jQuery(".miniCalc").change(function() {
						var mincalrate = jQuery("#miniCalcRate").val();
						var minicalprinciple = jQuery("#miniCalcPrinciple").val();
						minicalprinciple = minicalprinciple.replace(/,/g,'');
						GFMortgageUpdateCalc(mincalrate, minicalprinciple);
				});

				jQuery(".miniCalc").keyup(function() {
						var mincalrate = jQuery("#miniCalcRate").val();
						var minicalprinciple = jQuery("#miniCalcPrinciple").val();
						minicalprinciple = minicalprinciple.replace(/,/g,'');
						GFMortgageUpdateCalc(mincalrate, minicalprinciple);
				});

		});

		function GFMortgageUpdateCalc(rate, principle) {
			var mcbuyorrefi = jQuery("#mc-buy-or-refi").val();
			var mchomevalue = jQuery("#mc-home-value").val();
			var mccurrentlyowe = jQuery("#mc-currently-owe").val();
			var mccurrentrate = jQuery("#mc-current-rate").val();

				if (principle != null && rate != null) {
						P = parseInt(principle);
						I = parseFloat(rate) / 100 / 12;
				}else {
							if (mcbuyorrefi == "Refinance") {
									P = parseInt(mccurrentlyowe);
									I = parseFloat(mccurrentrate) / 100 / 12;
									//jQuery("#miniCalcRate").val(mccurrentrate);
									jQuery("#miniCalcRate").val('4.5');
									jQuery("#miniCalcPrinciple").val(numberWithCommas(mccurrentlyowe));
							} else {
									P = parseInt(mchomevalue);
									I = 4 / 100 / 12;
									jQuery("#miniCalcRate").val('4.5');
									jQuery("#miniCalcPrinciple").val(numberWithCommas(mchomevalue));
							}
					}
				var N = 30 * 12;
				jQuery(".finalPayment").text("Payment: $" + GFMortgageMonthlyPayment(P, N, I).toFixed(2) + "*");
				jQuery(".conventionalSavings").text("$" + GFMortgageconventionalMonthlySavings(P).toFixed(2));
				jQuery(".fhaSavings").text("$" + GFMortgagefhaMonthlySavings(P).toFixed(2));
		};

		function GFMortgageMonthlyPayment(p, n, i) {
			return p * i * (Math.pow(1 + i, n)) / (Math.pow(1 + i, n) - 1);
		}

		function GFMortgagefhaMonthlySavings(p) {
				return (p * .0085) / 12
		};

		function GFMortgageconventionalMonthlySavings(p) {
				return (p * .004) / 12
		};

		function numberWithCommas(x) {
		    var parts = x.toString().split(".");
		    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		    return parts.join(".");
		}
		</script>
		<?php
	}

	public function gf_mortgage_saving_field_shortcode_func($atts, $content = null) {
		extract(shortcode_atts(array(
			'val' => '',
		), $atts));
		ob_start();
		?>
		<span class="fhaSavings"></span>
		<?php

		$retcalculator_savings = ob_get_contents();
		ob_end_clean();
		return $retcalculator_savings;

	}


	public function gf_mortgage_calculator_result_shortcode_func($atts, $content = null) {
		extract(shortcode_atts(array(
			'form_id' => 1,
		), $atts));

		if(isset($_GET['cal_result'])){
			return do_shortcode($content);
		}else{
			return do_shortcode('[gravityform id="'.$form_id.'" title="false" description="false" ajax="true"]');
		}
	}


	public function gf_mortgage_calculator_shortcode_func( $atts, $content = "" ) {
		extract(shortcode_atts(array(
			'form_id' => '',
		), $atts));
		ob_start();

		$buy_or_refi_value = $estimated_price_value = $current_rate_value = $currently_owe_value = 0;
		$buy_or_refi_field = $estimated_price_field = $current_rate_field = $currently_owe_field = 0;

		$submitted_form = $this->get_submitted_forms();
		// echo "<pre>";
		// print_r($submitted_form);
		// echo "</pre>";
		if(isset($submitted_form['form_id'])){
			  //echo rgar($submitted_form, ' 1 ');
				$form_id = $submitted_form['form_id'];
				$form_meta = RGFormsModel::get_form_meta( $form_id );
				$form_settings  = array();
				//Get from settings data
				if(isset($form_meta['gravityformsrmloancalculator'])){
					$form_settings = $form_meta['gravityformsrmloancalculator'];
				}

				// echo "<pre>";
				// print_r($form_settings);
				// echo "</pre>";


				if(isset($form_settings['buy_or_refi'])){
					$buy_or_refi_field = $form_settings['buy_or_refi'];
				}
				if(isset($form_settings['estimated_price'])){
					$estimated_price_field = $form_settings['estimated_price'];
				}
				if(isset($form_settings['current_rate'])){
					$current_rate_field = $form_settings['current_rate'];
				}
				if(isset($form_settings['currently_owe'])){
					$currently_owe_field = $form_settings['currently_owe'];
				}


				foreach ($submitted_form as $key => $value) {
						//get submitted values from saved data
						if($buy_or_refi_field == $key){
							$buy_or_refi_value = $value;
						}
						if($estimated_price_field == $key){
							$estimated_price_value = $value;
						}
						if($current_rate_field == $key){
							$current_rate_value = $value;
							$current_rate_value = str_replace( ',', '', $current_rate_value );
						}
						if($currently_owe_field == $key){
							$currently_owe_value = $value;
							$currently_owe_value = str_replace( ',', '', $currently_owe_value );
						}
				}

				//echo $estimated_price_value;

		}

		?>
		<input type="hidden" name="mc-buy-or-refi" id="mc-buy-or-refi" value="<?php echo $buy_or_refi_value; ?>">
		<input type="hidden" name="mc-home-value" id="mc-home-value" value="<?php echo $estimated_price_value; ?>">
		<input type="hidden" name="mc-current-rate" id="mc-current-rate" value="<?php echo $current_rate_value; ?>">
		<input type="hidden" name="mc-currently-owe" id="mc-currently-owe" value="<?php echo $currently_owe_value; ?>">

		<div class="calculator-wrap"><span class="finalPayment">Payment: $718.47*</span>
		<span class="mort-cal-estd-loan-amount">
			Estimated Loan Amount: $<input id="miniCalcPrinciple" class="miniCalc input-number-comma-type" value="">
		</span>
		<span class="mort-cal-interested-rate">
		Interest Rate:
		<select id="miniCalcRate" class="miniCalc" name="miniRate" style="max-width:200px;">
			<option value="3.5" selected="">3.5%</option>
			<option value="3.625">3.625%</option>
			<option value="3.75">3.75%</option>
			<option value="3.875">3.875%</option>
			<option value="4.0">4.0%</option>
			<option value="4.125">4.125%</option>
			<option value="4.25">4.25%</option>
			<option value="4.375">4.375%</option>
			<option value="4.5" selected="selected">4.5%</option>
			<option value="4.625">4.625%</option>
			<option value="4.75">4.75%</option>
			<option value="4.875">4.875%</option>
			<option value="5.0">5.0%</option>
			<option value="5.125">5.125%</option>
			<option value="5.25">5.25%</option>
			<option value="5.375">5.375%</option>
			<option value="5.5">5.5%</option>
			<option value="5.5">5.5%+</option>
		</select>
	</span>

		</div>

		<?php

		$content = ob_get_clean();
		return $content;
	}


	/**
	 * Configures the settings which should be rendered on the Form Settings > Loan Calculator tab.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => esc_html__( 'Form Field and Calculator Map Settings', 'gravityformsrmloancalculator' ),
				'fields' => array(
					array(
						'name'     => 'buy_or_refi',
						'label'    => esc_html__( 'Buy or Refi', 'gravityformsrmloancalculator' ),
						'type'     => 'field_select',
						'class'    => 'medium',
						'tooltip'  => sprintf( '<h6>%s</h6> %s', esc_html__( 'Buy or Refi', 'gravityformsrmloancalculator' ), esc_html__( 'Are you planning to buy or refi?', 'gravityformsrmloancalculator' ) )
					),
					array(
						'name'     => 'estimated_price',
						'label'    => esc_html__( 'Estimated Price', 'gravityformsrmloancalculator' ),
						'type'     => 'field_select',
						'class'    => 'medium',
						'tooltip'  => sprintf( '<h6>%s</h6> %s', esc_html__( 'Estimated Price', 'gravityformsrmloancalculator' ), esc_html__( 'Estimated purchase price', 'gravityformsrmloancalculator' ) )
					),
					array(
						'name'     => 'current_rate',
						'label'    => esc_html__( 'Current Rate', 'gravityformsrmloancalculator' ),
						'type'     => 'field_select',
						'class'    => 'medium',
						'tooltip'  => sprintf( '<h6>%s</h6> %s', esc_html__( 'Current Rate', 'gravityformsrmloancalculator' ), esc_html__( 'What\'s your current rate?', 'gravityformsrmloancalculator' ) )
					),
					array(
						'name'     => 'currently_owe',
						'label'    => esc_html__( 'Currently Owe', 'gravityformsrmloancalculator' ),
						'type'     => 'field_select',
						'class'    => 'medium',
						'tooltip'  => sprintf( '<h6>%s</h6> %s', esc_html__( 'Currently Owe', 'gravityformsrmloancalculator' ), esc_html__( 'How much to you currently owe?', 'gravityformsrmloancalculator' ) )
					)

				),
			),
		);
	}


	public function get_submitted_forms() {

		// always check the cookie first; will allow user meta vs cookie to be set per page in the future
		$submitted_forms = (array) json_decode( stripslashes( rgar( $_COOKIE, 'rmgf_submitted_forms' ) ) );

		return array_filter( $submitted_forms );

	}

	public function add_submitted_form( $entry, $form ) {

			if( ! headers_sent() ) {
				$expiration = strtotime( '+1 day' );
				if(isset($_COOKIE['rmgf_submitted_forms'])){
					unset($_COOKIE['rmgf_submitted_forms']);
				}
				setcookie( 'rmgf_submitted_forms', json_encode( $entry ), $expiration, '/' );
			}

	}

}
